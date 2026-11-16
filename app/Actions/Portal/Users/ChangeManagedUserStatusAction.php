<?php

namespace App\Actions\Portal\Users;

use App\Actions\Security\RecordSecurityAuditEventAction;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class ChangeManagedUserStatusAction
{
    public function handle(
        User $user,
        bool $isActive,
        RecordSecurityAuditEventAction $audit
    ): User
    {
        return DB::transaction(function () use ($user, $isActive, $audit) {
            $lockedUser = User::query()
                ->whereKey($user->id)
                ->lockForUpdate()
                ->firstOrFail();

            $lockedUser->forceFill(['is_active' => $isActive])->save();

            $updated = $lockedUser->refresh();

            $audit?->handle('managed_user.status_changed', $updated, [
                'role' => $updated->role,
                'is_active' => (bool) $updated->is_active,
            ]);

            return $updated;
        });
    }
}
