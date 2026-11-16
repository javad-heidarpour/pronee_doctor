<?php

namespace App\Actions\Payments;

use App\Models\Appointment;
use App\Models\AppointmentPayment;
use App\Models\TimeSlot;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CancelFailedAppointmentAction
{
    /**
     * Mark one payment attempt failed without cancelling a reservation that
     * has another successful or unresolved payment attempt.
     *
     * @return string cancelled|already_paid|waiting|not_cancellable
     */
    public function handle(AppointmentPayment $payment): string
    {
        return DB::transaction(function () use ($payment) {
            // Keep the same lock order as payment initiation and settlement:
            // appointment first, then payment attempts in a stable order.
            $appointment = Appointment::query()
                ->whereKey($payment->appointment_id)
                ->lockForUpdate()
                ->first();

            $paymentAttempts = AppointmentPayment::query()
                ->where('appointment_id', $payment->appointment_id)
                ->orderBy('id')
                ->lockForUpdate()
                ->get();

            $lockedPayment = $paymentAttempts->firstWhere('id', $payment->id);

            if (!$lockedPayment) {
                throw new ModelNotFoundException();
            }

            if ($lockedPayment->status === 'paid') {
                return 'already_paid';
            }

            if (!$appointment) {
                $lockedPayment->update(['status' => 'failed']);
                return 'not_cancellable';
            }

            $lockedPayment->update(['status' => 'failed']);

            $otherPayments = $paymentAttempts
                ->reject(fn ($item) => (int) $item->id === (int) $lockedPayment->id);

            if ($otherPayments->contains(fn ($item) => $item->status === 'paid')) {
                $appointment->status = 'confirmed';
                $appointment->reservation_expires_at = null;

                if (Schema::hasColumn('appointments', 'payment_status')) {
                    $appointment->payment_status = 'paid';
                }

                $appointment->save();

                return 'already_paid';
            }

            $hasUnresolvedPayment = $otherPayments->contains(
                fn ($item) => in_array($item->status, ['pending', 'initiated'], true)
            );

            if ($hasUnresolvedPayment) {
                return 'waiting';
            }

            // Do not cancel a manually confirmed appointment just because one
            // payment attempt failed.
            if ($appointment->status !== 'pending') {
                return 'not_cancellable';
            }

            $appointment->status = 'cancelled';
            $appointment->reservation_expires_at = null;

            if (Schema::hasColumn('appointments', 'payment_status')) {
                $appointment->payment_status = 'failed';
            }

            $appointment->save();

            if (!$appointment->time_slot_id) {
                return 'cancelled';
            }

            $timeSlot = TimeSlot::query()
                ->whereKey($appointment->time_slot_id)
                ->lockForUpdate()
                ->first();

            if ($timeSlot && $timeSlot->status === 'booked') {
                $hasOtherActiveAppointment = Appointment::query()
                    ->where('time_slot_id', $timeSlot->id)
                    ->whereIn('status', ['pending', 'confirmed'])
                    ->exists();

                if (!$hasOtherActiveAppointment) {
                    $timeSlot->status = 'available';
                    $timeSlot->save();
                }
            }

            return 'cancelled';
        });
    }
}
