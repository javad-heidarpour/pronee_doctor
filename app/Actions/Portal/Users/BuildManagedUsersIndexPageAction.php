<?php

namespace App\Actions\Portal\Users;

use App\Models\User;

class BuildManagedUsersIndexPageAction
{
    public function __construct(
        private readonly ListManagedUserRolesAction $rolesAction
    ) {
    }

    public function handle(User $authUser, $activeDoctor): array
    {
        $usersQuery = User::withTrashed()->latest();

        if ($authUser->role === 'doctor') {
            $usersQuery->where('role', 'secretary')
                ->whereHas('doctors', function ($query) use ($activeDoctor) {
                    $query->where('doctors.id', $activeDoctor->id);
                });
        }

        return [
            'doctor' => $activeDoctor ?? $authUser->doctorProfile,
            'roles' => $this->rolesAction->handle($authUser),
            'users' => $usersQuery->get(),
        ];
    }
}
