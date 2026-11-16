<?php

namespace App\Actions\Portal\Clinic;

use App\Models\City;
use App\Models\Doctor;

class ListClinicsAction
{
    public function handle(Doctor $doctor): array
    {
        return [
            'clinics' => $doctor->address()->with('city')->get(),
            'doctor' => $doctor,
            'cities' => City::all(),
        ];
    }
}