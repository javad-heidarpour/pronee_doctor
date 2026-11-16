<?php

namespace App\Actions\Portal\Users;

use App\Models\User;
use Illuminate\Support\Facades\DB;

class UpdateManagedUserAction
{
    /**
     * به‌روزرسانی حساب کاربر با حفظ یکپارچگی ارتباط‌های منشی.
     */
    public function handle(\n        User $user,\n        array $validated,\n        ?RecordSecurityAuditEventAction $audit = null\n    ): User
    {
        return DB::transaction(function () use ($user, $validated) {
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

            $updated = $lockedUser->refresh();\n\n            $audit?->handle('managed_user.updated', $updated, [\n                'role' => $updated->role,\n                'is_active' => (bool) $updated->is_active,\n            ]);\n\n            return $updated;
        });
    }
}
