<?php

namespace App\Actions\Portal\Clinic;

use App\Models\City;
use App\Models\Doctor;

class ListClinicsAction
{
    public function handle(Doctor $doctor, ?array $allowedClinicIds = null): array
    {
        return [
            'clinics' => $doctor->address()->when($allowedClinicIds !== null, fn ($q) => $q->whereIn('addresses.id', $allowedClinicIds))->with('city')->get(),
            'doctor' => $doctor,
            'cities' => City::all(),
        ];
    }
}