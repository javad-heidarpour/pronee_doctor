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

        if (
            !is_array($verifyResponse) ||
            !array_key_exists('ResultCode', $verifyResponse)
        ) {
            Log::error('SEP invalid verification response', [
                'payment_id' => $payment->id,
                'ref_num' => $refNum,
            ]);

            throw new RuntimeException('پاسخ استعلام سامان‌کیش معتبر نیست.');
        }

        $resultCode = $verifyResponse['ResultCode'];
        $resultMessage = (string) (
            $verifyResponse['ResultDescription']
            ?? $verifyResponse['ResultMessage']
            ?? $verifyResponse['Message']
            ?? ''
        );
        $detail = $verifyResponse['TransactionDetail'] ?? [];

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

            $cancelled = $cancelFailedAppointment->handle($payment);

            return [
                'status' => $cancelled ? 'failed' : 'already_paid',
            ];
        }

        $verifiedAmount = is_array($detail)
            ? ($detail['OrginalAmount'] ?? null)
            : null;

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
                return true;
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

            return false;
        });

        return [
            'status' => $alreadyPaid ? 'already_paid' : 'success',
        ];
    }
}