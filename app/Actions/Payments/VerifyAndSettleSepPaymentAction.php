<?php

namespace App\Actions\Payments;

use App\Models\Appointment;
use App\Models\AppointmentPayment;
use App\Services\Payments\SepPaymentService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use RuntimeException;

class VerifyAndSettleSepPaymentAction
{
    public function __construct(
        private readonly NormalizeSepVerificationResponseAction $normalizeResponse
    ) {
    }

    /**
     * استعلام از درگاه و ثبت اتمیک نتیجه پرداخت.
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

        $payment->update([
            'ref_num' => $refNum,
            'terminal_id' => $terminalId,
            'gateway_result_message' => 'در انتظار تأیید تراکنش از سامان‌کیش.',
        ]);

        $verifyResponse = $sep->verifyTransaction($refNum);

        try {
            $normalized = $this->normalizeResponse->handle($verifyResponse);
        } catch (RuntimeException $exception) {
            Log::error('SEP invalid verification response', [
                'payment_id' => $payment->id,
                'ref_num' => $refNum,
            ]);

            throw $exception;
        }

        $resultCode = $normalized['result_code'];
        $resultMessage = $normalized['result_message'];
        $detail = $normalized['transaction_detail'];

        Log::info('SEP VERIFY RESPONSE', [
            'payment_id' => $payment->id,
            'ref_num' => $refNum,
            'result_code' => $resultCode,
            'result_message' => $resultMessage,
            'response_keys' => array_keys($verifyResponse),
            'detail_keys' => is_array($detail) ? array_keys($detail) : [],
            'original_amount' => is_array($detail)
                ? ($detail['OrginalAmount'] ?? null)
                : null,
            'affective_amount' => is_array($detail)
                ? ($detail['AffectiveAmount'] ?? null)
                : null,
        ]);

        $payment->update([
            'gateway_result_code' => is_numeric($resultCode)
                ? (string) $resultCode
                : null,
            'gateway_result_message' => $resultMessage !== ''
                ? $resultMessage
                : 'پاسخ استعلام از درگاه دریافت شد.',
        ]);

        if (!is_numeric($resultCode) || (int) $resultCode !== 0) {
            Log::warning('SEP verification unsuccessful', [
                'payment_id' => $payment->id,
                'ref_num' => $refNum,
                'result_code' => $resultCode,
                'result_message' => $resultMessage,
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

        $verifiedAmount = $normalized['verified_amount'];

        if (
            !is_numeric($verifiedAmount) ||
            (int) $verifiedAmount !== (int) $payment->amount_rial
        ) {
            Log::error('SEP transaction amount mismatch', [
                'payment_id' => $payment->id,
                'ref_num' => $refNum,
                'expected_amount' => $payment->amount_rial,
                'verified_amount' => $verifiedAmount,
                'detail_keys' => is_array($detail) ? array_keys($detail) : [],
            ]);

            $payment->update([
                'gateway_result_message' =>
                    'مبلغ تراکنش تأییدشده با مبلغ رکورد پرداخت مطابقت ندارد؛ بررسی دستی لازم است.',
            ]);

            return ['status' => 'amount_mismatch'];
        }

        $alreadyPaid = DB::transaction(function () use (
            $payment,
            $appointment,
            $refNum,
            $terminalId,
            $resultCode,
            $resultMessage
        ) {
            $lockedPayment = AppointmentPayment::query()
                ->whereKey($payment->id)
                ->lockForUpdate()
                ->firstOrFail();

            $lockedAppointment = Appointment::query()
                ->whereKey($appointment->id)
                ->lockForUpdate()
                ->firstOrFail();

            if ($lockedPayment->status === 'paid') {
                return 'already_paid';
            }

            // A late successful callback must not resurrect a payment that
            // was already failed/cancelled and whose slot may have been released.
            if (
                $lockedPayment->status === 'failed' ||
                $lockedAppointment->status === 'cancelled' ||
                !in_array($lockedPayment->status, ['pending', 'initiated'], true)
            ) {
                return 'conflict';
            }

            $lockedPayment->update([
                'status' => 'paid',
                'ref_num' => $refNum,
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

        return [
            'status' => $alreadyPaid,
        ];
    }
}