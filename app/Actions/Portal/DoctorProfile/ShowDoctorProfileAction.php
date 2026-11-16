<?php

namespace App\Actions\Portal\DoctorProfile;

use Illuminate\Support\Facades\Auth;

class ShowDoctorProfileAction
{
    public function handle()
    {
        return Auth::guard('doctor')->user()->load(['specialty', 'city']);
    }
}
