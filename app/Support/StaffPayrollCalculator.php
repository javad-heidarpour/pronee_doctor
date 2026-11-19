<?php

declare(strict_types=1);

namespace App\Support;

final class StaffPayrollCalculator
{
    /**
     * All monetary values are integer Rials. Minutes are actual elapsed minutes
     * after scheduled breaks have been deducted by the attendance engine.
     */
    public static function calculate(
        array $contract,
        int $workedMinutes,
        int $scheduledMinutes,
        int $paidLeaveMinutes,
        int $overtimeMinutes,
        int $allowanceAmount = 0,
        int $deductionAmount = 0
    ): array {
        $workedMinutes = max(0, $workedMinutes);
        $scheduledMinutes = max(0, $scheduledMinutes);
        $paidLeaveMinutes = max(0, $paidLeaveMinutes);
        $overtimeMinutes = max(0, $overtimeMinutes);
        $allowanceAmount = max(0, $allowanceAmount);
        $deductionAmount = max(0, $deductionAmount);

        $type = $contract['compensation_type'] ?? 'monthly';
        $monthlyBase = max(0, (int) ($contract['base_monthly_salary'] ?? 0));
        $hourlyRate = max(0, (int) ($contract['hourly_rate'] ?? 0));
        $standardMinutes = max(1, (int) ($contract['standard_monthly_minutes'] ?? 9600));
        $multiplier = max(10000, (int) ($contract['overtime_multiplier_basis_points'] ?? 15000));

        $creditedMinutes = $workedMinutes + $paidLeaveMinutes;
        if ($type === 'hourly') {
            $basePay = intdiv($creditedMinutes * $hourlyRate, 60);
            $regularHourlyRate = $hourlyRate;
        } else {
            $basePay = intdiv(min($creditedMinutes, $standardMinutes) * $monthlyBase, $standardMinutes);
            $regularHourlyRate = intdiv($monthlyBase * 60, $standardMinutes);
        }

        $overtimeRate = intdiv($regularHourlyRate * $multiplier, 10000);
        $overtimePay = intdiv($overtimeMinutes * $overtimeRate, 60);
        $gross = $basePay + $overtimePay + $allowanceAmount;
        $deductionAmount = min($gross, $deductionAmount);

        return [
            'worked_minutes' => $workedMinutes,
            'scheduled_minutes' => $scheduledMinutes,
            'paid_leave_minutes' => $paidLeaveMinutes,
            'overtime_minutes' => $overtimeMinutes,
            'base_pay' => $basePay,
            'overtime_pay' => $overtimePay,
            'allowance_amount' => $allowanceAmount,
            'gross_amount' => $gross,
            'deduction_amount' => $deductionAmount,
            'net_amount' => max(0, $gross - $deductionAmount),
            'credited_minutes' => $creditedMinutes,
        ];
    }
}
