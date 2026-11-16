<?php

namespace App\Actions\Portal\Users;

use App\Models\User;
use Illuminate\Support\Facades\DB;

class RestoreOrDeleteManagedUserAction
{
    /**
     * کاربر حذف‌شده را بازیابی یا کاربر فعال را soft-delete می‌کند.
     *
     * @return bool True when restored, false when deleted.
     */
    public function handle(User $user): bool
    {
        return DB::transaction(function () use ($user) {
            $lockedUser = User::withTrashed()
                ->whereKey($user->id)
                ->lockForUpdate()
                ->firstOrFail();

            if ($lockedUser->trashed()) {
                $lockedUser->restore();

                return true;
            }

            $lockedUser->delete();

            return false;
        });
    }
}
