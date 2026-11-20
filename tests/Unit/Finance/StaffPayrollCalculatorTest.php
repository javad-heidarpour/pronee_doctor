<?php

declare(strict_types=1);

namespace Tests\Unit\Finance;

use App\Support\StaffPayrollCalculator;
use PHPUnit\Framework\TestCase;

final class StaffPayrollCalculatorTest extends TestCase
{
    public function test_monthly_salary_prorates_regular_time_and_adds_overtime_and_allowance(): void
    {
        $result = StaffPayrollCalculator::calculate([
            'compensation_type' => 'monthly',
            'base_monthly_salary' => 30000000,
            'standard_monthly_minutes' => 10000,
            'overtime_multiplier_basis_points' => 15000,
        ], 9000, 10000, 500, 600, 1000000, 500000);

        self::assertSame(28500000, $result['base_pay']);
        self::assertSame(2700000, $result['overtime_pay']);
        self::assertSame(1000000, $result['allowance_amount']);
        self::assertSame(32200000, $result['gross_amount']);
        self::assertSame(500000, $result['deduction_amount']);
        self::assertSame(31700000, $result['net_amount']);
        self::assertSame(9500, $result['credited_minutes']);
    }

    public function test_hourly_salary_includes_paid_leave_and_applies_overtime_multiplier(): void
    {
        $result = StaffPayrollCalculator::calculate([
            'compensation_type' => 'hourly',
            'hourly_rate' => 600000,
            'overtime_multiplier_basis_points' => 15000,
        ], 480, 480, 120, 60);

        self::assertSame(5400000, $result['base_pay']);
        self::assertSame(900000, $result['overtime_pay']);
        self::assertSame(6300000, $result['gross_amount']);
        self::assertSame(0, $result['net_amount'] - $result['gross_amount']);
        self::assertSame(600, $result['credited_minutes']);
    }

    public function test_negative_inputs_are_clamped_and_deductions_cannot_exceed_gross_pay(): void
    {
        $result = StaffPayrollCalculator::calculate([
            'compensation_type' => 'hourly',
            'hourly_rate' => 1000,
            'overtime_multiplier_basis_points' => 9000,
        ], -10, -20, -30, -40, -50, 999999);

        self::assertSame(0, $result['worked_minutes']);
        self::assertSame(0, $result['scheduled_minutes']);
        self::assertSame(0, $result['paid_leave_minutes']);
        self::assertSame(0, $result['overtime_minutes']);
        self::assertSame(0, $result['allowance_amount']);
        self::assertSame(0, $result['deduction_amount']);
        self::assertSame(0, $result['net_amount']);
    }

    public function test_monthly_pay_is_capped_at_contract_standard_minutes(): void
    {
        $result = StaffPayrollCalculator::calculate([
            'compensation_type' => 'monthly',
            'base_monthly_salary' => 24000000,
            'standard_monthly_minutes' => 9600,
            'overtime_multiplier_basis_points' => 15000,
        ], 12000, 9600, 0, 0);

        self::assertSame(24000000, $result['base_pay']);
        self::assertSame(24000000, $result['gross_amount']);
    }
}
