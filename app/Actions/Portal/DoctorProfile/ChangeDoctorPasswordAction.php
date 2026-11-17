<?php

namespace App\\Actions\\Portal\\DoctorProfile;

use App\\Models\\User;
use Illuminate\\Support\\Facades\\DB;
use Illuminate\\Support\\Facades\\Hash;
use Illuminate\\Support\\Str;

class ChangeDoctorPasswordAction
{
    /**
     * رمز عبور حساب را تغییر می‌دهد و سایر نشست‌های همان حساب را می‌بندد.
     *
     * @return bool false اگر رمز فعلی اشتباه باشد.
     */
    public function handle(
        User $user,
        string $currentPassword,
        string $newPassword,
        string $currentSessionId
    ): bool {
        if (! Hash::check($currentPassword, $user->password)) {
            return false;
        }

        DB::transaction(function () use ($user, $newPassword, $currentSessionId) {
            $lockedUser = User::query()
                ->whereKey($user->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            $lockedUser->password = $newPassword;

            // نشست‌های مبتنی بر کوکی Remember نیز باطل شوند.
            $lockedUser->setRememberToken(Str::random(60));
            $lockedUser->save();

            // نشست فعلی حفظ می‌شود تا کاربر پس از تغییر رمز از پنل خارج نشود.
            if (config('session.driver') === 'database') {
                DB::connection(config('session.connection'))
                    ->table(config('session.table', 'sessions'))
                    ->where('user_id', $lockedUser->getAuthIdentifier())
                    ->where('id', '<>', $currentSessionId)
                    ->delete();
            }
        });

        return true;
    }
}