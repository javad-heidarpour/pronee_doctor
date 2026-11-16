<?php

namespace App\Actions\Portal\Users;

use App\Models\User;

class BuildManagedUserCreatePageAction
{
    public function __construct(
        private readonly ListManagedUserRolesAction $rolesAction
    ) {
    }

    public function handle(User $authUser, $activeDoctor): array
    {
        $roles = array_values(array_filter(
            $this->rolesAction->handle($authUser),
            fn ($role) => $role['value'] !== 'doctor'
        ));

        return [
            'doctor' => $activeDoctor ?? $authUser->doctorProfile,
            'roles' => $roles,
        ];
    }
}
