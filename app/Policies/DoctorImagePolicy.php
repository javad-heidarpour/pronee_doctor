<?php

namespace App\Policies;

use App\Models\Doctor;
use App\Models\DoctorImage;

class DoctorImagePolicy
{
    public function update(Doctor $doctor, DoctorImage $image): bool
    {
        return (int) $image->doctor_id === (int) $doctor->id;
    }

    public function delete(Doctor $doctor, DoctorImage $image): bool
    {
        return (int) $image->doctor_id === (int) $doctor->id;
    }
}
