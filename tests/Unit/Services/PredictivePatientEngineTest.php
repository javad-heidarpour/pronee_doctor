<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\Models\Patient;
use App\Services\PredictivePatientEngine;
use Tests\TestCase;

final class PredictivePatientEngineTest extends TestCase
{
    public function test_trend_is_reported_only_with_four_samples_over_fourteen_days(): void
    {
        $patient = new Patient();
        $patient->setAttribute('id', 5);
        $patient->setAttribute('name', 'آزمایشی');
        $rows = collect([100, 102, 120, 125])->map(fn ($value, $i) => (object) [
            'measurement_type' => 'blood_glucose',
            'value' => $value,
            'unit' => 'mg/dL',
            'measured_at' => now()->subDays(30 - ($i * 10)),
        ]);
        $result = app(PredictivePatientEngine::class)->evaluate($patient, $rows, collect());
        $this->assertCount(1, $result['signals']);
        $this->assertSame('increasing', $result['signals'][0]['direction']);
        $this->assertSame('limited', $result['confidence']);
    }

    public function test_sparse_data_does_not_create_a_trend_or_false_confidence(): void
    {
        $patient = new Patient();
        $patient->setAttribute('id', 6);
        $patient->setAttribute('name', 'بدون داده کافی');
        $rows = collect([(object) ['measurement_type' => 'systolic_bp', 'value' => 130, 'unit' => 'mmHg', 'measured_at' => now()->subDays(2)]]);
        $result = app(PredictivePatientEngine::class)->evaluate($patient, $rows, collect());
        $this->assertSame([], $result['signals']);
        $this->assertSame('insufficient', $result['confidence']);
    }

    public function test_overdue_and_upcoming_followups_are_reported_separately(): void
    {
        $patient = new Patient();
        $patient->setAttribute('id', 7);
        $patient->setAttribute('name', 'پیگیری');
        $tasks = collect([
            (object) ['status' => 'pending', 'due_at' => now()->subDay()],
            (object) ['status' => 'in_progress', 'due_at' => now()->addDays(3)],
        ]);
        $result = app(PredictivePatientEngine::class)->evaluate($patient, collect(), $tasks);
        $this->assertSame(1, $result['overdue_followups']);
        $this->assertSame(1, $result['upcoming_followups']);
        $this->assertTrue($result['needs_review']);
    }
}
