<?php

namespace App\Actions\Payments;

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
            'res_num' => $resNum,
            'ref_num' => $refNum,
            'exception_class' => get_class($exception),
            'exception' => $exception->getMessage(),
        ]);

        if (! $payment) {
            return;
        }

        try {
            DB::transaction(function () use ($payment): void {
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
                'exception' => $updateException->getMessage(),
            ]);
        }
    }
}
