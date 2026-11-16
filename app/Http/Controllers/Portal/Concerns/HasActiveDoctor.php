<?php

namespace App\Http\Controllers\Portal\Concerns;

use App\Models\Doctor;
use App\Models\Address;
use Illuminate\Support\Facades\Auth;

trait HasActiveDoctor
{
    protected function currentDoctor(): Doctor
    {
        $user = Auth::guard('web')->user();

        abort_unless($user, 401, 'کاربر وارد نشده است.');

        $doctors = $user->accessibleDoctors();
        $doctorId = session('active_doctor_id');

        $doctor = $doctorId
            ? (clone $doctors)->whereKey($doctorId)->first()
            : null;

        if (!$doctor) {
            session()->forget('active_doctor_id');

            $availableDoctors = (clone $doctors)->get();

            abort_if(
                $availableDoctors->isEmpty(),
                403,
                'هیچ پرونده پزشک فعالی برای این حساب کاربری در دسترس نیست.'
            );

            abort_if(
                $availableDoctors->count() > 1,
                403,
                'ابتدا پزشک فعال را از داشبورد انتخاب کنید.'
            );

            $doctor = $availableDoctors->first();
            session(['active_doctor_id' => $doctor->id]);
        }

        return $doctor;
    }
    /**
     * تغییر تنظیمات ساختاری مطب فقط برای پزشک یا مدیر مجاز است.
     * منشی می‌تواند عملیات روزمره نوبت‌ها را انجام دهد، نه پیکربندی مطب.
     */
    protected function authorizeDoctorOnly(): void
    {
        abort_if(
            Auth::guard('web')->user()?->role === 'secretary',
            403,
            'منشی اجازه تغییر تنظیمات ساختاری مطب را ندارد.'
        );
    }

    /**
     * اعمال مجوزهای واقعی منشی از pivot doctor_user.
     * پزشک و مدیر محدود به مجوزهای منشی نیستند؛ منشی بدون مجوز صریح رد می‌شود.
     */
    protected function authorizeSecretaryPermission(string $permission, ?Doctor $doctor = null): void
    {
        $user = Auth::guard('web')->user();

        if (!$user || $user->role !== 'secretary') {
            return;
        }

        $doctor ??= $this->currentDoctor();
        $assignment = $user->doctors()
            ->where('doctors.id', $doctor->id)
            ->first();

        abort_unless($assignment, 403, 'شما به این پزشک دسترسی ندارید.');

        $permissions = $assignment->pivot?->permissions ?? [];
        if (is_string($permissions)) {
            $permissions = json_decode($permissions, true) ?: [];
        }

        abort_unless(
            is_array($permissions) && in_array($permission, $permissions, true),
            403,
            'مجوز انجام این عملیات برای حساب شما فعال نیست.'
        );
    }

    protected function accessibleClinicIds(?Doctor $doctor = null): array
    {
        $user = Auth::guard('web')->user();
        $doctor ??= $this->currentDoctor();

        if (!$user) {
            return [];
        }

        if (in_array($user->role, ['admin', 'doctor'], true)) {
            return $doctor->address()->where('addresses.is_active', true)->pluck('addresses.id')
                ->map(fn ($id) => (int) $id)->all();
        }

        $assignment = $user->doctors()
            ->where('doctors.id', $doctor->id)
            ->first();

        $ids = $assignment?->pivot?->clinic_ids ?? [];

        if (is_string($ids)) {
            $ids = json_decode($ids, true) ?: [];
        }

        if (!is_array($ids)) {
            return [];
        }

        return $doctor->address()
            ->whereIn('addresses.id', $ids)
            ->where('addresses.is_active', true)
            ->pluck('addresses.id')
            ->map(fn ($id) => (int) $id)
            ->all();
    }

    protected function authorizeClinicAccess(int $clinicId, ?Doctor $doctor = null): void
    {
        abort_unless(
            in_array($clinicId, $this->accessibleClinicIds($doctor), true),
            403,
            'شما به این مطب دسترسی ندارید.'
        );
    }

    protected function accessibleClinicsQuery(?Doctor $doctor = null)
    {
        $doctor ??= $this->currentDoctor();

        return Address::query()
            ->where('doctor_id', $doctor->id)
            ->whereIn('id', $this->accessibleClinicIds($doctor));
    }

}
