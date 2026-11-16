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
            ->first();

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
