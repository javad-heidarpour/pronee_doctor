<?php

namespace App\Actions\Portal\Clinic;

use App\Models\Address;
use App\Models\Doctor;

class StoreClinicAction
{
    public function handle(Doctor $doctor, array $data): Address
    {
        $data['doctor_id'] = $doctor->id;

        return Address::create($data);
    }
}