<?php

namespace App\Http\Controllers\Portal\Concerns;

use App\Models\Appointment;
use Illuminate\Support\Facades\Auth;

trait AuthorizesPortalAppointments
{
    protected function authorizeAppointment(Appointment $appointment): void
    {
        $user = Auth::guard('web')->user();

        abort_unless($user, 401, 'کاربر وارد نشده است.');

        $doctor = $this->currentDoctor();

        abort_unless(
            (int) $appointment->doctor_id === (int) $doctor->id &&
            in_array(
                (int) $appointment->clinic_id,
                $this->accessibleClinicIds($doctor),
                true
            ),
            403,
            'شما اجازه دسترسی به این نوبت را ندارید.'
        );
    }
}
