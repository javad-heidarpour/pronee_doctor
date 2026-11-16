<?php

namespace App\Actions\Portal\Users;

use App\Models\User;

class ListManagedUserRolesAction
{
    public function handle(?User $user): array
    {
        $roles = [
            ['value' => 'doctor', 'label' => 'پزشک', 'icon' => '⚕️', 'description' => 'دسترسی به پنل پزشک'],
            ['value' => 'secretary', 'label' => 'منشی', 'icon' => '🗂️', 'description' => 'مدیریت نوبت‌ها و مراجعان'],
            ['value' => 'admin', 'label' => 'مدیر', 'icon' => '🛡️', 'description' => 'مدیریت سیستم'],
            ['value' => 'user', 'label' => 'کاربر عادی', 'icon' => '👤', 'description' => 'دسترسی عمومی'],
        ];

        return $user?->role === 'admin'
            ? $roles
            : array_values(array_filter(
                $roles,
                fn (array $role) => $role['value'] === 'secretary'
            ));
    }
}
