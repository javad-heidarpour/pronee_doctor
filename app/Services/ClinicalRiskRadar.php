<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Patient;
use Illuminate\Support\Collection;

final class ClinicalRiskRadar
{
    /**
     * Rules are conservative screening prompts, not diagnostic criteria.
     * Clinic measurements may create urgent-review flags; patient-entered
     * observations only create verification prompts.
     */
    private const CLINIC_RULES = [
        'systolic_bp' => [
            ['min' => 180, 'max' => null, 'level' => 'urgent', 'message' => 'فشار سیستولیک بسیار بالا ثبت شده است؛ اندازه‌گیری و وضعیت بیمار فوراً ارزیابی شود.'],
            ['min' => null, 'max' => 80, 'level' => 'urgent', 'message' => 'فشار سیستولیک بسیار پایین ثبت شده است؛ وضعیت بیمار فوراً ارزیابی شود.'],
            ['min' => 160, 'max' => 179.99, 'level' => 'review', 'message' => 'فشار سیستولیک بالا است و به بررسی بالینی نیاز دارد.'],
        ],
        'diastolic_bp' => [
            ['min' => 120, 'max' => null, 'level' => 'urgent', 'message' => 'فشار دیاستولیک بسیار بالا ثبت شده است؛ اندازه‌گیری و وضعیت بیمار فوراً ارزیابی شود.'],
            ['min' => null, 'max' => 50, 'level' => 'urgent', 'message' => 'فشار دیاستولیک پایین ثبت شده است؛ وضعیت بیمار فوراً ارزیابی شود.'],
            ['min' => 100, 'max' => 119.99, 'level' => 'review', 'message' => 'فشار دیاستولیک بالا است و به بررسی بالینی نیاز دارد.'],
        ],
        'heart_rate' => [
            ['min' => 130, 'max' => null, 'level' => 'urgent', 'message' => 'ضربان قلب بسیار بالا ثبت شده است؛ وضعیت بیمار فوراً ارزیابی شود.'],
            ['min' => null, 'max' => 40, 'level' => 'urgent', 'message' => 'ضربان قلب بسیار پایین ثبت شده است؛ وضعیت بیمار فوراً ارزیابی شود.'],
            ['min' => 110, 'max' => 129.99, 'level' => 'review', 'message' => 'ضربان قلب بالا است و به بررسی بالینی نیاز دارد.'],
        ],
        'temperature' => [
            ['min' => 39.5, 'max' => null, 'level' => 'urgent', 'message' => 'دمای بسیار بالا ثبت شده است؛ وضعیت بیمار فوراً ارزیابی شود.'],
            ['min' => null, 'max' => 35, 'level' => 'urgent', 'message' => 'دمای بسیار پایین ثبت شده است؛ وضعیت بیمار فوراً ارزیابی شود.'],
            ['min' => 38, 'max' => 39.49, 'level' => 'review', 'message' => 'تب ثبت شده و نیازمند بررسی بالینی است.'],
        ],
        'oxygen_saturation' => [
            ['min' => null, 'max' => 90, 'level' => 'urgent', 'message' => 'اشباع اکسیژن بسیار پایین ثبت شده است؛ اندازه‌گیری و وضعیت بیمار فوراً ارزیابی شود.'],
            ['min' => null, 'max' => 93, 'min_exclusive' => 90, 'level' => 'review', 'message' => 'اشباع اکسیژن پایین‌تر از محدوده معمول ثبت شده و باید بازبینی شود.'],
        ],
        'blood_glucose' => [
            ['min' => null, 'max' => 54, 'level' => 'urgent', 'message' => 'قند خون بسیار پایین ثبت شده است؛ وضعیت بیمار فوراً ارزیابی شود.'],
            ['min' => 400, 'max' => null, 'level' => 'urgent', 'message' => 'قند خون بسیار بالا ثبت شده است؛ وضعیت بیمار فوراً ارزیابی شود.'],
            ['min' => 250, 'max' => 399.99, 'level' => 'review', 'message' => 'قند خون بالا ثبت شده و به بررسی بالینی نیاز دارد.'],
        ],
    ];

