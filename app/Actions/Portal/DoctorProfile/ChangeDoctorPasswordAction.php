<?php

namespace App\Actions\Portal\DoctorProfile;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class ChangeDoctorPasswordAction
{
    /**
     * @return bool false اگر رمز فعلی اشتباه باشد.
     */
    public function handle(string $currentPassword, string $newPassword): bool
    {
        $doctor = Auth::guard('web')->user()->doctorProfile;

        if (!Hash::check($currentPassword, $doctor->password)) {
            return false;
        }

        $doctor->update([
            'password' => Hash::make($newPassword),
        ]);

        return true;
    }
}
