<?php

namespace App\Actions\Portal\Appointment;

use App\Models\Appointment;

class LoadPortalAppointmentDetailsAction
{
    public function handle(Appointment $appointment): Appointment
    {
        return $appointment->load([
            'user',
            'timeSlot',
            'clinic.city',
            'visitReason',
        ]);
    }
}
