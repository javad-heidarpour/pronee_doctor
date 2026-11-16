<?php

namespace App\Actions\Auth;

use App\Models\City;
use App\Models\Specialty;

class BuildDoctorRegistrationFormAction
{
    public function handle(): array
    {
        return [
            'specialties' => Specialty::query()
                ->where('is_active', 1)
                ->orderBy('sort_order')
                ->get(['id', 'name']),
            'cities' => City::query()
                ->where('is_active', 1)
                ->orderBy('name')
                ->get(['id', 'name', 'province']),
        ];
    }
}
