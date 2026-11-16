<?php

namespace App\Actions\Portal\Dashboard;

use App\Models\User;

class SelectActiveDoctorAction
{
    public function handle(User $user, int $doctorId): void
    {
        // فقط پزشکانی که از طریق doctor_user متصل هستند
        $hasAccess = $user->doctors()
            ->where('doctors.id', $doctorId)
            ->where('doctors.is_active', true)
            ->exists();

        abort_unless($hasAccess, 403, 'دسترسی به این پزشک مجاز نیست.');

        session(['active_doctor_id' => $doctorId]);
    }
}
