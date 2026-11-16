<?php

namespace App\Actions\Portal\Appointment;

use App\Models\Appointment;
use App\Models\TimeSlot;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class UpdateAppointmentStatusAction
{
    /**
     * Update appointment and its slot atomically.
     *
     * @return string updated|already_visited|visit_completion_not_allowed
     */
    public function handle(Appointment $appointment, string $newStatus): string
    {
        return DB::transaction(function () use ($appointment, $newStatus) {
            $lockedAppointment = Appointment::query()
                ->whereKey($appointment->id)
                ->lockForUpdate()
                ->firstOrFail();

            if ($lockedAppointment->status === 'visited') {
                return 'already_visited';
            }

            // Visit completion is handled by the dedicated visit-details flow.
            if ($newStatus === 'visited') {
                return 'visit_completion_not_allowed';
            }

            $timeSlot = $lockedAppointment->time_slot_id
                ? TimeSlot::query()
                    ->whereKey($lockedAppointment->time_slot_id)
                    ->lockForUpdate()
                    ->first()
                : null;

            if (in_array($newStatus, ['pending', 'confirmed'], true)) {
                $anotherAppointmentExists = Appointment::query()
                    ->where('time_slot_id', $lockedAppointment->time_slot_id)
                    ->where('id', '!=', $lockedAppointment->id)
                    ->whereIn('status', ['pending', 'confirmed'])
                    ->exists();

                if ($anotherAppointmentExists) {
                    throw ValidationException::withMessages([
                        'status' => 'امکان فعال‌سازی مجدد این نوبت وجود ندارد؛ زیرا ساعت انتخاب‌شده توسط نوبت دیگری رزرو شده است. لطفاً ساعت دیگری انتخاب کنید.',
                    ]);
                }
            }

            $lockedAppointment->update(['status' => $newStatus]);

            if ($timeSlot) {
                if ($newStatus === 'cancelled') {
                    $hasActiveAppointment = Appointment::query()
                        ->where('time_slot_id', $timeSlot->id)
                        ->whereIn('status', ['pending', 'confirmed'])
                        ->exists();

                    if (!$hasActiveAppointment) {
                        $timeSlot->update(['status' => 'available']);
                    }
                } elseif (in_array($newStatus, ['pending', 'confirmed'], true)) {
                    $timeSlot->update(['status' => 'booked']);
                }
            }

            return 'updated';
        });
    }
}
