<?php

namespace App\Actions\Portal\Users;

use App\Actions\Security\RecordSecurityAuditEventAction;
use App\Models\User;
use App\Models\Doctor;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class ChangeManagedUserStatusAction
{
    public function handle(
        User $user,
        bool $isActive,
        RecordSecurityAuditEventAction $audit
    ): User {
        return DB::transaction(function () use ($user, $isActive, $audit) {
            $lockedUser = User::query()
                ->whereKey($user->id)
                ->lockForUpdate()
                ->firstOrFail();

            $lockedUser->forceFill(['is_active' => $isActive]);

            // با غیرفعال‌سازی حساب، کوکی‌های ورود ماندگار نیز بی‌اعتبار می‌شوند.
            if (! $isActive) {
                $lockedUser->setRememberToken(Str::random(60));
            }

            $lockedUser->save();

            // حساب پزشک و پروفایل حرفه‌ای باید همیشه یک وضعیت دسترسی داشته باشند.
            // فعال‌سازی پزشک از این مسیر فقط در اختیار مدیر سامانه است.
            if ($lockedUser->role === 'doctor') {
                $doctorProfile = Doctor::query()
                    ->where('user_id', $lockedUser->id)
                    ->lockForUpdate()
                    ->first();

                if ($isActive && !$doctorProfile) {
                    throw ValidationException::withMessages([
                        'is_active' => 'برای فعال‌سازی حساب پزشک، پرونده حرفه‌ای معتبر لازم است.',
                    ]);
                }

                if ($doctorProfile) {
                    $doctorProfile->is_active = $isActive;

                    if ($isActive) {
                        // فعال‌سازی مدیر به معنی تأیید صلاحیت حرفه‌ای ثبت‌شده است.
                        $doctorProfile->registration_status = 'approved';
                        $doctorProfile->approved_at ??= now();
                        $doctorProfile->approved_by = Auth::guard('web')->id();
                        $doctorProfile->rejection_reason = null;
                        $doctorProfile->trial_started_at ??= now();
                        $doctorProfile->site_expires_at ??= now()->addDays(7);
                    }

                    $doctorProfile->save();
                }
            }

            // نشست‌های کاربر غیرفعال فوراً از مخزن نشست حذف می‌شوند.
            // میان‌افزار DoctorAuth نیز در هر درخواست وضعیت حساب را دوباره بررسی می‌کند.
            if (! $isActive && config('session.driver') === 'database') {
                DB::connection(config('session.connection'))
                    ->table(config('session.table', 'sessions'))
                    ->where('user_id', $lockedUser->getAuthIdentifier())
                    ->delete();
            }

            $updated = $lockedUser->refresh();

            $audit->handle('managed_user.status_changed', $updated, [
                'role' => $updated->role,
                'is_active' => (bool) $updated->is_active,
            ]);

            return $updated;
        });
    }
}