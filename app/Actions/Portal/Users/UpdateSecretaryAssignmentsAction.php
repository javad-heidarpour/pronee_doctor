<?php

declare(strict_types=1);

namespace App\Actions\Portal\Users;

use App\Actions\Security\RecordSecurityAuditEventAction;
use App\Models\Doctor;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

final class UpdateSecretaryAssignmentsAction
{
    public function __construct(
        private readonly SyncOrganizationSecretaryDoctorAccessAction $organizationAccess
    ) {
    }

    /**
     * Update direct doctor-to-secretary assignments without overwriting
     * organization-managed grants stored on the same legacy pivot.
     */
    public function handle(
        User $secretary,
        array $syncData,
        string $actorRole,
        ?Doctor $activeDoctor,
        array $submittedDoctorIds,
        RecordSecurityAuditEventAction $audit
    ): void {
        DB::transaction(function () use (
            $secretary,
            $syncData,
            $actorRole,
            $activeDoctor,
            $submittedDoctorIds,
            $audit
        ): void {
            $lockedSecretary = User::query()
                ->whereKey($secretary->id)
                ->lockForUpdate()
                ->firstOrFail();

            $clinicIdsBefore = $this->assignedClinicIds($lockedSecretary->id);

            if ($actorRole === 'admin') {
                $existingDoctorIds = DB::table('doctor_user')
                    ->where('user_id', $lockedSecretary->id)
                    ->pluck('doctor_id')
                    ->map(fn ($id) => (int) $id)
                    ->all();

                $organizationDoctorIds = DB::table('clinic_organization_doctors as organization_doctors')
                    ->join(
                        'clinic_organization_members as organization_members',
                        'organization_members.organization_id',
                        '=',
                        'organization_doctors.organization_id'
                    )
                    ->where('organization_members.user_id', $lockedSecretary->id)
                    ->where('organization_members.is_active', true)
                    ->where('organization_doctors.status', 'active')
                    ->pluck('organization_doctors.doctor_id')
                    ->map(fn ($id) => (int) $id)
                    ->all();

                $submittedIds = collect(array_keys($syncData))
                    ->map(fn ($id) => (int) $id)
                    ->all();

                foreach ($syncData as $doctorId => $pivot) {
                    $this->saveDirectAssignment(
                        $lockedSecretary,
                        (int) $doctorId,
                        $pivot
                    );
                }

                foreach (array_diff($existingDoctorIds, $submittedIds) as $doctorId) {
                    $this->clearDirectAssignment($lockedSecretary, (int) $doctorId);
                }

                $doctorIdsToReconcile = array_values(array_unique(array_merge(
                    $existingDoctorIds,
                    $organizationDoctorIds,
                    $submittedIds
                )));

                $this->reconcileDoctors($lockedSecretary, $doctorIdsToReconcile);
                $this->assertGlobalClinicLimit($lockedSecretary->id, $clinicIdsBefore);

                $this->revokeSecretarySessions($lockedSecretary);
                $audit->handle('secretary.assignments_updated', $lockedSecretary, [
                    'clinic_count' => count($this->submittedClinicIds($syncData)),
                    'permission_count' => $this->submittedPermissionCount($syncData),
                ]);

                return;
            }

            if ($actorRole !== 'doctor' || ! $activeDoctor) {
                abort(403, 'نقش کاربر برای ویرایش تخصیص‌ها مجاز نیست.');
            }

            // Count effective clinic IDs across every other doctor, not just
            // the currently edited doctor.
            $otherClinicIds = $this->assignedClinicIds(
                $lockedSecretary->id,
                $activeDoctor->id
            );

            $currentClinicIds = in_array($activeDoctor->id, $submittedDoctorIds, true)
                ? array_map(
                    'intval',
                    $this->decodeArray($syncData[$activeDoctor->id]['clinic_ids'] ?? '[]')
                )
                : [];

            if (count(array_unique(array_merge($otherClinicIds, $currentClinicIds))) > 2) {
                throw ValidationException::withMessages([
                    'doctors' => 'این منشی در مطب‌های دیگری نیز فعال است؛ مجموع مطب‌های تخصیص‌یافته به هر منشی حداکثر دو مورد است.',
                ]);
            }

            if (in_array($activeDoctor->id, $submittedDoctorIds, true)) {
                $this->saveDirectAssignment(
                    $lockedSecretary,
                    $activeDoctor->id,
                    $syncData[$activeDoctor->id]
                );
            } else {
                $this->clearDirectAssignment($lockedSecretary, $activeDoctor->id);
            }

            // Rebuild the effective pivot so organization grants remain
            // available even when the direct doctor assignment is removed.
            $this->organizationAccess->handle($lockedSecretary, $activeDoctor);
            $this->assertGlobalClinicLimit($lockedSecretary->id, $clinicIdsBefore);

            $this->revokeSecretarySessions($lockedSecretary);
            $audit->handle('secretary.assignments_updated', $lockedSecretary, [
                'doctor_id' => $activeDoctor->id,
                'clinic_count' => count($currentClinicIds),
                'permission_count' => count($this->decodeArray(
                    $syncData[$activeDoctor->id]['permissions'] ?? '[]'
                )),
            ]);
        });
    }

