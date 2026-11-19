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

    public function test_absence_is_not_counted_for_holiday_leave_or_existing_check_in(): void
    {
        $day = Carbon::parse('2026-11-18 00:00:00');
        $end = Carbon::parse('2026-11-18 16:00:00');
        $now = Carbon::parse('2026-11-19 09:00:00');

        $this->assertFalse(StaffAttendanceCalculator::shouldCountAbsence($day, $end, $now, true, false, false));
        $this->assertFalse(StaffAttendanceCalculator::shouldCountAbsence($day, $end, $now, false, true, false));
        $this->assertFalse(StaffAttendanceCalculator::shouldCountAbsence($day, $end, $now, false, false, true));
    }

    public function test_current_shift_is_not_absent_until_shift_end(): void
    {
        $day = Carbon::parse('2026-11-19 00:00:00');
        $end = Carbon::parse('2026-11-19 16:00:00');
        $beforeEnd = Carbon::parse('2026-11-19 15:59:00');
        $afterEnd = Carbon::parse('2026-11-19 16:01:00');

        $this->assertFalse(StaffAttendanceCalculator::shouldCountAbsence($day, $end, $beforeEnd, false, false, false));
        $this->assertTrue(StaffAttendanceCalculator::shouldCountAbsence($day, $end, $afterEnd, false, false, false));
    }
}