    public function evaluate(
        Patient $patient,
        Collection $followUps,
        Collection $observations,
        Collection $medicationPlans
    ): array {
        $flags = [];
        $clinicVitals = $patient->vitalSigns ?? collect();

        foreach ($clinicVitals->groupBy('measurement_type') as $type => $items) {
            $latest = $items->sortByDesc(fn ($item) => $item->measured_at?->timestamp ?? 0)->first();
            if (!$latest || !is_numeric($latest->value) || !$latest->measured_at) {
                continue;
            }

            foreach (self::CLINIC_RULES[$type] ?? [] as $rule) {
                $value = (float) $latest->value;
                $matchesMin = $rule['min'] === null || ($rule['min_exclusive'] ?? false ? $value > $rule['min'] : $value >= $rule['min']);
                $matchesMax = $rule['max'] === null || $value <= $rule['max'];
                if ($matchesMin && $matchesMax) {
                    $flags[] = $this->flag(
                        $rule['level'],
                        'بررسی علامت حیاتی',
                        $rule['message'],
                        'clinic',
                        $latest->measured_at,
                        (string) $latest->measurement_type,
                        (string) $latest->value.' '.(string) $latest->unit
                    );
                    break;
                }
            }
        }

        foreach ($observations->groupBy('measurement_type') as $observationType => $observationItems) {
            $observation = $observationItems->sortByDesc(fn ($item) => $item->observed_at?->timestamp ?? 0)->first();
            if (!$observation) {
                continue;
            }
            $type = ['pulse' => 'heart_rate', 'spo2' => 'oxygen_saturation'][$observationType] ?? $observationType;
            if (!isset(self::CLINIC_RULES[$type]) || !is_numeric($observation->value) || !$observation->observed_at) {
                continue;
            }

            foreach (self::CLINIC_RULES[$type] as $rule) {
                $value = (float) $observation->value;
                $matchesMin = $rule['min'] === null || ($rule['min_exclusive'] ?? false ? $value > $rule['min'] : $value >= $rule['min']);
                $matchesMax = $rule['max'] === null || $value <= $rule['max'];
                if ($matchesMin && $matchesMax) {
                    $flags[] = $this->flag(
                        'review',
                        'بازبینی اندازه‌گیری خانگی',
                        'بیمار مقداری خارج از محدوده غربالگری ثبت کرده است؛ ابتدا صحت اندازه‌گیری و سپس وضعیت بالینی بررسی شود.',
                        'patient',
                        $observation->observed_at,
                        (string) $type,
                        (string) $observation->value.' '.(string) $observation->unit
                    );
                    break;
                }
            }
        }

        $overdue = $followUps->filter(fn ($task) => $task->due_at && $task->due_at->isPast());
        if ($overdue->isNotEmpty()) {
            $flags[] = $this->flag(
                'review',
                'پیگیری درمانی عقب‌افتاده',
                $overdue->count().' برنامه پیگیری از موعد مقرر عبور کرده است.',
                'follow_up',
                $overdue->min('due_at'),
                'follow_up',
                $overdue->count().' مورد'
            );
        }

        $skipped = $medicationPlans->flatMap(fn ($plan) => $plan->doses ?? collect())
            ->filter(fn ($dose) => $dose->status === 'skipped');
        if ($skipped->count() >= 3) {
            $flags[] = $this->flag(
                'review',
                'گزارش عدم مصرف مکرر دارو',
                $skipped->count().' نوبت عدم مصرف در ثبت‌های هفت روز اخیر گزارش شده است؛ علت با بیمار بررسی شود.',
                'medication',
                $skipped->max('scheduled_for'),
                'medication_adherence',
                $skipped->count().' نوبت'
            );
        }

        $priority = collect($flags)->contains(fn ($flag) => $flag['level'] === 'urgent')
            ? 'urgent'
            : (count($flags) ? 'review' : 'routine');

        $latestVital = $clinicVitals->sortByDesc(fn ($item) => $item->measured_at?->timestamp ?? 0)->first();

        return [
            'patient_id' => $patient->id,
            'patient_name' => $patient->name,
            'priority' => $priority,
            'priority_label' => ['urgent' => 'بررسی فوری', 'review' => 'نیازمند بررسی', 'routine' => 'بدون پرچم فعال'][$priority],
            'flags_count' => count($flags),
            'flags' => $flags,
            'last_clinic_measurement_at' => $latestVital?->measured_at?->toIso8601String(),
            'clinic_measurements_count' => $clinicVitals->count(),
            'home_measurements_count' => $observations->count(),
            'overdue_followups_count' => $overdue->count(),
            'active_medication_plans_count' => $medicationPlans->count(),
        ];
    }

    private function flag(string $level, string $title, string $description, string $source, $date, string $type, string $value): array
    {
        return [
            'level' => $level,
            'title' => $title,
            'description' => $description,
            'source' => $source,
            'source_label' => [
                'clinic' => 'اندازه‌گیری ثبت‌شده در مرکز',
                'patient' => 'اظهار بیمار / اندازه‌گیری خانگی',
                'follow_up' => 'برنامه پیگیری',
                'medication' => 'ثبت مصرف دارو توسط بیمار',
            ][$source],
            'recorded_at' => $date?->toIso8601String(),
            'measurement_type' => $type,
            'value_label' => $value,
        ];
    }
}
