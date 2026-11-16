<?php

namespace App\Actions\Payments;

use App\Models\Appointment;
use RuntimeException;

class ValidateAppointmentPaymentPhoneAction
{
    public function handle(Appointment $appointment): string
    {
        $phone = trim((string) $appointment->patient_phone);

        if (! preg_match('/^09\d{9}$/', $phone)) {
            throw new RuntimeException('شماره موبایل بیمار معتبر نیست.');
        }

        return $phone;
    }
}
