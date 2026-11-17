<?php

namespace App\\Actions\\Payments;

use App\\Models\\Appointment;
use App\\Models\\AppointmentPayment;
use App\\Services\\Payments\\SepPaymentService;
use Illuminate\\Support\\Facades\\DB;
use Illuminate\\Support\\Facades\\Log;
use Illuminate\\Support\\Facades\\Schema;
use RuntimeException;

class VerifyAndSettleSepPaymentAction
{
    public function __construct(
        private readonly NormalizeSepVerificationResponseAction $normalizeResponse
    ) {
    }

    /**
     * Verify gateway identifiers and amount before atomically settling payment.
     *
     * @return array{status:string,message?:string}
     */
    public function handle(
        AppointmentPayment $payment,
        Appointment $appointment,
        string $refNum,
        string $terminalId,
        SepPaymentService $sep,
        CancelFailedAppointmentAction $cancelFailedAppointment
    ): array {
        if ($payment->status === 'paid') {
            return ['status' => 'already_paid'];
        }

        $verifyResponse = $sep->verifyTransaction($refNum);

        try {
            $normalized = $this->normalizeResponse->handle($verifyResponse);
        } catch (RuntimeException $exception) {
            Log::error('SEP invalid verification response', [
                'payment_id' => $payment->id,
                'ref_num_fingerprint' => hash('sha256', $refNum),
            ]);

            throw $exception;
        }

        $resultCode = $normalized['result_code'];
        $resultMessage = $normalized['result_message'];
        $detail = $normalized['transaction_detail'];

        Log::info('SEP VERIFY RESPONSE', [
            'payment_id' => $payment->id,
            'ref_num_fingerprint' => hash('sha256', $refNum),
            'result_code' => $resultCode,
            'success' => $normalized['success'],
            'response_keys' => array_keys($verifyResponse),
            'detail_keys' => array_keys($detail),
            'original_amount' => $normalized['verified_amount'],
            'affective_amount' => $detail['AffectiveAmount'] ?? null,
        ]);

        if (
            ! $normalized['success'] ||
            ! is_numeric($resultCode) ||
            (int) $resultCode !== 0
        ) {
            Log::warning('SEP verification unsuccessful', [
                'payment_id' => $payment->id,
                'ref_num_fingerprint' => hash('sha256', $refNum),
                'result_code' => $resultCode,
                'success' => $normalized['success'],
            ]);

            $cancelResult = $cancelFailedAppointment->handle($payment);

            return [
                'status' => match ($cancelResult) {
                    'cancelled' => 'failed',
                    'already_paid' => 'already_paid',
                    'waiting' => 'waiting',
                    default => 'conflict',
                },
            ];
        }

        $verifiedRefNum = $normalized['verified_ref_num'];
        $verifiedTerminal = $normalized['verified_terminal_number'];
        $callbackTerminal = trim($terminalId);

        // SEP documents RefNum and TerminalNumber in TransactionDetail.
        // ResNum is correlated locally through payment_uuid in the callback lookup.
        $terminalMatches = is_string($verifiedTerminal) || is_int($verifiedTerminal);
        $terminalValue = $terminalMatches
            ? trim((string) $verifiedTerminal)
            : '';

        $terminalMatches = $terminalMatches
            && ctype_digit($terminalValue)
            && ctype_digit($callbackTerminal)
            && ltrim($terminalValue, '0') === ltrim($callbackTerminal, '0');

        if (
            $verifiedRefNum === '' ||
            ! hash_equals($refNum, $verifiedRefNum) ||
            ! $terminalMatches
        ) {
            Log::critical('SEP verified transaction identity mismatch', [
                'payment_id' => $payment->id,
                'callback_ref_num_fingerprint' => hash('sha256', $refNum),
                'verified_ref_num_fingerprint' => $verifiedRefNum !== '' ? hash('sha256', $verifiedRefNum) : null,
                'callback_terminal_fingerprint' => hash('sha256', $callbackTerminal),
                'verified_terminal_fingerprint' => is_string($verifiedTerminal) || is_int($verifiedTerminal) ? hash('sha256', (string) $verifiedTerminal) : null,
            ]);

            return ['status' => 'verification_mismatch'];
        }

        $verifiedAmount = $normalized['verified_amount'];

        if (
            ! is_numeric($verifiedAmount) ||
            (int) $verifiedAmount !== (int) $payment->amount_rial
        ) {
            Log::critical('SEP transaction amount mismatch', [
                'payment_id' => $payment->id,
                'ref_num_fingerprint' => hash('sha256', $refNum),
                'expected_amount' => $payment->amount_rial,
                'verified_amount' => $verifiedAmount,
                'detail_keys' => array_keys($detail),
            ]);

            return ['status' => 'amount_mismatch'];
        }

        $settlementStatus = DB::transaction(function () use (
            $payment,
            $appointment,
            $refNum,
            $terminalId,
            $resultCode,
            $resultMessage
        ) {
            // Match the appointment -> payment lock order used by initiation
            // and cancellation to reduce deadlock risk on concurrent callbacks.
            $lockedAppointment = Appointment::query()
                ->whereKey($appointment->id)
                ->lockForUpdate()
                ->firstOrFail();

            $lockedPayment = AppointmentPayment::query()
                ->whereKey($payment->id)
                ->lockForUpdate()
                ->firstOrFail();

            if ($lockedPayment->status === 'paid') {
                return 'already_paid';
            }

            if (
                $lockedPayment->status === 'failed' ||
                $lockedAppointment->status === 'cancelled' ||
                ! in_array($lockedPayment->status, ['pending', 'initiated'], true)
            ) {
                return 'conflict';
            }

            // A payment UUID (ResNum) may only settle against its own first
            // verified reference. Replays with the same RefNum are idempotent;
            // a different reference for the same payment is treated as conflict.
            $storedRefNum = trim((string) $lockedPayment->ref_num);

            if ($storedRefNum !== '' && ! hash_equals($storedRefNum, $refNum)) {
                Log::critical('SEP callback reference changed for payment', [
                    'payment_id' => $lockedPayment->id,
                    'stored_ref_num_fingerprint' => hash('sha256', $storedRefNum),
                    'callback_ref_num_fingerprint' => hash('sha256', $refNum),
                ]);

                return 'verification_mismatch';
            }

            // RefNum must not already be bound to another payment record.
            $referenceOwner = AppointmentPayment::query()
                ->where('gateway_name', 'sep')
                ->where('ref_num', $refNum)
                ->where('id', '<>', $lockedPayment->id)
                ->lockForUpdate()
                ->first();

            if ($referenceOwner) {
                Log::critical('SEP RefNum already assigned to another payment', [
                    'payment_id' => $lockedPayment->id,
                    'reference_owner_payment_id' => $referenceOwner->id,
                    'ref_num_fingerprint' => hash('sha256', $refNum),
                ]);

                return 'verification_mismatch';
            }

            $lockedPayment->update([
                'status' => 'paid',
                'ref_num_fingerprint' => hash('sha256', $refNum),
                'terminal_id' => $terminalId,
                'gateway_result_code' => (string) $resultCode,
                'gateway_result_message' => $resultMessage !== ''
                    ? $resultMessage
                    : 'تراکنش توسط سامان‌کیش تأیید شد.',
                'paid_at' => now(),
                'gateway_verified_at' => now(),
            ]);

            if (Schema::hasColumn('appointments', 'payment_status')) {
                $lockedAppointment->payment_status = 'paid';
            }

            $lockedAppointment->status = 'confirmed';
            $lockedAppointment->reservation_expires_at = null;
            $lockedAppointment->save();

            return 'success';
        });

        return ['status' => $settlementStatus];
    }
}
