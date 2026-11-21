<?php

declare(strict_types=1);

namespace App\Actions\Auth;

use App\DTOs\Auth\RegisterPublicUserData;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

final class RegisterPublicUserAction
{
    public function handle(RegisterPublicUserData $data): User
    {
        $user = DB::transaction(function () use ($data): User {
            $usernameBase = Str::slug(Str::before($data->email, '@'));
            $username = substr($usernameBase !== '' ? $usernameBase : 'user', 0, 60)
                . '-' . Str::lower(Str::random(12));

            return User::query()->create([
                'name' => $data->name,
                'family' => $data->family,
                'username' => $username,
                'email' => $data->email,
                'password' => $data->password,
                // Public self-registration receives no portal privileges.
                'role' => 'user',
                'is_active' => true,
            ]);
        });

        event(new Registered($user));
        Auth::guard('web')->login($user);
        // Rotate the anonymous session after privilege elevation to prevent session fixation.
        request()->session()->regenerate();

        return $user;
    }
}
