<?php

namespace App\Actions\Portal\Dashboard;

use App\Models\Doctor;
use App\Models\User;

class SelectActiveDoctorAction
{
    public function handle(User $user, int $doctorId): void
    {
        $hasAccess = $user->role === 'admin'
            ? Doctor::query()
                ->whereKey($doctorId)
                ->where('is_active', true)
                ->exists()
            : $user->doctors()
                ->where('doctors.id', $doctorId)
                ->where('doctors.is_active', true)
                ->exists();

        abort_unless($hasAccess, 403, 'دسترسی به این پزشک مجاز نیست.');

        session(['active_doctor_id' => $doctorId]);
    }
}