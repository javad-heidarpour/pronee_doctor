<?php

namespace App\Actions\Portal\Users;

use App\Models\Doctor;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class CreateManagedUserAction
{
    /**
     * ایجاد کاربر مدیریتی و در صورت نیاز اتصال اولیه منشی به پزشک.
     */
    public function handle(\n        array $validated,\n        ?Doctor $doctor = null,\n        ?RecordSecurityAuditEventAction $audit = null\n    ): User
    {
        return DB::transaction(function () use ($validated, $doctor) {
            $user = User::create($validated);

            if ($doctor && $user->role === 'secretary') {
                $user->doctors()->attach($doctor->id, [
                    'permissions' => json_encode([], JSON_UNESCAPED_UNICODE),
                    'clinic_ids' => json_encode([], JSON_UNESCAPED_UNICODE),
                ]);
            }

            $audit?->handle('managed_user.created', $user, [\n                'role' => $user->role,\n                'is_active' => (bool) $user->is_active,\n                'doctor_id' => $doctor?->id,\n            ]);\n\n            return $user;
        });
    }
}
