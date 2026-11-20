<?php

namespace App\Actions\Portal\Users;

use App\Actions\Security\RecordSecurityAuditEventAction;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class RestoreOrDeleteManagedUserAction
{
    /**
     * کاربر حذف‌شده را به‌صورت غیرفعال بازیابی یا کاربر فعال را soft-delete می‌کند.
     *
     * @return bool True when restored, false when deleted.
     */
    public function handle(
        User $user,
        RecordSecurityAuditEventAction $audit
    ): bool {
        return DB::transaction(function () use ($user, $audit) {
            $lockedUser = User::withTrashed()
                ->whereKey($user->id)
                ->lockForUpdate()
                ->firstOrFail();

            if ($lockedUser->trashed()) {
                // بازیابی نباید دسترسی قبلی را بدون بررسی مدیر دوباره برقرار کند.
                $lockedUser->forceFill(['is_active' => false]);
                $lockedUser->restore();
                $lockedUser->save();

                $audit->handle('managed_user.restored', $lockedUser, [
                    'role' => $lockedUser->role,
                    'restored' => true,
                    'is_active' => false,
                ]);

                return true;
            }

            // حذف حساب منشی باید عضویت‌های سازمانی و تخصیص‌های مستقیم او را
            // نیز لغو کند؛ بازیابی بعدی فقط حساب غیرفعال را برمی‌گرداند.
            if ($lockedUser->role === 'secretary') {
                DB::table('clinic_organization_members')
                    ->where('user_id', $lockedUser->id)
                    ->where('is_active', true)
                    ->update([
                        'is_active' => false,
                        'updated_at' => now(),
                    ]);

                $lockedUser->doctors()->detach();
            }

            // حذف حساب باید کوکی‌های remember و نشست‌های قبلی را نیز بی‌اعتبار کند.
            $lockedUser->setRememberToken(Str::random(60));
            $lockedUser->forceFill(['is_active' => false]);
            $lockedUser->save();

            if (config('session.driver') === 'database') {
                DB::connection(config('session.connection'))
                    ->table(config('session.table', 'sessions'))
                    ->where('user_id', $lockedUser->getAuthIdentifier())
                    ->delete();
            }

            $audit->handle('managed_user.deleted', $lockedUser, [
                'role' => $lockedUser->role,
                'restored' => false,
                'is_active' => false,
                'sessions_revoked' => config('session.driver') === 'database',
            ]);

            $lockedUser->delete();

            return false;
        });
    }
}
