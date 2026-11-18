<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Patient;
use Illuminate\Support\Collection;

final class PatientHealthIntelligence
{
    /**
     * Build explainable, non-diagnostic insights from the patient's own record.
     *
     * @param Collection<int, mixed> $followUps
     * @return array{summary: array<int, array<string, string|int>>, insights: array<int, array<string, string>>, trends: array<int, array<string, string>>}
     */
    public function build(Patient $patient, Collection $followUps, Collection $observations = new Collection()): array
    {
        $now = now();
        $vitals = $patient->vitalSigns ?? collect();
        $overdue = $followUps->filter(fn ($task) => $task->due_at && $task->due_at->isPast());
        $upcoming = $followUps->filter(fn ($task) => $task->due_at && $task->due_at->between($now, $now->copy()->addDays(7)));

        $summary = [
            ['label' => 'اندازه‌گیری‌های مرکز', 'value' => $vitals->count(), 'caption' => 'ثبت‌شده توسط کادر درمان'],
            ['label' => 'ثبت‌های خانگی', 'value' => $observations->count(), 'caption' => 'ثبت‌شده توسط خود بیمار'],
            ['label' => 'پیگیری عقب‌افتاده', 'value' => $overdue->count(), 'caption' => 'نیازمند هماهنگی با مرکز'],
            ['label' => 'پیگیری این هفته', 'value' => $upcoming->count(), 'caption' => 'در هفت روز آینده'],
        ];

        $insights = [];
        if ($overdue->isNotEmpty()) {
            $insights[] = [
                'level' => 'warning',
                'title' => 'پیگیری سررسید گذشته دارید',
                'description' => $overdue->count().' مورد از برنامه‌های پیگیری شما از موعد مقرر عبور کرده است.',
                'action' => 'برای تعیین زمان انجام یا اصلاح برنامه، با مرکز درمانی هماهنگ کنید.',
            ];
        }
        if ($upcoming->isNotEmpty()) {
            $insights[] = [
                'level' => 'info',
                'title' => 'پیگیری‌های پیش‌رو',
                'description' => $upcoming->count().' برنامه پیگیری در هفت روز آینده زمان‌بندی شده است.',
                'action' => 'برای انجام به‌موقع آن‌ها برنامه‌ریزی کنید.',
            ];
        }
        $latestMeasurement = $vitals->first();
        if ($latestMeasurement && $latestMeasurement->measured_at && $latestMeasurement->measured_at->lt($now->copy()->subDays(90))) {
            $insights[] = [
                'level' => 'info',
                'title' => 'اطلاعات اندازه‌گیری قدیمی است',
                'description' => 'آخرین اندازه‌گیری ثبت‌شده بیش از ۹۰ روز قبل بوده است.',
                'action' => 'اگر پزشک اندازه‌گیری دوره‌ای توصیه کرده، زمان ثبت بعدی را از او بپرسید.',
            ];
        }
        if ($vitals->isEmpty() && $observations->isEmpty()) {
            $insights[] = [
                'level' => 'info',
                'title' => 'هنوز داده‌ای برای تحلیل روند نداریم',
                'description' => 'هنوز اندازه‌گیری مرکز درمانی یا ثبت خانگی برای شما موجود نیست.',
                'action' => 'در صورت نیاز، اندازه‌گیری را در مراجعه بعدی از پزشک بخواهید ثبت کند.',
            ];
        }

        $trendEntries = $vitals->map(fn ($item) => (object) [
            'measurement_type' => $item->measurement_type,
            'value' => $item->value,
            'unit' => $item->unit,
            'measured_at' => $item->measured_at,
            'source_label' => 'ثبت‌شده توسط مرکز درمانی',
            'source_key' => 'clinic',
        ])->concat($observations->map(fn ($item) => (object) [
            'measurement_type' => $item->measurement_type,
            'value' => $item->value,
            'unit' => $item->unit,
            'measured_at' => $item->observed_at,
            'source_label' => 'ثبت‌شده توسط بیمار',
            'source_key' => 'patient',
        ]));

        $trends = $trendEntries
            ->filter(fn ($item) => is_numeric($item->value) && $item->measured_at)
            ->groupBy(fn ($item) => mb_strtolower(trim((string) $item->measurement_type)).'|'.trim((string) $item->unit).'|'.$item->source_key)
            ->map(function ($items, $key) {
                $ordered = $items->sortByDesc(fn ($item) => $item->measured_at->timestamp)->values();
                if ($ordered->count() < 2) {
                    return null;
                }

                $latest = $ordered[0];
                $previous = $ordered[1];
                $difference = (float) $latest->value - (float) $previous->value;
                $direction = $difference > 0 ? 'up' : ($difference < 0 ? 'down' : 'same');
                $change = $difference > 0 ? '+' : '';
                $change .= rtrim(rtrim(number_format($difference, 2, '.', ''), '0'), '.');

                return [
                    'key' => sha1($key),
                    'label' => (string) $latest->measurement_type,
                    'latest' => (string) $latest->value,
                    'unit' => (string) $latest->unit,
                    'latest_date' => $latest->measured_at->format('Y/m/d'),
                    'previous_date' => $previous->measured_at->format('Y/m/d'),
                    'direction' => $direction,
                    'source_label' => $latest->source_label,
                    'change_label' => $direction === 'same' ? 'بدون تغییر عددی' : 'تغییر '.$change.' '.(string) $latest->unit,
                ];
            })
            ->filter()
            ->take(8)
            ->values()
            ->all();

        return ['summary' => $summary, 'insights' => $insights, 'trends' => $trends];
    }
}
