<?php

namespace App\Actions\Portal\Users;

use App\Actions\Security\RecordSecurityAuditEventAction;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ChangeManagedUserStatusAction
{
    public function handle(
        User $user,
        bool $isActive,
        RecordSecurityAuditEventAction $audit
    ): User {
        return DB::transaction(function () use ($user, $isActive, $audit) {
            $lockedUser = User::query()
                ->whereKey($user->id)
                ->lockForUpdate()
                ->firstOrFail();

            $lockedUser->forceFill(['is_active' => $isActive]);

            // با غیرفعال‌سازی حساب، کوکی‌های ورود ماندگار نیز بی‌اعتبار می‌شوند.
            if (! $isActive) {
                $lockedUser->setRememberToken(Str::random(60));
            }

            $lockedUser->save();

            // نشست‌های کاربر غیرفعال فوراً از مخزن نشست حذف می‌شوند.
            // میان‌افزار DoctorAuth نیز در هر درخواست وضعیت حساب را دوباره بررسی می‌کند.
            if (! $isActive && config('session.driver') === 'database') {
                DB::connection(config('session.connection'))
                    ->table(config('session.table', 'sessions'))
                    ->where('user_id', $lockedUser->getAuthIdentifier())
                    ->delete();
            }

            $updated = $lockedUser->refresh();

            $audit->handle('managed_user.status_changed', $updated, [
                'role' => $updated->role,
                'is_active' => (bool) $updated->is_active,
            ]);

            return $updated;
        });
    }
}