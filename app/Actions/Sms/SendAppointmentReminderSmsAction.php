<?php

namespace App\Actions\Sms;

use App\Models\Appointment;
use App\Models\SmsLog;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Morilog\Jalali\Jalalian;
use Throwable;

class SendAppointmentReminderSmsAction
{
    /**
     * Send one configured reminder for a confirmed appointment.
     *
     * @return string sent|already_attempted|skipped|failed
     */
    public function handle(Appointment $appointment): string
    {
        $template = trim((string) config('services.ghasedak.appointment_reminder_template'));

        if ($template === '' || !config('services.ghasedak.api_key') || $appointment->status !== 'confirmed') {
            return 'skipped';
        }

        $mobile = trim((string) $appointment->patient_phone);

        if (!preg_match('/^09\d{9}$/', $mobile)) {
            return 'skipped';
        }

        $deduplicationKey = 'appointment-reminder-day-before:' . $appointment->id;

        if (SmsLog::query()->where('deduplication_key', $deduplicationKey)->exists()) {
            return 'already_attempted';
        }

        try {
            $smsLog = SmsLog::create([
                'appointment_id' => $appointment->id,
                'type' => 'appointment_reminder_day_before',
                'status' => 'pending',
                'provider' => 'ghasedak',
                'deduplication_key' => $deduplicationKey,
            ]);
        } catch (QueryException $exception) {
            if (SmsLog::query()->where('deduplication_key', $deduplicationKey)->exists()) {
                return 'already_attempted';
            }

            throw $exception;
        }

        try {
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

            $response = Http::timeout(15)->withHeaders([
                'ApiKey' => config('services.ghasedak.api_key'),
                'Accept' => 'application/json',
                'Content-Type' => 'application/json',
            ])->post(
                'https://gateway.ghasedak.me/rest/api/v1/WebService/SendOtpSMS',
                [
                    'sendDate' => null,
                    'receptors' => [[
                        'mobile' => $mobile,
                        'clientReferenceId' => 'reminder-' . $appointment->id,
                    ]],
                    'templateName' => $template,
                    'inputs' => [
                        ['param' => 'param1', 'value' => $patientName],
                        ['param' => 'param2', 'value' => $date . ' ساعت ' . $time],
                        ['param' => 'param3', 'value' => $clinicAddress],
                    ],
                    'isVoice' => false,
                ]
            );

            if (!$response->successful() || $response->json('IsSuccess') === false) {
                $smsLog->update([
                    'status' => 'failed',
                    'error_message' => 'provider_rejected_request',
                ]);

                Log::warning('Appointment reminder SMS rejected by provider', [
                    'appointment_id' => $appointment->id,
                    'http_status' => $response->status(),
                ]);

                return 'failed';
            }

            $smsLog->update([
                'status' => 'sent',
                'sent_at' => now(),
            ]);

            return 'sent';
        } catch (Throwable $exception) {
            $smsLog->update([
                'status' => 'failed',
                'error_message' => 'provider_connection_error',
            ]);

            Log::error('Appointment reminder SMS exception', [
                'appointment_id' => $appointment->id,
                'exception' => get_class($exception),
            ]);

            return 'failed';
        }
    }
}
