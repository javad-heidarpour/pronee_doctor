<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Contracts\Sms\SmsGateway;
use App\Models\PatientTreatmentInstallment;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Throwable;

class SendInstallmentReminders extends Command
{
    protected $signature = 'finance:send-installment-reminders';
    protected $description = 'Send consent-based reminders for upcoming and overdue treatment installments';

    public function handle(SmsGateway $sms): int
    {
        $template = config('services.ghasedak.installment_reminder_template');
        if (!config('services.ghasedak.api_key') || !$template) {
            $this->warn('Ghasedak API key or installment reminder template is not configured.');
            return self::SUCCESS;
        }

        $counts = ['sent' => 0, 'failed' => 0];
        PatientTreatmentInstallment::query()
            ->whereColumn('paid_amount', '<', 'amount')
            ->whereIn('status', ['pending', 'partial', 'overdue'])
            ->whereDate('due_date', '<=', today()->addDays(3))
            ->where(function ($q): void {
                $q->whereNull('last_reminded_at')->orWhere('last_reminded_at', '<', today()->startOfDay());
            })
            ->whereHas('plan', fn ($q) => $q->where('sms_reminders_enabled', true)->where('sms_consent_confirmed', true))
            ->whereHas('plan.patient', fn ($q) => $q->whereNotNull('phone')->where('phone', '!=', ''))
            ->with(['plan.patient:id,name,phone'])
            ->orderBy('due_date')
            ->chunkById(50, function ($rows) use ($sms, $template, &$counts): void {
                foreach ($rows as $row) {
                    $claimed = PatientTreatmentInstallment::query()
                        ->whereKey($row->id)
                        ->whereColumn('paid_amount', '<', 'amount')
                        ->where(function ($q): void {
                            $q->whereNull('last_reminded_at')->orWhere('last_reminded_at', '<', today()->startOfDay());
                        })
                        ->update(['last_reminded_at' => now()]);

                    if (!$claimed) continue;

                    $plan = $row->plan;
                    try {
                        $sms->sendTemplate(
                            $plan->patient->phone,
                            $template,
                            [
                                ['param' => 'param1', 'value' => $plan->patient->name],
                                ['param' => 'param2', 'value' => $row->due_date->format('Y/m/d')],
                                ['param' => 'param3', 'value' => number_format(max(0, $row->amount - $row->paid_amount))],
                                ['param' => 'param4', 'value' => $plan->title],
                            ],
                            'installment-' . $row->id . '-' . today()->format('Ymd')
                        );
                        $counts['sent']++;
                    } catch (Throwable $exception) {
                        // A failed delivery is not retried the same day, preventing duplicate SMS bursts.
                        $counts['failed']++;
                        Log::warning('Installment reminder SMS failed', [
                            'installment_id' => $row->id,
                            'exception' => $exception::class,
                        ]);
                    }
                }
            });

        $this->info("Installment reminders: {$counts['sent']} sent, {$counts['failed']} failed.");
        return self::SUCCESS;
    }
}
