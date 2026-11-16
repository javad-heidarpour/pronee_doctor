<?php

namespace App\Actions\Payments;

use App\Models\Appointment;
use App\Models\AppointmentPayment;

class ProcessLocalSepPaymentCallbackAction
{
    public function __construct(
        private readonly ProcessLocalSepPaymentTestAction $testAction,
        private readonly CancelFailedAppointmentAction $cancelAction
    ) {
    }

    /**
     * @return array{status:string, appointment_id?:int}
     */
    public function handle(string $resNum, string $testResult): array
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

        $appointmentId = (int) $appointment->id;

        $testStatus = $this->testAction->handle(
            $payment,
            $appointment,
            $testResult
        );

        if ($testStatus === 'already_paid') {
            return [
                'status' => 'already_paid',
                'appointment_id' => $appointmentId,
            ];
        }

        if ($testStatus === 'reservation_not_payable') {
            return [
                'status' => 'reservation_not_payable',
                'appointment_id' => $appointmentId,
            ];
        }

        if ($testResult === 'fail') {
            $cancelStatus = $this->cancelAction->handle($payment);

            if ($cancelStatus === 'already_paid') {
                return [
                    'status' => 'already_paid',
                    'appointment_id' => $appointmentId,
                ];
            }

            if ($cancelStatus === 'waiting') {
                return [
                    'status' => 'waiting',
                    'appointment_id' => $appointmentId,
                ];
            }
        }

        return [
            'status' => $testResult === 'success' ? 'success' : 'failed',
            'appointment_id' => $appointmentId,
        ];
    }
}
