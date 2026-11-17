<?php

declare(strict_types=1);

namespace App\Actions\Auth;

use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

final class RegisterPublicUserAction
{
    public function handle(array $data): User
    {
        $user = DB::transaction(function () use ($data): User {
            return User::query()->create([
                'name' => $data['name'],
                'email' => mb_strtolower($data['email']),
                'password' => $data['password'],
                // Public self-registration receives no portal privileges.
                'role' => 'patient',
                'is_active' => true,
            ]);
        });

        event(new Registered($user));
        Auth::guard('web')->login($user);

        return $user;
    }
}
