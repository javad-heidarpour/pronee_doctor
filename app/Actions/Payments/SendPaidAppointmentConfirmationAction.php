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
     *
     * SMS failures must never turn a successful payment into a failed callback.
     */
    public function handle(int $appointmentId): void
    {
        try {
            $this->smsAction->handle($appointmentId);

            Log::info('APPOINTMENT CONFIRMATION SMS TRIGGERED', [
                'appointment_id' => $appointmentId,
            ]);
        } catch (Throwable $exception) {
            Log::error('APPOINTMENT CONFIRMATION SMS FAILED', [
                'appointment_id' => $appointmentId,
                'exception' => $exception->getMessage(),
            ]);
        }
    }
}
