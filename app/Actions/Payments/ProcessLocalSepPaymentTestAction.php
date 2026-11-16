<?php

namespace App\Actions\Payments;

use App\Models\Appointment;
use App\Models\AppointmentPayment;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class ProcessLocalSepPaymentTestAction
{
    /**
     * @return string already_paid|reservation_not_payable|failed|success
     */
    public function handle(
        AppointmentPayment $payment,
        Appointment $appointment,
        string $testResult
    ): string {
        return DB::transaction(function () use ($payment, $appointment, $testResult) {
            // Match the appointment -> payment lock order used by all
            // other payment lifecycle operations.
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
                ! in_array($lockedAppointment->status, ['pending', 'confirmed'], true)
            ) {
                return 'reservation_not_payable';
            }

            if ($testResult === 'fail') {
                $lockedPayment->update([
                    'status' => 'failed',
                    'gateway_result_code' => 'LOCAL_TEST_FAILED',
                    'gateway_result_message' => 'تست محلی؛ تراکنش واقعی انجام نشده است.',
                    'paid_at' => null,
                    'gateway_verified_at' => null,
                ]);

                return 'failed';
            }

            $lockedPayment->update([
                'status' => 'paid',
                'ref_num' => 'LOCAL-TEST-' . $lockedPayment->id,
                'terminal_id' => 'LOCAL-TEST',
                'gateway_result_code' => 'LOCAL_TEST_SUCCESS',
                'gateway_result_message' => 'تست محلی؛ پرداخت واقعی SEP نیست.',
                'paid_at' => now(),
                'gateway_verified_at' => null,
            ]);

            if (Schema::hasColumn('appointments', 'payment_status')) {
                $lockedAppointment->payment_status = 'paid';
            }

            $lockedAppointment->status = 'confirmed';
            $lockedAppointment->save();

            return 'success';
        });
    }
}
