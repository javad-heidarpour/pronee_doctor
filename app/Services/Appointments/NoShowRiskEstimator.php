<?php

declare(strict_types=1);

namespace App\Services\Appointments;

use Illuminate\Support\Collection;

final class NoShowRiskEstimator
{
    /**
     * Descriptive historical rate only; this is not a trained prediction model.
     */
    public function summarize(Collection $appointments): array
    {
        $eligible = $appointments->filter(fn ($appointment) => in_array(
            $appointment->attendance_outcome,
            ['attended', 'no_show'],
            true
        ));

        $total = $eligible->count();
        $missed = $eligible->where('attendance_outcome', 'no_show')->count();
        $rate = $total > 0 ? round(($missed / $total) * 100, 1) : null;

        $band = $total < 3 ? 'insufficient'
            : ($rate >= 50 ? 'high' : ($rate >= 25 ? 'moderate' : 'low'));

        return [
            'observed_appointments' => $total,
            'no_show_count' => $missed,
            'attended_count' => $eligible->where('attendance_outcome', 'attended')->count(),
            'historical_no_show_rate' => $rate,
            'risk_band' => $band,
            'risk_label' => [
                'insufficient' => 'سابقه کافی نیست',
                'high' => 'نرخ عدم حضور تاریخی بالا',
                'moderate' => 'نرخ عدم حضور تاریخی متوسط',
                'low' => 'نرخ عدم حضور تاریخی پایین',
            ][$band],
            'minimum_sample_met' => $total >= 3,
        ];
    }
}
