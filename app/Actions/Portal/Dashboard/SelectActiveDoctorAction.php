<?php

namespace App\Actions\Portal\Dashboard;

use App\Models\User;

class SelectActiveDoctorAction
{
    public function handle(User $user, int $doctorId): void
    {
        $hasAccess = $user->accessibleDoctors()
            ->whereKey($doctorId)
            ->exists();

        abort_unless($hasAccess, 403, 'دسترسی به این پزشک مجاز نیست.');

        session(['active_doctor_id' => $doctorId]);
    }
}