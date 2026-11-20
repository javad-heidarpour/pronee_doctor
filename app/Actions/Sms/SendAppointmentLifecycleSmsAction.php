<?php

declare(strict_types=1);

namespace App\Actions\Sms;

use App\Contracts\Sms\SmsGateway;
use App\Models\Appointment;
use App\Models\SmsLog;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Log;
use Throwable;

final class SendAppointmentLifecycleSmsAction
{
    public function __construct(
        private readonly SmsGateway $gateway,
        private readonly BuildAppointmentSmsInputsAction $inputsBuilder
    ) {
    }

    /**
     * @param  'updated'|'cancelled'  $event
     * @return string sent|already_attempted|skipped|failed
     */
    public function handle(int $appointmentId, string $event): string
    {
        $templates = [
            'updated' => [
                'type' => 'appointment_updated',
                'template' => 'services.ghasedak.appointment_update_template',
                'dedupe' => 'appointment-updated',
            ],
            'cancelled' => [
                'type' => 'appointment_cancelled',
                'template' => 'services.ghasedak.appointment_cancellation_template',
                'dedupe' => 'appointment-cancelled',
            ],
        ];

        if (!isset($templates[$event])) {
            return 'skipped';
        }

        $template = trim((string) config($templates[$event]['template'], ''));

        if ($template === '' || !config('services.ghasedak.api_key')) {
            return 'skipped';
        }

        $appointment = Appointment::query()
            ->with('clinic.city')
            ->find($appointmentId);

        if (!$appointment) {
            return 'skipped';
        }

        if ($event === 'cancelled' && $appointment->status !== 'cancelled') {
            return 'skipped';
        }

        if ($event === 'updated' && !in_array($appointment->status, ['pending', 'confirmed'], true)) {
            return 'skipped';
        }

        $sms = $this->inputsBuilder->handle($appointment);
        $mobile = $sms['mobile'];

        if (!preg_match('/^09\d{9}$/', $mobile)) {
            return 'skipped';
        }

        $updatedAt = $appointment->updated_at?->format('YmdHis') ?? now()->format('YmdHis');
        $deduplicationKey = $templates[$event]['dedupe'] . ':' . $appointment->id . ':' . $updatedAt;

        if (SmsLog::query()->where('deduplication_key', $deduplicationKey)->exists()) {
            return 'already_attempted';
        }

        try {
            $smsLog = SmsLog::create([
                'appointment_id' => $appointment->id,
                'type' => $templates[$event]['type'],
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
            $this->gateway->sendTemplate(
                $mobile,
                $template,
                $sms['inputs'],
                $event . '-visit-' . $appointment->id . '-' . $updatedAt
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

            Log::error('Appointment lifecycle SMS failed', [
                'appointment_id' => $appointment->id,
                'event' => $event,
                'exception' => get_class($exception),
            ]);

            return 'failed';
        }
    }
}
