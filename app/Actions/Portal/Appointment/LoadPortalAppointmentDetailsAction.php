<?php

namespace App\Actions\Portal\Appointment;

use App\Models\Appointment;

class LoadPortalAppointmentDetailsAction
{
    public function handle(Appointment $appointment): Appointment
    {
        $appointment->load([
            'user',
            'timeSlot',
            'clinic.city',
            'visitReason',
            'surgeryType',
            'latestPayment',
        ]);

        $nationalCode = trim((string) $appointment->patient_national_code);
        $phone = trim((string) $appointment->patient_phone);

        $history = collect();

        if ($nationalCode !== '' || $phone !== '') {
            $history = Appointment::query()
                ->where('doctor_id', $appointment->doctor_id)
                ->where('id', '!=', $appointment->id)
                ->where(function ($query) use ($nationalCode, $phone) {
                    if ($nationalCode !== '') {
                        $query->where('patient_national_code', $nationalCode);
                    } else {
                        $query->where('patient_phone', $phone);
                    }
                })
                ->with(['clinic', 'visitReason', 'latestPayment'])
                ->orderByDesc('appointment_date')
                ->orderByDesc('appointment_time')
                ->limit(10)
                ->get();
        }

        $appointment->setRelation('patientHistory', $history);

        return $appointment;
    }
}
