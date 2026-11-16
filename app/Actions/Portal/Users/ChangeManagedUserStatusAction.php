<?php

namespace App\Actions\Portal\Users;

use App\Models\User;
use Illuminate\Support\Facades\DB;

class ChangeManagedUserStatusAction
{
    public function handle(User $user, bool $isActive): User
    {
        return DB::transaction(function () use ($user, $isActive) {
            $lockedUser = User::query()
                ->whereKey($user->id)
                ->lockForUpdate()
                ->firstOrFail();

            $lockedUser->forceFill(['is_active' => $isActive])->save();

            return $lockedUser->refresh();
        });
    }
}
