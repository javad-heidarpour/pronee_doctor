<?php

namespace App\Actions\Portal\WorkingHours;

use App\DTOs\Portal\Scheduling\WorkingHourData;
use App\Models\Doctor;
use App\Models\DoctorWorkingHour;
use Illuminate\Support\Facades\DB;

class SaveWorkingHourAction
{
    /**
     * @return array{saved:bool,duplicate:?DoctorWorkingHour}
     */
    public function handle(
        Doctor $doctor,
        WorkingHourData $data,
        ?DoctorWorkingHour $workingHour = null
    ): array {
        return DB::transaction(function () use ($doctor, $data, $workingHour) {
            // قفل پزشک، عملیات هم‌زمان ثبت/ویرایش بازه‌های کاری او را سریالی می‌کند.
            Doctor::query()
                ->whereKey($doctor->id)
                ->lockForUpdate()
                ->firstOrFail();

            $lockedWorkingHour = null;

            if ($workingHour) {
                $lockedWorkingHour = DoctorWorkingHour::query()
                    ->whereKey($workingHour->id)
                    ->lockForUpdate()
                    ->first();

                abort_unless(
                    $lockedWorkingHour &&
                    (int) $lockedWorkingHour->doctor_id === (int) $doctor->id,
                    403,
                    'بازه کاری متعلق به این پزشک نیست.'
                );
            }

            $query = DoctorWorkingHour::query()
                ->where('doctor_id', $doctor->id)
                ->where('day_of_week', $data->dayOfWeek)
                ->where('start_time', '<', $data->endTime)
                ->where('end_time', '>', $data->startTime)
                ->with('clinic');

            if ($lockedWorkingHour) {
                $query->where('id', '!=', $lockedWorkingHour->id);
            }

            $duplicate = $query->lockForUpdate()->first();

            if ($duplicate) {
                return ['saved' => false, 'duplicate' => $duplicate];
            }

            if ($lockedWorkingHour) {
                $lockedWorkingHour->update($data->toArray());
            } else {
                DoctorWorkingHour::create([
                    'doctor_id' => $doctor->id,
                    ...$data->toArray(),
                    'is_active' => true,
                ]);
            }

            return ['saved' => true, 'duplicate' => null];
        });
    }
}
