<?php

namespace App\Actions\Portal\Users;

use App\Models\User;
use App\Models\Doctor;

class BuildManagedUserEditPageAction
{
    public function __construct(
        private readonly ListManagedUserRolesAction $rolesAction
    ) {
    }

    public function handle(User $user, User $authUser, ?Doctor $activeDoctor): array
    {
        return [
            'user' => $user,
            'doctor' => $activeDoctor ?? $authUser->doctorProfile,
            'roles' => $this->rolesAction->handle($authUser),
        ];
    }
}
