<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Support\StaffAttendanceCalculator;
use Carbon\Carbon;
use PHPUnit\Framework\TestCase;

final class StaffAttendanceCalculatorTest extends TestCase
{
    public function test_check_in_within_grace_period_is_not_late(): void
    {
        $scheduled = Carbon::parse('2026-11-19 08:00:00');
        $actual = Carbon::parse('2026-11-19 08:09:00');

        $this->assertSame(0, StaffAttendanceCalculator::lateMinutes($actual, $scheduled, 10));
    }

    public function test_check_in_after_grace_period_returns_late_minutes(): void
    {
        $scheduled = Carbon::parse('2026-11-19 08:00:00');
        $actual = Carbon::parse('2026-11-19 08:17:00');

        $this->assertSame(7, StaffAttendanceCalculator::lateMinutes($actual, $scheduled, 10));
    }

    public function test_worked_minutes_subtracts_break_without_becoming_negative(): void
    {
        $start = Carbon::parse('2026-11-19 08:00:00');
        $end = Carbon::parse('2026-11-19 16:30:00');

        $this->assertSame(480, StaffAttendanceCalculator::workedMinutes($start, $end, 30));
        $this->assertSame(0, StaffAttendanceCalculator::workedMinutes($start, $end, 600));
    }
}
