<?php

namespace App\Actions\Portal\Users;

use App\Actions\Security\RecordSecurityAuditEventAction;
use App\Models\Doctor;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class CreateManagedUserAction
{
    /**
     * ایجاد کاربر مدیریتی و در صورت نیاز اتصال اولیه منشی به پزشک.
     */
    public function handle(
        array $validated,
        RecordSecurityAuditEventAction $audit,
        ?Doctor $doctor = null
    ): User
    {
        return DB::transaction(function () use ($validated, $doctor, $audit) {
            $user = User::create($validated);

            if ($doctor && $user->role === 'secretary') {
                $user->doctors()->attach($doctor->id, [
                    'permissions' => json_encode([], JSON_UNESCAPED_UNICODE),
                    'clinic_ids' => json_encode([], JSON_UNESCAPED_UNICODE),
                ]);
            }

            $audit->handle('managed_user.created', $user, [
                'role' => $user->role,
                'is_active' => (bool) $user->is_active,
                'doctor_id' => $doctor?->id,
            ]);

            return $user;
        });
    }
}
