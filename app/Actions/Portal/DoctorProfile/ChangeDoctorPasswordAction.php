<?php

namespace App\Actions\Portal\DoctorProfile;

use App\Models\User;
use Illuminate\Support\Facades\Hash;

class ChangeDoctorPasswordAction
{
    /**
     * رمز عبور حساب کاربری واردشده را تغییر می‌دهد.
     *
     * @return bool false اگر رمز فعلی اشتباه باشد.
     */
    public function handle(
        User $user,
        string $currentPassword,
        string $newPassword
    ): bool {
        if (!Hash::check($currentPassword, $user->password)) {
            return false;
        }

        $user->password = $newPassword;
        $user->save();

        return true;
    }
}