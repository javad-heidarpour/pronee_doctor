<?php

namespace App\Actions\Portal\WorkingHours;

use App\Models\DoctorWorkingHour;

class BuildWorkingHourConflictMessageAction
{
    public function handle(DoctorWorkingHour $duplicate): string
    {
        return __('working_hours.duplicate', [
            'day' => __('working_hours.days.' . $duplicate->day_of_week),
            'clinic' => $duplicate->clinic?->name
                ?? __('working_hours.unknown_clinic'),
        ]);
    }
}
