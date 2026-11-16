<?php

namespace App\Actions\Portal\Dashboard;

use App\Models\User;

class ResolveActiveDoctorAction
{
    /**
     * @return array{doctors: \Illuminate\Database\Eloquent\Collection, activeDoctorId: int|string|null}
     */
    public function handle(User $user): array
    {
        $doctors = $user->accessibleDoctors()->get();
        $activeDoctorId = session('active_doctor_id');

        if (
            $activeDoctorId &&
            !$doctors->contains('id', (int) $activeDoctorId)
        ) {
            session()->forget('active_doctor_id');
            $activeDoctorId = null;
        }

        if (!$activeDoctorId && $doctors->count() === 1) {
            $activeDoctorId = $doctors->first()->id;
            session(['active_doctor_id' => $activeDoctorId]);
        }

        if ($doctors->isEmpty()) {
            abort(403, 'هیچ پرونده پزشکی فعالی برای این حساب کاربری تعریف نشده است.');
        }

        return [
            'doctors' => $doctors,
            'activeDoctorId' => $activeDoctorId,
        ];
    }
}