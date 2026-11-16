<?php

namespace App\Actions\Payments;

use App\Actions\Sms\SendAppointmentConfirmationSmsAction;
use Illuminate\Support\Facades\Log;
use Throwable;

class SendPaidAppointmentConfirmationAction
{
    public function __construct(
        private readonly SendAppointmentConfirmationSmsAction $smsAction
    ) {
    }

    /**
     * Notify the patient after SEP confirms payment.
     * SMS failures must never turn a successful payment into a failed callback.
     */
    public function handle(int $appointmentId): void
    {
        try {
            $response = $this->smsAction->handle($appointmentId);

            if ($response->getStatusCode() >= 400) {
                Log::warning('Paid appointment SMS was not delivered', [
                    'appointment_id' => $appointmentId,
                    'http_status' => $response->getStatusCode(),
                ]);

                return;
            }

            Log::info('Paid appointment confirmation SMS sent', [
                'appointment_id' => $appointmentId,
            ]);
        } catch (Throwable $exception) {
            Log::error('Paid appointment confirmation SMS failed', [
                'appointment_id' => $appointmentId,
                'exception' => get_class($exception),
            ]);
        }
    }
}
