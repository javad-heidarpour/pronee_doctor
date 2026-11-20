<?php

namespace App\\Actions\\Portal\\WorkingHours;

use App\\Models\\Doctor;
use App\\Models\\DoctorWorkingHour;
use Illuminate\\Support\\Facades\\DB;

class ToggleWorkingHourAction
{
    /**
     * @return bool False when activating would overlap another active schedule.
     */
    public function handle(DoctorWorkingHour $workingHour): bool
    {
        return DB::transaction(function () use ($workingHour): bool {
            Doctor::query()
                ->whereKey($workingHour->doctor_id)
                ->lockForUpdate()
                ->firstOrFail();

            $lockedWorkingHour = DoctorWorkingHour::query()
                ->whereKey($workingHour->id)
                ->lockForUpdate()
                ->firstOrFail();

            if (!$lockedWorkingHour->is_active) {
                $conflictExists = DoctorWorkingHour::query()
                    ->where('doctor_id', $lockedWorkingHour->doctor_id)
                    ->where('is_active', true)
                    ->where('day_of_week', $lockedWorkingHour->day_of_week)
                    ->where('start_time', '<', $lockedWorkingHour->end_time)
                    ->where('end_time', '>', $lockedWorkingHour->start_time)
                    ->where('id', '!=', $lockedWorkingHour->id)
                    ->lockForUpdate()
                    ->exists();

                if ($conflictExists) {
                    return false;
                }
            }

            $lockedWorkingHour->update([
                'is_active' => !$lockedWorkingHour->is_active,
            ]);

            return true;
        });
    }
}
