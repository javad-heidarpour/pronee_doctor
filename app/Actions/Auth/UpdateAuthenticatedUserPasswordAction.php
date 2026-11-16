<?php

namespace App\Actions\Auth;

use App\Models\User;
use Illuminate\Support\Facades\Hash;

class UpdateAuthenticatedUserPasswordAction
{
    public function handle(User $user, string $newPassword): void
    {
        $user->forceFill([
            'password' => Hash::make($newPassword),
        ])->save();
    }
}
