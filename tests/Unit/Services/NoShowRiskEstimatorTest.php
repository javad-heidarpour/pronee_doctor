<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\Services\Appointments\NoShowRiskEstimator;
use Tests\TestCase;

final class NoShowRiskEstimatorTest extends TestCase
{
    public function test_insufficient_history_is_not_misrepresented_as_low_risk(): void
    {
        $rows = collect([
            (object) ['attendance_outcome' => 'no_show'],
            (object) ['attendance_outcome' => 'attended'],
        ]);

        $result = app(NoShowRiskEstimator::class)->summarize($rows);

        $this->assertSame('insufficient', $result['risk_band']);
        $this->assertSame(50.0, $result['historical_no_show_rate']);
        $this->assertFalse($result['minimum_sample_met']);
    }

    public function test_cancellations_and_unknown_outcomes_are_excluded_from_rate(): void
    {
        $rows = collect([
            (object) ['attendance_outcome' => 'no_show'],
            (object) ['attendance_outcome' => 'attended'],
            (object) ['attendance_outcome' => 'attended'],
            (object) ['attendance_outcome' => 'cancelled'],
            (object) ['attendance_outcome' => null],
        ]);

        $result = app(NoShowRiskEstimator::class)->summarize($rows);

        $this->assertSame(3, $result['observed_appointments']);
        $this->assertSame(66.7, $result['historical_no_show_rate']);
        $this->assertSame('high', $result['risk_band']);
    }

    public function test_low_historical_rate_requires_three_explicit_outcomes(): void
    {
        $rows = collect([
            (object) ['attendance_outcome' => 'attended'],
            (object) ['attendance_outcome' => 'attended'],
            (object) ['attendance_outcome' => 'no_show'],
            (object) ['attendance_outcome' => 'attended'],
        ]);

        $result = app(NoShowRiskEstimator::class)->summarize($rows);

        $this->assertSame(25.0, $result['historical_no_show_rate']);
        $this->assertSame('moderate', $result['risk_band']);
        $this->assertTrue($result['minimum_sample_met']);
    }

    public function test_all_attended_history_has_zero_historical_no_show_rate(): void
    {
        $rows = collect([
            (object) ['attendance_outcome' => 'attended'],
            (object) ['attendance_outcome' => 'attended'],
            (object) ['attendance_outcome' => 'attended'],
        ]);

        $result = app(NoShowRiskEstimator::class)->summarize($rows);

        $this->assertSame(0.0, $result['historical_no_show_rate']);
        $this->assertSame('low', $result['risk_band']);
    }
}
