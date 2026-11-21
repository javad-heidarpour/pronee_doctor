<?php

declare(strict_types=1);

namespace App\\Actions\\Sms;

use App\\Contracts\\Sms\\SmsGateway;
use App\\Models\\Appointment;
use App\\Models\\SmsLog;
use Illuminate\\Database\\QueryException;
use Illuminate\\Support\\Facades\\URL;
use Throwable;

final class SendAppointmentSatisfactionSurveySmsAction
{
    public function __construct(
        private readonly SmsGateway $gateway,
        private readonly BuildAppointmentSmsInputsAction $inputsBuilder
    ) {
    }

    public function handle(int $appointmentId): string
    {
        $template = trim((string) config('services.ghasedak.appointment_satisfaction_survey_template', ''));
        if ($template === '' || !config('services.ghasedak.api_key')) {
            return 'skipped';
        }

        $appointment = Appointment::query()->with('clinic.city')->find($appointmentId);
        if (!$appointment || !in_array($appointment->status, ['visited', 'completed'], true) || $appointment->satisfactionSurvey()->exists()) {
            return 'skipped';
        }

        $sms = $this->inputsBuilder->handle($appointment);
        if (!preg_match('/^09\\d{9}$/', $sms['mobile'])) {
            return 'skipped';
        }

        $url = URL::temporarySignedRoute(
            'patient.satisfaction.show',
            now()->addDays(90),
            ['appointment' => $appointment->id]
        );
        $inputs = [...$sms['inputs'], ['param' => 'param4', 'value' => $url]];
        $key = 'appointment-satisfaction-survey:' . $appointment->id;

        try {
            $log = SmsLog::query()->create([
                'appointment_id' => $appointment->id,
                'type' => 'appointment_satisfaction_survey',
                'status' => 'pending',
                'provider' => 'ghasedak',
                'deduplication_key' => $key,
            ]);
        } catch (QueryException $exception) {
            if (SmsLog::query()->where('deduplication_key', $key)->exists()) {
                return 'already_attempted';
            }
            throw $exception;
        }

        try {
            $this->gateway->sendTemplate(
                $sms['mobile'],
                $template,
                $inputs,
                'satisfaction-survey-' . $appointment->id
            );
            $log->update(['status' => 'sent', 'sent_at' => now()]);
            return 'sent';
        } catch (Throwable $exception) {
            $log->update(['status' => 'failed', 'error_message' => 'provider_or_connection_error']);
            return 'failed';
        }
    }
}
