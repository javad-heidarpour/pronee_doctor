<?php

declare(strict_types=1);

namespace App\Support;

use Carbon\Carbon;

final class StaffAttendanceCalculator
{
    public static function lateMinutes(Carbon $actualCheckIn, Carbon $scheduledStart, int $graceMinutes): int
    {
        $deadline = $scheduledStart->copy()->addMinutes(max(0, $graceMinutes));

        return $actualCheckIn->greaterThan($deadline)
            ? (int) $deadline->diffInMinutes($actualCheckIn)
            : 0;
    }

    public static function workedMinutes(Carbon $checkIn, Carbon $checkOut, int $breakMinutes = 0): int
    {
        if ($checkOut->lessThanOrEqualTo($checkIn)) {
            return 0;
        }

        return max(0, (int) $checkIn->diffInMinutes($checkOut) - max(0, $breakMinutes));
    }

    public static function shouldCountAbsence(
        Carbon $scheduledDay,
        Carbon $shiftEnd,
        Carbon $now,
        bool $hasCheckIn,
        bool $isHoliday,
        bool $hasPendingOrApprovedLeave
    ): bool {
        if ($hasCheckIn || $isHoliday || $hasPendingOrApprovedLeave) {
            return false;
        }

        if ($scheduledDay->isAfter($now)) {
            return false;
        }

        return !$scheduledDay->isSameDay($now) || $now->greaterThan($shiftEnd);
    }
}
