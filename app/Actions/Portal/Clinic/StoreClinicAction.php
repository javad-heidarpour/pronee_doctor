<?php

namespace App\Actions\Portal\Clinic;

use App\Models\Address;
use Illuminate\Support\Facades\Auth;

class StoreClinicAction
{
    public function handle(array $data): Address
    {
        $doctor = Auth::guard('web')->user()->doctorProfile;

        $data['doctor_id'] = $doctor->id;

        return Address::create($data);
    }
}
