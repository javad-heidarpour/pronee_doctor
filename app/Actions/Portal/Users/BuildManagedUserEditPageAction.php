<?php

namespace App\Actions\Portal\Users;

use App\Models\User;

class BuildManagedUserEditPageAction
{
    public function __construct(
        private readonly ListManagedUserRolesAction $rolesAction
    ) {
    }

    public function handle(User $user, User $authUser): array
    {
        return [
            'user' => $user,
            'roles' => $this->rolesAction->handle($authUser),
        ];
    }
}
