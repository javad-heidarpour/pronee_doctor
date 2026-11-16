<?php

namespace App\Actions\Portal\Users;

use App\Models\User;

class BuildManagedUserEditPageAction
{
    /**
     * Build the data required by the managed-user edit page.
     */
    public function handle(User $user): array
    {
        return [
            'user' => $user,
        ];
    }
}
