<?php

namespace App\Actions\Portal\Shifts;

use App\Models\Doctor;
use App\Models\DoctorHoliday;
use App\Models\TimeSlot;
use Illuminate\Support\Facades\DB;

class ToggleTimeSlotAction
{
    public function handle(TimeSlot $slot): ?string
    {
        return DB::transaction(function () use ($slot) {
            Doctor::query()->whereKey($slot->doctor_id)->lockForUpdate()->firstOrFail();

            $lockedSlot = TimeSlot::query()
                ->whereKey($slot->id)
                ->lockForUpdate()
                ->first();

            if (!$lockedSlot || $lockedSlot->isBooked()) {
                return null;
            }

            $newStatus = $lockedSlot->status === 'available'
                ? 'cancelled'
                : 'available';

            if ($newStatus === 'available') {
                if (DoctorHoliday::query()
                    ->where('doctor_id', $lockedSlot->doctor_id)
                    ->where('clinic_id', $lockedSlot->clinic_id)
                    ->whereDate('holiday_date', $lockedSlot->date->format('Y-m-d'))
                    ->exists()) {
                    return null;
                }

                $overlap = TimeSlot::query()
                    ->overlapping(
                        (int) $lockedSlot->doctor_id,
                        $lockedSlot->date->format('Y-m-d'),
                        $lockedSlot->start_time,
                        $lockedSlot->end_time,
                        (int) $lockedSlot->id
                    )
                    ->lockForUpdate()
                    ->exists();

                if ($overlap) {
                    return null;
                }
            }

            $lockedSlot->update(['status' => $newStatus]);

            return $newStatus;
        });
    }
}
