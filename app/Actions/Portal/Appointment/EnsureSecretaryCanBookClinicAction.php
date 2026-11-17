<?php

namespace App\Actions\Portal\Appointment;

use App\Models\Doctor;
use App\Models\User;
use Illuminate\Validation\ValidationException;

class EnsureSecretaryCanBookClinicAction
{
    public function handle(User $user, Doctor $doctor, int $clinicId): void
    {
        if ($user->role !== 'secretary') {
            return;
        }

        $assignment = $user->doctors()
            ->where('doctors.id', $doctor->id)
            ->where('doctors.is_active', true)
            ->first();

        $permissions = $assignment?->pivot?->permissions ?? [];

        if (is_string($permissions)) {
            $permissions = json_decode($permissions, true) ?: [];
        }

        // این Action ممکن است در آینده از مسیر دیگری نیز فراخوانی شود؛
        // بنابراین به کنترل Middleware به‌تنهایی اتکا نمی‌کنیم.
        if (! is_array($permissions) || ! in_array('appointments.manage', $permissions, true)) {
            throw ValidationException::withMessages([
                'time_slot_id' => 'مجوز ثبت نوبت برای شما فعال نیست.',
            ]);
        }

        $allowedClinicIds = $assignment?->pivot?->clinic_ids ?? [];

        if (is_string($allowedClinicIds)) {
            $allowedClinicIds = json_decode($allowedClinicIds, true) ?: [];
        }

        $allowedClinicIds = is_array($allowedClinicIds)
            ? array_map('intval', $allowedClinicIds)
            : [];

        if (! in_array($clinicId, $allowedClinicIds, true)) {
            throw ValidationException::withMessages([
                'time_slot_id' => 'شما اجازه ثبت نوبت در این مطب را ندارید.',
            ]);
        }
    }
}
