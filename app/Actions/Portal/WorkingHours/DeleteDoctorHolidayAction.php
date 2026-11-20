<?php

declare(strict_types=1);

namespace App\Actions\Portal\WorkingHours;

use App\Models\Doctor;
use App\Models\DoctorHoliday;
use Illuminate\Support\Facades\DB;

final class DeleteDoctorHolidayAction
{
    public function handle(Doctor $doctor, DoctorHoliday $holiday): void
    {
        DB::transaction(function () use ($doctor, $holiday): void {
            Doctor::query()->whereKey($doctor->id)->lockForUpdate()->firstOrFail();

            $lockedHoliday = DoctorHoliday::query()
                ->whereKey($holiday->id)
                ->lockForUpdate()
                ->firstOrFail();

            abort_unless(
                (int) $lockedHoliday->doctor_id === (int) $doctor->id,
                403,
                'تعطیلی متعلق به این پزشک نیست.'
            );

            $lockedHoliday->delete();
        });
    }
}
