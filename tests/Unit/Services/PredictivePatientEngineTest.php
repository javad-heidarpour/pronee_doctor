<?php

declare(strict_types=1);

namespace Tests\\Unit\\Services;

use App\\Models\\Patient;
use App\\Services\\PredictivePatientEngine;
use Carbon\\Carbon;
use Tests\\TestCase;

final class PredictivePatientEngineTest extends TestCase
{
    private function patient(): Patient
    {
        $patient = new Patient();
        $patient->setAttribute('id', 5);
        $patient->setAttribute('name', 'بیمار آزمایشی');

        return $patient;
    }

    public function test_trend_is_reported_only_with_four_samples_over_fourteen_days(): void
    {
        $rows = collect([100, 102, 120, 125])->map(fn ($value, $i) => (object) [
            'measurement_type' => 'blood_glucose',
            'value' => $value,
            'unit' => 'mg/dL',
            'measured_at' => Carbon::parse('2026-10-01')->addDays($i * 10),
        ]);

        $result = app(PredictivePatientEngine::class)->evaluate(
            $this->patient(),
            $rows,
            collect(),
            Carbon::parse('2026-11-01')
        );

        $this->assertCount(1, $result['signals']);
        $this->assertSame('increasing', $result['signals'][0]['direction']);
        $this->assertSame('limited', $result['confidence']);
    }

    public function test_sparse_data_does_not_create_a_trend_or_false_confidence(): void
    {
        $rows = collect([(object) [
            'measurement_type' => 'systolic_bp',
            'value' => 130,
            'unit' => 'mmHg',
            'measured_at' => Carbon::parse('2026-10-30'),
        ]]);

        $result = app(PredictivePatientEngine::class)->evaluate(
            $this->patient(),
            $rows,
            collect(),
            Carbon::parse('2026-11-01')
        );

        $this->assertSame([], $result['signals']);
        $this->assertSame('insufficient', $result['confidence']);
    }

    public function test_unrelated_measurement_types_are_not_combined_for_strong_coverage(): void
    {
        $rows = collect(['systolic_bp', 'blood_glucose', 'weight', 'heart_rate'])
            ->flatMap(fn ($type) => collect([1, 2, 3])->map(fn ($day) => (object) [
                'measurement_type' => $type,
                'value' => 100 + $day,
                'unit' => 'unit',
                'measured_at' => Carbon::parse('2026-10-01')->addDays($day * 10),
            ]));

        $result = app(PredictivePatientEngine::class)->evaluate(
            $this->patient(),
            $rows,
            collect(),
            Carbon::parse('2026-11-01')
        );

        $this->assertSame(12, $result['measurement_count']);
        $this->assertSame('insufficient', $result['confidence']);
        $this->assertSame([], $result['signals']);
    }

    public function test_future_measurements_are_excluded_from_trend_and_coverage(): void
    {
        $rows = collect([100, 102, 120, 125])->map(fn ($value, $i) => (object) [
            'measurement_type' => 'blood_glucose',
            'value' => $value,
            'unit' => 'mg/dL',
            'measured_at' => Carbon::parse('2026-10-01')->addDays($i * 10),
        ]);

        $result = app(PredictivePatientEngine::class)->evaluate(
            $this->patient(),
            $rows,
            collect(),
            Carbon::parse('2026-10-21')
        );

        $this->assertSame(3, $result['measurement_count']);
        $this->assertSame([], $result['signals']);
        $this->assertSame('insufficient', $result['confidence']);
    }

    public function test_strong_coverage_requires_twelve_samples_in_the_same_series(): void
    {
        $rows = collect(range(0, 11))->map(fn ($i) => (object) [
            'measurement_type' => 'blood_glucose',
            'value' => 100 + $i,
            'unit' => 'mg/dL',
            'measured_at' => Carbon::parse('2026-10-01')->addDays($i * 3),
        ]);

        $result = app(PredictivePatientEngine::class)->evaluate(
            $this->patient(),
            $rows,
            collect(),
            Carbon::parse('2026-11-01')
        );

        $this->assertSame('strong', $result['confidence']);
        $this->assertSame(12, $result['measurement_count']);
    }

    public function test_overdue_and_upcoming_followups_are_reported_separately(): void
    {
        $tasks = collect([
            (object) ['status' => 'pending', 'due_at' => Carbon::parse('2026-10-31')],
            (object) ['status' => 'in_progress', 'due_at' => Carbon::parse('2026-11-04')],
        ]);

        $result = app(PredictivePatientEngine::class)->evaluate(
            $this->patient(),
            collect(),
            $tasks,
            Carbon::parse('2026-11-01')
        );

        $this->assertSame(1, $result['overdue_followups']);
        $this->assertSame(1, $result['upcoming_followups']);
        $this->assertTrue($result['needs_review']);
    }
}
