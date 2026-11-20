<?php

declare(strict_types=1);

namespace App\Actions\Portal\Users;

use App\Models\Doctor;
use App\Models\User;
use Illuminate\Support\Facades\DB;

final class SyncOrganizationSecretaryDoctorAccessAction
{
    /**
     * Rebuild the legacy doctor_user grant from every active organization
     * membership, preserving permissions that were assigned outside organizations.
     *
     * The caller may invoke this from a larger transaction. The nested
     * transaction keeps standalone calls atomic as well.
     */
    public function handle(User $member, Doctor $doctor): void
    {
        DB::transaction(function () use ($member, $doctor): void {
            User::query()->whereKey($member->id)->lockForUpdate()->firstOrFail();
            Doctor::query()->whereKey($doctor->id)->lockForUpdate()->firstOrFail();

            $assignments = DB::table('clinic_organization_doctors as organization_doctors')
                ->join(
                    'clinic_organization_members as organization_members',
                    'organization_members.organization_id',
                    '=',
                    'organization_doctors.organization_id'
                )
                ->join('users as organization_users', 'organization_users.id', '=', 'organization_members.user_id')
                ->join('doctors as active_doctor_profiles', 'active_doctor_profiles.id', '=', 'organization_doctors.doctor_id')
                ->join('users as active_doctor_users', 'active_doctor_users.id', '=', 'active_doctor_profiles.user_id')
                ->where('organization_doctors.doctor_id', $doctor->id)
                ->where('organization_members.user_id', $member->id)
                ->where('organization_doctors.status', 'active')
                ->where('organization_members.is_active', true)
                ->where('organization_users.role', 'secretary')
                ->where('organization_users.is_active', true)
                ->whereNull('organization_users.deleted_at')
                ->where('active_doctor_profiles.is_active', true)
                ->whereNull('active_doctor_profiles.deleted_at')
                ->where('active_doctor_users.role', 'doctor')
                ->where('active_doctor_users.is_active', true)
                ->whereNull('active_doctor_users.deleted_at')
                ->get([
                    'organization_doctors.organization_id',
                    'organization_members.permissions',
                    'organization_members.clinic_ids',
                ]);

            $pivot = DB::table('doctor_user')
                ->where('doctor_id', $doctor->id)
                ->where('user_id', $member->id)
                ->lockForUpdate()
                ->first();

            $grantedPermissions = [];
            $grantedClinicIds = [];

            foreach ($assignments as $assignment) {
                $assignedClinicIds = array_map(
                    'intval',
                    $this->decodeArray($assignment->clinic_ids)
                );

                if ($assignedClinicIds === []) {
                    continue;
                }

                $validClinicIds = DB::table('clinic_organization_clinics as organization_clinics')
                    ->join('addresses', 'addresses.id', '=', 'organization_clinics.clinic_id')
                    ->where('organization_clinics.organization_id', $assignment->organization_id)
                    ->where('organization_clinics.status', 'active')
                    ->where('addresses.organization_id', $assignment->organization_id)
                    ->where('addresses.doctor_id', $doctor->id)
                    ->where('addresses.is_active', true)
                    ->whereNull('addresses.deleted_at')
                    ->whereIn('addresses.id', $assignedClinicIds)
                    ->pluck('addresses.id')
                    ->map(fn ($id) => (int) $id)
                    ->all();

                if ($validClinicIds === []) {
                    continue;
                }

                $grantedPermissions = array_merge(
                    $grantedPermissions,
                    $this->decodeArray($assignment->permissions)
                );
                $grantedClinicIds = array_merge($grantedClinicIds, $validClinicIds);
            }

            $grantedPermissions = array_values(array_unique($grantedPermissions));
            $grantedClinicIds = array_values(array_unique($grantedClinicIds));

            $basePermissions = $pivot
                ? $this->decodeArray(
                    $pivot->managed_by_organization
                        ? $pivot->base_permissions
                        : $pivot->permissions
                )
                : [];
            $baseClinicIds = $pivot
                ? array_map(
                    'intval',
                    $this->decodeArray(
                        $pivot->managed_by_organization
                            ? $pivot->base_clinic_ids
                            : $pivot->clinic_ids
                    )
                )
                : [];

            if ($grantedPermissions === [] && $grantedClinicIds === []) {
                if (! $pivot || ! (bool) $pivot->managed_by_organization) {
                    return;
                }

                if ($pivot->base_permissions !== null || $pivot->base_clinic_ids !== null) {
                    DB::table('doctor_user')
                        ->where('doctor_id', $doctor->id)
                        ->where('user_id', $member->id)
                        ->update([
                            'permissions' => $pivot->base_permissions ?? json_encode([]),
                            'clinic_ids' => $pivot->base_clinic_ids ?? json_encode([]),
                            'base_permissions' => null,
                            'base_clinic_ids' => null,
                            'managed_by_organization' => false,
                            'updated_at' => now(),
                        ]);
                } else {
                    DB::table('doctor_user')
                        ->where('doctor_id', $doctor->id)
                        ->where('user_id', $member->id)
                        ->delete();
                }

                return;
            }

            $values = [
                'permissions' => json_encode(
                    array_values(array_unique(array_merge($basePermissions, $grantedPermissions))),
                    JSON_UNESCAPED_UNICODE
                ),
                'clinic_ids' => json_encode(
                    array_values(array_unique(array_merge($baseClinicIds, $grantedClinicIds)))
                ),
                'base_permissions' => $pivot
                    ? ($pivot->managed_by_organization ? $pivot->base_permissions : $pivot->permissions)
                    : null,
                'base_clinic_ids' => $pivot
                    ? ($pivot->managed_by_organization ? $pivot->base_clinic_ids : $pivot->clinic_ids)
                    : null,
                'managed_by_organization' => true,
                'updated_at' => now(),
            ];

            if ($pivot) {
                DB::table('doctor_user')
                    ->where('doctor_id', $doctor->id)
                    ->where('user_id', $member->id)
                    ->update($values);
            } else {
                DB::table('doctor_user')->insert($values + [
                    'doctor_id' => $doctor->id,
                    'user_id' => $member->id,
                    'created_at' => now(),
                ]);
            }
        });
    }

    private function decodeArray(mixed $value): array
    {
        if (is_array($value)) {
            return $value;
        }

        if (! is_string($value) || $value === '') {
            return [];
        }

        $decoded = json_decode($value, true);

        return is_array($decoded) ? $decoded : [];
    }
}