    private function saveDirectAssignment(User $secretary, int $doctorId, array $pivot): void
    {
        $existing = DB::table('doctor_user')
            ->where('doctor_id', $doctorId)
            ->where('user_id', $secretary->id)
            ->lockForUpdate()
            ->first();

        $permissions = $pivot['permissions'] ?? json_encode([]);
        $clinicIds = $pivot['clinic_ids'] ?? json_encode([]);

        if ($existing && (bool) $existing->managed_by_organization) {
            DB::table('doctor_user')
                ->where('doctor_id', $doctorId)
                ->where('user_id', $secretary->id)
                ->update([
                    'base_permissions' => $permissions,
                    'base_clinic_ids' => $clinicIds,
                    'updated_at' => now(),
                ]);

            return;
        }

        if ($existing) {
            DB::table('doctor_user')
                ->where('doctor_id', $doctorId)
                ->where('user_id', $secretary->id)
                ->update([
                    'permissions' => $permissions,
                    'clinic_ids' => $clinicIds,
                    'updated_at' => now(),
                ]);

            return;
        }

        DB::table('doctor_user')->insert([
            'doctor_id' => $doctorId,
            'user_id' => $secretary->id,
            'permissions' => $permissions,
            'clinic_ids' => $clinicIds,
            'managed_by_organization' => false,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function clearDirectAssignment(User $secretary, int $doctorId): void
    {
        $existing = DB::table('doctor_user')
            ->where('doctor_id', $doctorId)
            ->where('user_id', $secretary->id)
            ->lockForUpdate()
            ->first();

        if (! $existing) {
            return;
        }

        if ((bool) $existing->managed_by_organization) {
            DB::table('doctor_user')
                ->where('doctor_id', $doctorId)
                ->where('user_id', $secretary->id)
                ->update([
                    'base_permissions' => null,
                    'base_clinic_ids' => null,
                    'updated_at' => now(),
                ]);

            return;
        }

        DB::table('doctor_user')
            ->where('doctor_id', $doctorId)
            ->where('user_id', $secretary->id)
            ->delete();
    }

    private function reconcileDoctors(User $secretary, array $doctorIds): void
    {
        foreach (array_unique(array_map('intval', $doctorIds)) as $doctorId) {
            $doctor = Doctor::query()->find($doctorId);

            if ($doctor) {
                $this->organizationAccess->handle($secretary, $doctor);
                continue;
            }

            DB::table('doctor_user')
                ->where('doctor_id', $doctorId)
                ->where('user_id', $secretary->id)
                ->delete();
        }
    }

    private function assertGlobalClinicLimit(int $userId, array $clinicIdsBefore): void
    {
        $clinicIdsAfter = $this->assignedClinicIds($userId);

        if (count($clinicIdsAfter) > 2 && count($clinicIdsAfter) > count($clinicIdsBefore)) {
            throw ValidationException::withMessages([
                'doctors' => 'هر منشی در مجموع سازمان‌ها و پزشکان حداکثر می‌تواند به دو مطب فعال دسترسی داشته باشد.',
            ]);
        }
    }

    private function assignedClinicIds(int $userId, ?int $excludeDoctorId = null): array
    {
        $clinicIds = DB::table('doctor_user')
            ->where('user_id', $userId)
            ->when(
                $excludeDoctorId !== null,
                fn ($query) => $query->where('doctor_id', '<>', $excludeDoctorId)
            )
            ->pluck('clinic_ids')
            ->flatMap(fn ($value) => array_map('intval', $this->decodeArray($value)))
            ->unique()
            ->values()
            ->all();

        if ($clinicIds === []) {
            return [];
        }

        return DB::table('addresses')
            ->whereIn('id', $clinicIds)
            ->where('is_active', true)
            ->whereNull('deleted_at')
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->all();
    }

    private function submittedClinicIds(array $syncData): array
    {
        return collect($syncData)
            ->flatMap(fn ($pivot) => array_map(
                'intval',
                $this->decodeArray($pivot['clinic_ids'] ?? '[]')
            ))
            ->unique()
            ->values()
            ->all();
    }

    private function submittedPermissionCount(array $syncData): int
    {
        return collect($syncData)
            ->sum(fn ($pivot) => count($this->decodeArray($pivot['permissions'] ?? '[]')));
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

    /**
     * Invalidate persistent cookies and database sessions after access changes.
     */
    private function revokeSecretarySessions(User $secretary): void
    {
        $secretary->forceFill([
            'remember_token' => Str::random(60),
        ])->save();

        if (config('session.driver') === 'database') {
            DB::table(config('session.table', 'sessions'))
                ->where('user_id', $secretary->id)
                ->delete();
        }
    }
}
