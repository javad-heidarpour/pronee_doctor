<?php

namespace App\Actions\Portal\Users;

use App\Actions\Security\RecordSecurityAuditEventAction;
use App\Models\User;
use App\Models\Doctor;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class UpdateManagedUserAction
{
    /**
     * به‌روزرسانی حساب کاربر با حفظ یکپارچگی ارتباط‌های منشی.
     */
    public function handle(
        User $user,
        array $validated,
        RecordSecurityAuditEventAction $audit
    ): User {
        return DB::transaction(function () use ($user, $validated, $audit) {
            $lockedUser = User::withTrashed()
                ->whereKey($user->id)
                ->lockForUpdate()
                ->firstOrFail();

            $oldRole = $lockedUser->role;
            $oldIsActive = (bool) $lockedUser->is_active;
            $newRole = $validated['role'] ?? $oldRole;
            $newIsActive = array_key_exists('is_active', $validated)
                ? (bool) $validated['is_active']
                : $oldIsActive;

            if ($oldRole === 'secretary' && $newRole !== 'secretary') {
                // Revoke both direct doctor assignments and organization
                // memberships. Leaving an active organization membership behind
                // could recreate doctor_user grants during a later organization sync.
                DB::table('clinic_organization_members')
                    ->where('user_id', $lockedUser->id)
                    ->where('is_active', true)
                    ->update([
                        'is_active' => false,
                        'updated_at' => now(),
                    ]);

                $lockedUser->doctors()->detach();
            }

            // مدل User فیلد password را با cast از نوع hashed ذخیره می‌کند.
            // مقدار خام رمز عبور را دریافت می‌کنیم تا دوباره hash نشود.
            $passwordChanged = filled($validated['password'] ?? null);

            if (! $passwordChanged) {
                unset($validated['password']);
            }

            $roleChanged = $oldRole !== $newRole;
            $statusChanged = $oldIsActive !== $newIsActive;
            $accessChanged = $passwordChanged || $roleChanged || $statusChanged;

            $lockedUser->fill($validated);

            if ($accessChanged) {
                // تغییر رمز، نقش یا وضعیت باید تمام اعتبارنامه‌های قبلی را باطل کند.
                $lockedUser->setRememberToken(Str::random(60));
            }

            $lockedUser->save();

            // نقش/وضعیت حساب و پروفایل پزشک یک مرز امنیتی مشترک دارند.
            // تغییر نقش پزشک به نقش دیگر، پروفایل عمومی و دسترسی‌های حرفه‌ای را می‌بندد.
            $doctorProfile = Doctor::query()
                ->where('user_id', $lockedUser->id)
                ->lockForUpdate()
                ->first();

            if ($newRole === 'doctor' && !$doctorProfile) {
                throw ValidationException::withMessages([
                    'role' => 'برای تبدیل این حساب به پزشک، ابتدا باید پرونده حرفه‌ای پزشک ایجاد شود.',
                ]);
            }

            if ($doctorProfile) {
                $doctorProfile->is_active = $newRole === 'doctor' && $newIsActive;

                if ($newRole === 'doctor' && $newIsActive) {
                    // فقط مدیر مجاز به ویرایش نقش پزشک است؛ فعال‌سازی یعنی تأیید مدیر.
                    $doctorProfile->registration_status = 'approved';
                    $doctorProfile->approved_at ??= now();
                    $doctorProfile->approved_by = Auth::guard('web')->id();
                    $doctorProfile->rejection_reason = null;
                    $doctorProfile->trial_started_at ??= now();
                    $doctorProfile->site_expires_at ??= now()->addDays(7);
                }

                $doctorProfile->save();
            }

            if ($accessChanged && config('session.driver') === 'database') {
                DB::connection(config('session.connection'))
                    ->table(config('session.table', 'sessions'))
                    ->where('user_id', $lockedUser->getAuthIdentifier())
                    ->delete();
            }

            $updated = $lockedUser->refresh();

            $audit->handle('managed_user.updated', $updated, [
                'role' => $updated->role,
                'is_active' => (bool) $updated->is_active,
                'role_changed' => $roleChanged,
                'status_changed' => $statusChanged,
                'password_changed' => $passwordChanged,
                'sessions_revoked' => $accessChanged && config('session.driver') === 'database',
            ]);

            return $updated;
        });
    }
}
