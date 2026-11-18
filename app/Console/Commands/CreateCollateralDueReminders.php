<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\PatientCollateral;
use App\Models\PatientFollowUpTask;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class CreateCollateralDueReminders extends Command
{
    protected $signature = 'finance:create-collateral-reminders';
    protected $description = 'Create internal follow-up tasks for collateral due dates and overdue guarantees';

    public function handle(): int
    {
        $created = 0;
        PatientCollateral::query()
            ->where('status', 'held')
            ->where('reminder_enabled', true)
            ->whereNotNull('due_date')
            ->whereDate('due_date', '<=', today()->addDays(7))
            ->where(function ($q): void {
                $q->whereNull('last_reminded_at')
                    ->orWhere('last_reminded_at', '<=', now()->subDays(7));
            })
            ->with(['plan.patient'])
            ->orderBy('due_date')
            ->chunkById(50, function ($collaterals) use (&$created): void {
                foreach ($collaterals as $collateral) {
                    DB::transaction(function () use ($collateral, &$created): void {
                        $claimed = PatientCollateral::query()->whereKey($collateral->id)
                            ->where('status', 'held')->where('reminder_enabled', true)
                            ->where(function ($q): void {
                                $q->whereNull('last_reminded_at')
                                    ->orWhere('last_reminded_at', '<=', now()->subDays(7));
                            })->update(['last_reminded_at' => now()]);

                        if (!$claimed) return;

                        $existingTask = PatientFollowUpTask::query()->where('collateral_id', $collateral->id)->where('status', 'pending')->first();
                        if ($existingTask) return;

                        $plan = $collateral->plan;
                        $dueAt = $collateral->due_date->copy()->setTime(9, 0);
                        if ($dueAt->isPast()) $dueAt = now();

                        PatientFollowUpTask::create([
                            'doctor_id' => $plan->doctor_id,
                            'clinic_id' => $plan->clinic_id,
                            'patient_id' => $plan->patient_id,
                            'treatment_plan_id' => $plan->id,
                            'collateral_id' => $collateral->id,
                            'assigned_to' => $collateral->received_by ?: $plan->created_by,
                            'created_by' => $collateral->received_by ?: $plan->created_by,
                            'title' => 'پیگیری وثیقه طرح درمان: ' . $plan->title,
                            'description' => 'بررسی سررسید یا وضعیت وثیقه ' . $collateral->type
                                . ($collateral->identifier ? ' با شناسه ' . $collateral->identifier : '')
                                . ($collateral->face_value ? ' به مبلغ ' . number_format($collateral->face_value) . ' ریال' : ''),
                            'due_at' => $dueAt,
                            'priority' => $collateral->due_date->isPast() ? 'high' : 'normal',
                            'contact_method' => 'internal',
                            'send_sms' => false,
                            'sms_consent_confirmed' => false,
                            'status' => 'pending',
                        ]);
                        $created++;
                    }, 3);
                }
            });

        $this->info("Collateral reminders created: {$created}");
        return self::SUCCESS;
    }
}
