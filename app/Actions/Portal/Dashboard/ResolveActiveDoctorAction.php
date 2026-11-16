<?php

namespace App\Actions\Portal\Dashboard;

use App\Models\Doctor;
use App\Models\User;
use Illuminate\Support\Collection;

class ResolveActiveDoctorAction
{
    /**
     * پزشکان قابل دسترسی کاربر و پزشک فعال (از Session) را مشخص می‌کند.
     *
     * @return array{doctors: Collection, activeDoctorId: int|string|null}
     */
    public function handle(User $user): array
    {
        // دریافت پزشکان قابل دسترسی بر اساس نقش کاربر
        if ($user->role === 'admin') {
            // ادمین به تمام پزشکان فعال دسترسی دارد
            $doctors = Doctor::query()
                ->where('is_active', true)
                ->get();
        } else {
            // پزشک و منشی فقط پزشکان متصل‌شده در doctor_user را می‌بینند
            $doctors = $user->doctors()
                ->where('doctors.is_active', true)
                ->get();
        }

        // پزشک فعال از Session
        $activeDoctorId = session('active_doctor_id');

        // اگر پزشک انتخاب‌شده دیگر در دسترس نیست
        if (
            $activeDoctorId &&
            !$doctors->contains('id', (int) $activeDoctorId)
        ) {
            session()->forget('active_doctor_id');
            $activeDoctorId = null;
        }

        // اگر فقط یک پزشک متصل است، انتخاب خودکار
        if (!$activeDoctorId && $doctors->count() === 1) {
            $activeDoctorId = $doctors->first()->id;

            session(['active_doctor_id' => $activeDoctorId]);
        }

        // اگر هیچ پزشکی در دسترس نیست
        if ($doctors->isEmpty()) {
            abort(403, 'هیچ پرونده پزشکی برای این حساب کاربری تعریف نشده است.');
        }

        return [
            'doctors' => $doctors,
            'activeDoctorId' => $activeDoctorId,
        ];
    }
}
