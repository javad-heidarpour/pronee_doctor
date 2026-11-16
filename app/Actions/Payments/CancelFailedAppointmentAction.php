<?php

namespace App\Actions\Payments;

use App\Models\Appointment;
use App\Models\AppointmentPayment;
use App\Models\TimeSlot;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CancelFailedAppointmentAction
{
    /**
     * لغو اتمیک رزرو پس از اعلام قطعی ناموفق بودن پرداخت.
     *
     * @return bool false اگر پرداخت هم‌زمان قبلاً موفق شده باشد.
     */
    public function handle(AppointmentPayment $payment): bool
    {
        return DB::transaction(function () use ($payment) {
            $lockedPayment = AppointmentPayment::query()
                ->whereKey($payment->id)
                ->lockForUpdate()
                ->firstOrFail();

            if ($lockedPayment->status === 'paid') {
                return false;
            }

            $appointment = Appointment::query()
                ->whereKey($lockedPayment->appointment_id)
                ->lockForUpdate()
                ->first();

            $lockedPayment->update([
                'status' => 'failed',
            ]);

            if (!$appointment) {
                return true;
            }

            $appointment->status = 'cancelled';
            $appointment->reservation_expires_at = null;

            if (Schema::hasColumn('appointments', 'payment_status')) {
                $appointment->payment_status = 'failed';
            }

            $appointment->save();

            if (!$appointment->time_slot_id) {
                return true;
            }

            $timeSlot = TimeSlot::query()
                ->whereKey($appointment->time_slot_id)
                ->lockForUpdate()
                ->first();

            if (!$timeSlot || $timeSlot->status !== 'booked') {
                return true;
            }

            $hasOtherActiveAppointment = Appointment::query()
                ->where('time_slot_id', $timeSlot->id)
                ->whereIn('status', ['pending', 'confirmed'])
                ->exists();

            if (!$hasOtherActiveAppointment) {
                $timeSlot->status = 'available';
                $timeSlot->save();
            }

            return true;
        });
    }
}