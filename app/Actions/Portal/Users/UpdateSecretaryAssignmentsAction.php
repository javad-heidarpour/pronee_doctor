<?php

namespace App\Actions\Portal\Users;

use App\Actions\Security\RecordSecurityAuditEventAction;
use App\Models\Doctor;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class UpdateSecretaryAssignmentsAction
{
    /**
     * ذخیره تخصیص پزشکان و مطب‌های منشی به‌صورت اتمیک.
     *
     * در ویرایش توسط پزشک، تخصیص‌های سایر پزشکان دست‌نخورده می‌مانند
     * و سقف دو مطب برای کل حساب منشی محاسبه می‌شود.
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
        ) {
            $lockedSecretary = User::query()
                ->whereKey($secretary->id)
                ->lockForUpdate()
                ->firstOrFail();

            if ($actorRole === 'admin') {
                $totalClinicAssignments = collect($syncData)
                    ->sum(function ($pivot) {
                        $clinicIds = json_decode($pivot['clinic_ids'] ?? '[]', true);
                        return is_array($clinicIds) ? count($clinicIds) : 0;
                    });

                if ($totalClinicAssignments > 2) {
                    throw ValidationException::withMessages([
                        'doctors' => 'هر منشی حداکثر می‌تواند به دو مطب متصل باشد.',
                    ]);
                }

                $lockedSecretary->doctors()->sync($syncData);
                $this->revokeSecretarySessions($lockedSecretary);
                $audit->handle('secretary.assignments_updated', $lockedSecretary, [
                    'clinic_count' => collect($syncData)->sum(function ($pivot) {
                        $ids = json_decode($pivot['clinic_ids'] ?? '[]', true);
                        return is_array($ids) ? count($ids) : 0;
                    }),
                    'permission_count' => collect($syncData)->sum(function ($pivot) {
                        $permissions = json_decode($pivot['permissions'] ?? '[]', true);
                        return is_array($permissions) ? count($permissions) : 0;
                    }),
                ]);
                return;
            }

            if ($actorRole !== 'doctor' || !$activeDoctor) {
                abort(403, 'نقش کاربر برای ویرایش تخصیص‌ها مجاز نیست.');
            }

            // مطب‌های اختصاص‌یافته به سایر پزشکان را نیز در سقف کل حساب لحاظ کن.
            $otherClinicCount = $lockedSecretary->doctors()
                ->where('doctors.id', '!=', $activeDoctor->id)
                ->get()
                ->sum(function ($doctor) {
                    $clinicIds = $doctor->pivot?->clinic_ids ?? [];

                    if (is_string($clinicIds)) {
                        $clinicIds = json_decode($clinicIds, true) ?: [];
                    }

                    return is_array($clinicIds) ? count(array_unique($clinicIds)) : 0;
                });

            $currentDoctorClinicCount = 0;

            if (in_array($activeDoctor->id, $submittedDoctorIds, true)) {
                $pivot = $syncData[$activeDoctor->id] ?? null;
                $clinicIds = $pivot
                    ? json_decode($pivot['clinic_ids'] ?? '[]', true)
                    : [];

                $currentDoctorClinicCount = is_array($clinicIds)
                    ? count(array_unique($clinicIds))
                    : 0;
            }

            if ($otherClinicCount + $currentDoctorClinicCount > 2) {
                throw ValidationException::withMessages([
                    'doctors' => 'این منشی در مطب‌های دیگری نیز فعال است؛ مجموع مطب‌های تخصیص‌یافته به هر منشی حداکثر دو مورد است.',
                ]);
            }

            // پزشک فقط می‌تواند ارتباط خودش را اضافه، اصلاح یا حذف کند.
            $lockedSecretary->doctors()->syncWithoutDetaching($syncData);

            if (!in_array($activeDoctor->id, $submittedDoctorIds, true)) {
                $lockedSecretary->doctors()->detach($activeDoctor->id);
            }

            $this->revokeSecretarySessions($lockedSecretary);

            $audit->handle('secretary.assignments_updated', $lockedSecretary, [
                'doctor_id' => $activeDoctor->id,
                'clinic_count' => $currentDoctorClinicCount,
                'permission_count' => count(json_decode(
                    $syncData[$activeDoctor->id]['permissions'] ?? '[]',
                    true
                ) ?: []),
            ]);
        });
    }
    /**
     * دسترسی‌های منشی تغییر کرده‌اند؛ کوکی‌های ماندگار و نشست‌های دیتابیسی
     * قبلی را باطل می‌کنیم تا نشست قدیمی به تغییرات دسترسی متکی نماند.
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
