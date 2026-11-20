<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Contracts\Sms\SmsGateway;
use App\Models\PatientFollowUpTask;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Throwable;

class SendPatientFollowUpReminders extends Command
{
    protected $signature = 'patients:send-follow-up-reminders';

    protected $description = 'Send due patient follow-up reminders through the configured Ghasedak template';

    public function handle(SmsGateway $sms): int
    {
        $template = config('services.ghasedak.patient_follow_up_template');
        if (!config('services.ghasedak.api_key') || !$template) {
            $this->warn('Ghasedak API key or patient follow-up template is not configured.');
            return self::SUCCESS;
        }

        $counts = ['sent' => 0, 'failed' => 0];
        PatientFollowUpTask::query()
            ->where('status', 'pending')
            ->where('send_sms', true)
            ->where('sms_consent_confirmed', true)
            ->where(function ($query): void {
                $query->whereNull('treatment_plan_id')
                    ->orWhereHas('treatmentPlan', fn ($plan) => $plan
                        ->where('follow_up_sms_enabled', true)
                        ->where('follow_up_sms_consent_confirmed', true)
                        ->whereNull('follow_up_sms_consent_revoked_at'));
            })
            ->whereNull('sms_sent_at')
            ->where('sms_attempts', '<', 3)
            ->where(function ($q): void {
                $q->whereNull('sms_attempted_at')->orWhere('sms_attempted_at', '<=', now()->subMinutes(30));
            })
            ->where('due_at', '<=', now())
            ->whereHas('patient', fn ($q) => $q->whereNotNull('phone')->where('phone', '!=', ''))
            ->with('patient:id,name,phone')
            ->orderBy('due_at')
            ->chunkById(50, function ($tasks) use ($sms, $template, &$counts): void {
                foreach ($tasks as $task) {
                    // Claim before sending so overlapping scheduler runs cannot duplicate a message.
                    $claimed = PatientFollowUpTask::query()
                        ->whereKey($task->id)
                        ->where('status', 'pending')
                        ->where('send_sms', true)
                        ->where('sms_consent_confirmed', true)
                        ->where(function ($query): void {
                            $query->whereNull('treatment_plan_id')
                                ->orWhereHas('treatmentPlan', fn ($plan) => $plan
                                    ->where('follow_up_sms_enabled', true)
                                    ->where('follow_up_sms_consent_confirmed', true)
                                    ->whereNull('follow_up_sms_consent_revoked_at'));
                        })
                        ->whereNull('sms_sent_at')
                        ->where('sms_attempts', '<', 3)
                        ->where(function ($q): void {
                            $q->whereNull('sms_attempted_at')->orWhere('sms_attempted_at', '<=', now()->subMinutes(30));
                        })
                        ->update(['sms_attempted_at' => now(), 'sms_attempts' => $task->sms_attempts + 1]);

                    if (!$claimed) {
                        continue;
                    }

                    try {
                        $sms->sendTemplate(
                            $task->patient->phone,
                            $template,
                            [
                                ['param' => 'param1', 'value' => $task->patient->name],
                                ['param' => 'param2', 'value' => $task->due_at->format('Y/m/d H:i')],
                                ['param' => 'param3', 'value' => $task->title],
                            ],
                            'followup-' . $task->id
                        );

                        $task->forceFill(['sms_sent_at' => now(), 'last_sms_error' => null])->save();
                        $counts['sent']++;
                    } catch (Throwable $exception) {
                        $task->forceFill(['last_sms_error' => mb_substr($exception->getMessage(), 0, 500)])->save();
                        $counts['failed']++;
                        Log::warning('Patient follow-up SMS failed', [
                            'follow_up_id' => $task->id,
                            'exception' => $exception::class,
                        ]);
                    }
                }
            });

        $this->info("Follow-up reminders: {$counts['sent']} sent, {$counts['failed']} failed.");
        return self::SUCCESS;
    }
}