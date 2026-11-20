<?php

declare(strict_types=1);

namespace App\Actions\Sms;

use App\Models\Appointment;
use Morilog\Jalali\Jalalian;

final class BuildAppointmentSmsInputsAction
{
    /**
     * @return array{mobile: string, inputs: array<int, array{param: string, value: string}>}
     */
    public function handle(Appointment $appointment): array
    {
        $appointment->loadMissing('clinic.city');

        $clinic = $appointment->clinic;
        $clinicAddress = trim(implode('، ', array_filter([
            $clinic?->city?->name,
            $clinic?->full_address,
        ])));

        if ($clinicAddress === '') {
            $clinicAddress = $clinic?->name ?: 'اطلاعات مطب در دسترس نیست';
        }

        $patientName = trim((string) $appointment->patient_name) ?: 'مراجع گرامی';
        $date = Jalalian::fromDateTime($appointment->appointment_date)->format('j F Y');
        $time = substr((string) $appointment->appointment_time, 0, 5);

        return [
            'mobile' => trim((string) $appointment->patient_phone),
            'inputs' => [
                ['param' => 'param1', 'value' => $patientName],
                ['param' => 'param2', 'value' => $date . ' ساعت ' . $time],
                ['param' => 'param3', 'value' => $clinicAddress],
            ],
        ];
    }
}
