<?php

namespace App\Actions\Payments;

use App\Models\Appointment;
use App\Models\AppointmentPayment;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

class RecordSepCallbackFailureAction
{
    public function handle(
        ?AppointmentPayment $payment,
        string $resNum,
        string $refNum,
        Throwable $exception
    ): void {
        Log::error('SEP callback exception', [
            'payment_id' => $payment?->id,
            'res_num_fingerprint' => $resNum !== '' ? hash('sha256', $resNum) : null,
            'ref_num_fingerprint' => $refNum !== '' ? hash('sha256', $refNum) : null,
            'exception_class' => get_class($exception),
        ]);

        if (! $payment) {
            return;
        }

        try {
            DB::transaction(function () use ($payment): void {
                // Lock appointment before payment to keep a single global order.
                Appointment::query()
                    ->whereKey($payment->appointment_id)
                    ->lockForUpdate()
                    ->first();

                $lockedPayment = AppointmentPayment::query()
                    ->whereKey($payment->id)
                    ->lockForUpdate()
                    ->first();

                if (! $lockedPayment || $lockedPayment->status === 'paid') {
                    return;
                }

                $lockedPayment->update([
                    'gateway_result_message' =>
                        'خطا در بررسی تراکنش؛ وضعیت نیازمند بررسی است.',
                ]);
            });
        } catch (Throwable $updateException) {
            Log::error('SEP payment error update failed', [
                'payment_id' => $payment->id,
                'exception_class' => get_class($updateException),
            ]);
        }
    }
}
