<?php

namespace App\\Actions\\Profile;

use App\\Models\\Doctor;
use Illuminate\\Support\\Facades\\Hash;
use Illuminate\\Support\\Facades\\Log;
use RuntimeException;

class ChangeProfilePasswordAction
{
    /**
     * رمز عبور پروفایل پزشک را تغییر می‌دهد.
     *
     * @return bool false when the current password is invalid.
     */
    public function handle(
        Doctor $doctor,
        string $currentPassword,
        string $newPassword
    ): bool {
        try {
            $passwordIsValid = Hash::check(
                $currentPassword,
                $doctor->getAuthPassword()
            );
        } catch (RuntimeException $exception) {
            Log::warning(
                'Stored doctor password hash is incompatible with the configured hasher.',
                ['doctor_id' => $doctor->getAuthIdentifier()]
            );

            throw new RuntimeException(
                'Stored password hash cannot be verified.',
                previous: $exception
            );
        }

        if (! $passwordIsValid) {
            return false;
        }

        $doctor->forceFill([
            'password' => Hash::make($newPassword),
        ])->save();

        return true;
    }
}
