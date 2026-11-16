<?php

namespace App\Actions\Portal\WorkingHours;

use App\Models\Doctor;
use App\Models\DoctorWorkingHour;
use Illuminate\Support\Facades\DB;

class DeleteWorkingHourAction
{
    public function handle(DoctorWorkingHour $workingHour): void
    {
        DB::transaction(function () use ($workingHour) {
            Doctor::query()
                ->whereKey($workingHour->doctor_id)
                ->lockForUpdate()
                ->firstOrFail();

            $lockedWorkingHour = DoctorWorkingHour::query()
                ->whereKey($workingHour->id)
                ->lockForUpdate()
                ->firstOrFail();

            $lockedWorkingHour->delete();
        });
    }
}
