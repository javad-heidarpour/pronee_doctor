<?php

namespace App\Actions\Payments;

use App\Models\Appointment;
use App\Models\AppointmentPayment;

class FindSepCallbackPaymentAction
{
    /**
     * @return array{status:string,payment?:AppointmentPayment,appointment?:Appointment}
     */
    public function handle(string $resNum): array
    {
        $payment = AppointmentPayment::query()
            ->where('payment_uuid', $resNum)
            ->where('gateway_name', 'sep')
            ->first();

        if (! $payment) {
            return ['status' => 'payment_not_found'];
        }

        $appointment = Appointment::query()->find($payment->appointment_id);

        if (! $appointment) {
            return ['status' => 'appointment_not_found'];
        }

        return [
            'status' => 'found',
            'payment' => $payment,
            'appointment' => $appointment,
        ];
    }
}
