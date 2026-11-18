<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Patient;
use Illuminate\Support\Collection;

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

    public function evaluate(Patient $patient, Collection $measurements, Collection $followUps): array
    {
        $signals = [];
        $coverage = [];
        foreach ($measurements->groupBy('measurement_type') as $type => $rows) {
            $rows = $rows->filter(fn ($row) => is_numeric($row->value) && $row->measured_at)
                ->sortBy(fn ($row) => $row->measured_at->timestamp)->values();
            if ($rows->isEmpty()) continue;

            $first = $rows->first();
            $last = $rows->last();
            $spanDays = (int) $first->measured_at->diffInDays($last->measured_at);
            $coverage[] = ['count' => $rows->count(), 'span_days' => $spanDays];
            if ($rows->count() < 4 || $spanDays < 14) continue;

            $half = max(2, (int) floor($rows->count() / 2));
            $early = $rows->take($half)->avg(fn ($row) => (float) $row->value);
            $recent = $rows->slice($rows->count() - $half)->avg(fn ($row) => (float) $row->value);
            if ($early === null || abs((float) $early) < 0.0001) continue;

            $change = (($recent - $early) / abs($early)) * 100;
            if (abs($change) < 10) continue;

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

        $active = fn ($task) => in_array($task->status, ['pending', 'in_progress'], true);
        $overdue = $followUps->filter(fn ($task) => $active($task) && $task->due_at && $task->due_at->isPast())->count();
        $dueSoon = $followUps->filter(fn ($task) => $active($task) && $task->due_at && $task->due_at->between(now(), now()->addDays(7)))->count();

        $sampleCount = $measurements->count();
        $maxSpan = collect($coverage)->max('span_days') ?? 0;
        $confidence = $sampleCount >= 12 && $maxSpan >= 30 ? 'strong'
            : ($sampleCount >= 4 && $maxSpan >= 14 ? 'limited' : 'insufficient');

        return [
            'patient_id' => $patient->id,
            'patient_name' => $patient->name,
            'measurement_count' => $sampleCount,
            'coverage_days' => (int) $maxSpan,
            'confidence' => $confidence,
            'confidence_label' => ['strong' => 'پوشش داده مناسب', 'limited' => 'پوشش داده محدود', 'insufficient' => 'داده ناکافی'][$confidence],
            'signals' => $signals,
            'overdue_followups' => $overdue,
            'upcoming_followups' => $dueSoon,
            'needs_review' => $signals !== [] || $overdue > 0,
            'summary' => $signals !== [] ? 'تغییرات روندی در برخی اندازه‌گیری‌ها دیده شده و بازبینی پرونده پیشنهاد می‌شود.'
                : ($overdue > 0 ? 'پیگیری‌های معوق نیازمند تعیین تکلیف هستند.' : 'روند قابل اتکایی برای نتیجه‌گیری شناسایی نشد.'),
        ];
    }
}
