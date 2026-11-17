<?php

namespace App\Actions\Auth;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class UpdateAuthenticatedUserPasswordAction
{
    /**
     * تغییر رمز حساب و خاتمه نشست‌های دیگر، با حفظ نشست فعلی.
     */
    public function handle(
        User $user,
        string $newPassword,
        string $currentSessionId
    ): void {
        DB::transaction(function () use ($user, $newPassword, $currentSessionId) {
            $lockedUser = User::query()
                ->whereKey($user->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            // مدل User از cast نوع hashed استفاده می‌کند؛ مقدار خام را می‌پذیرد.
            $lockedUser->password = $newPassword;
            $lockedUser->setRememberToken(Str::random(60));
            $lockedUser->save();

            if (config('session.driver') === 'database') {
                DB::connection(config('session.connection'))
                    ->table(config('session.table', 'sessions'))
                    ->where('user_id', $lockedUser->getAuthIdentifier())
                    ->where('id', '<>', $currentSessionId)
                    ->delete();
            }
        });
    }
}
