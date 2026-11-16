<?php

namespace App\Actions\Portal\Users;

use App\Models\User;
use Illuminate\Support\Facades\DB;

class ChangeManagedUserStatusAction
{
    public function handle(\n        User $user,\n        bool $isActive,\n        ?RecordSecurityAuditEventAction $audit = null\n    ): User
    {
        return DB::transaction(function () use ($user, $isActive) {
            $lockedUser = User::query()
                ->whereKey($user->id)
                ->lockForUpdate()
                ->firstOrFail();

            $lockedUser->forceFill(['is_active' => $isActive])->save();

            $updated = $lockedUser->refresh();\n\n            $audit?->handle('managed_user.status_changed', $updated, [\n                'role' => $updated->role,\n                'is_active' => (bool) $updated->is_active,\n            ]);\n\n            return $updated;
        });
    }
}
