<?php

namespace App\Actions\Portal\Appointment;

use App\Models\Appointment;

class LoadPortalAppointmentDetailsAction
{
    public function handle(Appointment $appointment, array $accessibleClinicIds): Appointment
    {
        $appointment->load([
            'user',
            'timeSlot',
            'clinic.city',
            'visitReason',
            'surgeryType',
            'latestPayment',
            'smsLogs',
        ]);

        $nationalCode = trim((string) $appointment->patient_national_code);
        $history = collect();

        $identityQuery = null;
        if ($appointment->patient_id !== null) {
            $identityQuery = Appointment::query()
                ->where('patient_id', $appointment->patient_id);
        } elseif ($nationalCode !== '') {
            // Legacy rows can be linked by national code, but never by phone:
            // household phone numbers are not unique patient identifiers.
            $identityQuery = Appointment::query()
                ->whereNull('patient_id')
                ->where('patient_national_code', $nationalCode);
        }

        if ($identityQuery !== null) {
            $history = $identityQuery
                ->where('doctor_id', $appointment->doctor_id)
                ->whereIn('clinic_id', $accessibleClinicIds)
                ->where('id', '!=', $appointment->id)
                ->whereNotIn('status', ['cancelled'])
                ->where(function ($query) use ($appointment) {
                    $query
                        ->whereDate('appointment_date', '<', $appointment->appointment_date)
                        ->orWhere(function ($sameDay) use ($appointment) {
                            $sameDay
                                ->whereDate('appointment_date', $appointment->appointment_date)
                                ->where('appointment_time', '<', $appointment->appointment_time);
                        });
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
