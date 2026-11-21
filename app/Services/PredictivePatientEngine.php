<?php

declare(strict_types=1);

namespace App\\Services;

use App\\Models\\Patient;
use Carbon\\CarbonInterface;
use Illuminate\\Support\\Collection;

final class PredictivePatientEngine
{
    private const LABELS = [
        'systolic_bp' => 'فشار سیستولیک',
        'diastolic_bp' => 'فشار دیاستولیک',
        'blood_glucose' => 'قند خون',
        'heart_rate' => 'ضربان قلب',
        'temperature' => 'دمای بدن',
        'oxygen_saturation' => 'اشباع اکسیژن',
        'weight' => 'وزن',
    ];

    /**
     * Describe longitudinal changes only. This is not a validated prediction model.
     *
     * @return array<string, mixed>
     */
    public function evaluate(
        Patient $patient,
        Collection $measurements,
        Collection $followUps,
        ?CarbonInterface $now = null
    ): array {
        $now = $now ?? now();
        $signals = [];
        $seriesCoverage = [];
        $validMeasurementCount = 0;

        foreach ($measurements->groupBy('measurement_type') as $type => $rows) {
            $rows = $rows->filter(fn ($row) => is_numeric($row->value)
                    && $row->measured_at instanceof CarbonInterface
                    && !$row->measured_at->greaterThan($now->copy()->addMinutes(5)))
                ->sortBy(fn ($row) => $row->measured_at->timestamp)
                ->values();

            if ($rows->isEmpty()) {
                continue;
            }

            $validMeasurementCount += $rows->count();
            $first = $rows->first();
            $last = $rows->last();
            $spanDays = (int) $first->measured_at->diffInDays($last->measured_at);
            $seriesCoverage[] = [
                'type' => (string) $type,
                'count' => $rows->count(),
                'span_days' => $spanDays,
            ];

            if ($rows->count() < 4 || $spanDays < 14) {
                continue;
            }

            $half = max(2, (int) floor($rows->count() / 2));
            $early = $rows->take($half)->avg(fn ($row) => (float) $row->value);
            $recent = $rows->slice($rows->count() - $half)->avg(fn ($row) => (float) $row->value);

            if ($early === null || abs((float) $early) < 0.0001) {
                continue;
            }

            $change = (($recent - $early) / abs($early)) * 100;
            if (abs($change) < 10) {
                continue;
            }

            $signals[] = [
                'type' => (string) $type,
                'label' => self::LABELS[$type] ?? (string) $type,
                'direction' => $change > 0 ? 'increasing' : 'decreasing',
                'direction_label' => $change > 0 ? 'روند افزایشی' : 'روند کاهشی',
                'change_percent' => round($change, 1),
                'early_average' => round((float) $early, 1),
                'recent_average' => round((float) $recent, 1),
                'unit' => (string) ($last->unit ?? ''),
                'samples' => $rows->count(),
                'span_days' => $spanDays,
                'last_measured_at' => $last->measured_at->toIso8601String(),
                'message' => 'تغییر نسبی در میانگین بخش اخیر داده‌ها مشاهده شده است؛ جهت و اهمیت بالینی آن باید توسط پزشک تفسیر شود.',
            ];
        }

        $active = fn ($task): bool => in_array($task->status, ['pending', 'in_progress'], true);
        $overdue = $followUps->filter(fn ($task) => $active($task)
            && $task->due_at
            && $task->due_at->lessThan($now))->count();
        $dueSoon = $followUps->filter(fn ($task) => $active($task)
            && $task->due_at
            && $task->due_at->betweenIncluded($now, $now->copy()->addDays(7)))->count();

        $maxSpan = (int) (collect($seriesCoverage)->max('span_days') ?? 0);
        // Coverage is assessed within one measurement series, never by adding
        // unrelated types (e.g. BP + glucose + weight) into a false sample count.
        $hasStrongSeries = collect($seriesCoverage)->contains(
            fn (array $series): bool => $series['count'] >= 12 && $series['span_days'] >= 30
        );
        $hasLimitedSeries = collect($seriesCoverage)->contains(
            fn (array $series): bool => $series['count'] >= 4 && $series['span_days'] >= 14
        );
        $confidence = $hasStrongSeries ? 'strong'
            : ($hasLimitedSeries ? 'limited' : 'insufficient');

        return [
            'patient_id' => $patient->id,
            'patient_name' => $patient->name,
            'measurement_count' => $validMeasurementCount,
            'coverage_days' => $maxSpan,
            'confidence' => $confidence,
            'confidence_label' => [
                'strong' => 'پوشش روند مناسب',
                'limited' => 'پوشش روند محدود',
                'insufficient' => 'داده ناکافی',
            ][$confidence],
            'signals' => $signals,
            'overdue_followups' => $overdue,
            'upcoming_followups' => $dueSoon,
            'needs_review' => $signals !== [] || $overdue > 0,
            'summary' => $signals !== [] ? 'تغییرات روندی در برخی اندازه‌گیری‌ها دیده شده و بازبینی پرونده پیشنهاد می‌شود.'
                : ($overdue > 0 ? 'پیگیری‌های معوق نیازمند تعیین تکلیف هستند.' : 'روند قابل اتکایی برای نتیجه‌گیری شناسایی نشد.'),
        ];
    }
}
