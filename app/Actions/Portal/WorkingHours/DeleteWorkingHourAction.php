<?php

namespace App\Actions\Portal\WorkingHours;

use App\Models\DoctorWorkingHour;

class DeleteWorkingHourAction
{
    public function handle(DoctorWorkingHour $workingHour): void
    {
        $workingHour->delete();
    }
}