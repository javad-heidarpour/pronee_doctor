<?php

namespace App\Actions\Portal\Clinic;

use App\Models\City;
use Illuminate\Support\Facades\Auth;

class ListClinicsAction
{
    public function handle(): array
    {
        $doctor = Auth::guard('web')->user()->doctorProfile;

        return [
            'clinics' => $doctor->address()->with('city')->get(),
            'doctor' => $doctor,
            'cities' => City::all(),
        ];
    }
}
