<?php

namespace App\Actions\Portal\Users;

use App\Models\Doctor;
use App\Models\User;

class BuildManagedUserAssignmentsPageAction
{
    public function __construct(
        private readonly ListManagedUserPermissionsAction $permissionsAction
    ) {
    }

    public function handle(User $user, User $authUser, ?Doctor $activeDoctor): array
    {
        $doctorsQuery = Doctor::query()
            ->with(['user', 'address' => fn ($query) => $query->where('is_active', true)])
            ->where('doctors.is_active', true)
            ->whereHas('user', function ($query) {
                $query->where('role', 'doctor')
                    ->where('is_active', true);
            });

        if ($authUser->role === 'doctor') {
            $doctorsQuery->whereKey($activeDoctor->id);
        }

        $doctors = $doctorsQuery->orderBy('name')->get();
        $assigned = $user->doctors()->get()->keyBy('id');

        return [
            'doctor' => $activeDoctor ?? $authUser->doctorProfile,
            'user' => $user->only(['id', 'name', 'family', 'phone', 'role']),
            'doctors' => $doctors->map(function ($doctor) use ($assigned) {
                $relation = $assigned->get($doctor->id);
                $managedByOrganization = (bool) ($relation?->pivot?->managed_by_organization ?? false);
                $permissions = $managedByOrganization
                    ? $relation?->pivot?->base_permissions
                    : $relation?->pivot?->permissions;
                $clinicIds = $managedByOrganization
                    ? $relation?->pivot?->base_clinic_ids
                    : $relation?->pivot?->clinic_ids;
                $hasDirectAssignment = $relation !== null && (
                    ! $managedByOrganization
                    || $relation->pivot->base_permissions !== null
                    || $relation->pivot->base_clinic_ids !== null
                );

                if (is_string($permissions)) {
                    $permissions = json_decode($permissions, true) ?: [];
                }

                if (is_string($clinicIds)) {
                    $clinicIds = json_decode($clinicIds, true) ?: [];
                }

                return [
                    'id' => $doctor->id,
                    'name' => $doctor->name ?: $doctor->user?->name,
                    'family' => $doctor->family ?: $doctor->user?->family,
                    'phone' => $doctor->phone ?: $doctor->user?->phone,
                    'specialty_title' => $doctor->specialty_title,
                    'assigned' => $hasDirectAssignment,
                    'permissions' => $permissions,
                    'clinics' => $doctor->address->map(fn ($clinic) => [
                        'id' => $clinic->id,
                        'name' => $clinic->name,
                        'is_active' => (bool) $clinic->is_active,
                    ])->values(),
                    'clinic_ids' => array_map(
                        'intval',
                        is_array($clinicIds) ? $clinicIds : []
                    ),
                ];
            })->values(),
            'permissions' => $this->permissionsAction->handle(),
        ];
    }
}
