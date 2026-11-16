<?php

namespace App\Console\Commands;

use App\Actions\Sms\SendAppointmentReminderSmsAction;
use App\Models\Appointment;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class SendAppointmentReminders extends Command
{
    protected $signature = 'appointments:send-reminders';

    protected $description = 'Send configured SMS reminders for confirmed appointments scheduled for tomorrow';

    public function handle(SendAppointmentReminderSmsAction $action): int
    {
        if (!config('services.ghasedak.appointment_reminder_template')) {
            $this->warn('Reminder SMS template is not configured; no messages were sent.');

            Log::warning('Appointment reminder job skipped: GHASEDAK_APPOINTMENT_REMINDER_TEMPLATE is empty.');

            return self::SUCCESS;
        }

        $tomorrow = now()->addDay()->toDateString();

        $counts = [
            'sent' => 0,
            'already_attempted' => 0,
            'skipped' => 0,
            'failed' => 0,
        ];

        Appointment::query()
            ->whereDate('appointment_date', $tomorrow)
            ->where('status', 'confirmed')
            ->with('clinic.city')
            ->orderBy('id')
            ->chunkById(100, function ($appointments) use ($action, &$counts) {
                foreach ($appointments as $appointment) {
                    $result = $action->handle($appointment);

                    if (array_key_exists($result, $counts)) {
                        $counts[$result]++;
                    }
                }
            });

        $summary = sprintf(
            'Appointment reminders completed. Sent: %d, already attempted: %d, skipped: %d, failed: %d.',
            $counts['sent'],
            $counts['already_attempted'],
            $counts['skipped'],
            $counts['failed']
        );

        $this->info($summary);
        Log::info($summary);

        return self::SUCCESS;
    }
}
