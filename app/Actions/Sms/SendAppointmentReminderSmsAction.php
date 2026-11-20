<?php

declare(strict_types=1);

namespace App\Actions\Sms;

use App\Contracts\Sms\SmsGateway;
use App\Models\Appointment;
use App\Models\SmsLog;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Log;
use Morilog\Jalali\Jalalian;
use Throwable;

final class SendAppointmentReminderSmsAction
{
    public function __construct(
        private readonly SmsGateway $gateway
    ) {
    }

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

            $this->gateway->sendTemplate(
                $mobile,
                $template,
                [
                    ['param' => 'param1', 'value' => $patientName],
                    ['param' => 'param2', 'value' => $date . ' ساعت ' . $time],
                    ['param' => 'param3', 'value' => $clinicAddress],
                ],
                'reminder-' . $appointment->id
            );

            $smsLog->update([
                'status' => 'sent',
                'sent_at' => now(),
            ]);

            return 'sent';
        } catch (Throwable $exception) {
            $smsLog->update([
                'status' => 'failed',
                'error_message' => 'provider_or_connection_error',
            ]);

            Log::error('Appointment reminder SMS failed', [
                'appointment_id' => $appointment->id,
                'exception' => get_class($exception),
            ]);

            return 'failed';
        }
    }
}
