<?php

namespace App\Actions\Portal\Users;

use App\Actions\Security\RecordSecurityAuditEventAction;
use App\Models\User;
use Illuminate\Support\Facades\DB;

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
            if (empty($validated['password'])) {
                unset($validated['password']);
            }

            $lockedUser->fill($validated);
            $lockedUser->save();

            $updated = $lockedUser->refresh();

            $audit?->handle('managed_user.updated', $updated, [
                'role' => $updated->role,
                'is_active' => (bool) $updated->is_active,
            ]);

            return $updated;
        });
    }
}
