<?php

namespace App\\Actions\\Portal\\DoctorProfile;

use App\\Models\\Doctor;

class ShowDoctorProfileAction
{
    public function handle(Doctor $doctor): Doctor
    {
        return $doctor->load(['specialty', 'city']);
    }
}