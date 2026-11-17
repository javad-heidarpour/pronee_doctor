<?php

namespace App\Actions\Portal\Users;

use App\Actions\Security\RecordSecurityAuditEventAction;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class UpdateManagedUserAction
{
    /**
     * به‌روزرسانی حساب کاربر با حفظ یکپارچگی ارتباط‌های منشی.
     */
    public function handle(
        User $user,
        array $validated,
        RecordSecurityAuditEventAction $audit
    ): User
    {
        return DB::transaction(function () use ($user, $validated, $audit) {
            $lockedUser = User::withTrashed()
                ->whereKey($user->id)
                ->lockForUpdate()
                ->firstOrFail();

            $newRole = $validated['role'] ?? $lockedUser->role;

            if ($lockedUser->role === 'secretary' && $newRole !== 'secretary') {
                $lockedUser->doctors()->detach();
            }

            // مدل User فیلد password را با cast از نوع hashed ذخیره می‌کند.
            // مقدار خام رمز عبور را دریافت می‌کنیم تا دوباره hash نشود.
            $passwordChanged = filled($validated['password'] ?? null);

            if (! $passwordChanged) {
                unset($validated['password']);
            }

            $lockedUser->fill($validated);

            if ($passwordChanged) {
                // تغییر رمز توسط مدیر، نشست‌ها و کوکی‌های ماندگار قبلی را باطل می‌کند.
                $lockedUser->setRememberToken(Str::random(60));
            }

            $lockedUser->save();

            if ($passwordChanged && config('session.driver') === 'database') {
                DB::connection(config('session.connection'))
                    ->table(config('session.table', 'sessions'))
                    ->where('user_id', $lockedUser->getAuthIdentifier())
                    ->delete();
            }

            $updated = $lockedUser->refresh();

            $audit->handle('managed_user.updated', $updated, [
                'role' => $updated->role,
                'is_active' => (bool) $updated->is_active,
            ]);

            return $updated;
        });
    }
}
