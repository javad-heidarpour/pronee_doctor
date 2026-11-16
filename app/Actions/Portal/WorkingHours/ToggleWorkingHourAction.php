<?php

namespace App\Actions\Portal\WorkingHours;

use App\Models\DoctorWorkingHour;

class ToggleWorkingHourAction
{
    public function handle(DoctorWorkingHour $workingHour): void
    {
        $workingHour->update([
            'is_active' => !$workingHour->is_active,
        ]);
    }
}