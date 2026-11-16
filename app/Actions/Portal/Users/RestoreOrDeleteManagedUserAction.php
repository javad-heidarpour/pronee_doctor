<?php

namespace App\Actions\Portal\Users;

use App\Actions\Security\RecordSecurityAuditEventAction;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class RestoreOrDeleteManagedUserAction
{
    /**
     * کاربر حذف‌شده را بازیابی یا کاربر فعال را soft-delete می‌کند.
     *
     * @return bool True when restored, false when deleted.
     */
    public function handle(
        User $user,
        RecordSecurityAuditEventAction $audit
    ): bool
    {
        return DB::transaction(function () use ($user, $audit) {
            $lockedUser = User::withTrashed()
                ->whereKey($user->id)
                ->lockForUpdate()
                ->firstOrFail();

            if ($lockedUser->trashed()) {
                $lockedUser->restore();
                $audit->handle('managed_user.restored', $lockedUser, [
                    'role' => $lockedUser->role,
                    'restored' => true,
                ]);

                return true;
            }

            $audit->handle('managed_user.deleted', $lockedUser, [
                'role' => $lockedUser->role,
                'restored' => false,
            ]);
            $lockedUser->delete();

            return false;
        });
    }
}
