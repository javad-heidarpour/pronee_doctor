<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Support\StaffPayrollCalculator;
use PHPUnit\Framework\TestCase;

final class StaffPayrollCalculatorTest extends TestCase
{
    public function test_monthly_pay_is_prorated_and_paid_leave_is_credited(): void
    {
        $result = StaffPayrollCalculator::calculate([
            'compensation_type'=>'monthly',
            'base_monthly_salary'=>30000000,
            'standard_monthly_minutes'=>10000,
            'overtime_multiplier_basis_points'=>15000,
        ], 8000, 10000, 1000, 0);

        $this->assertSame(27000000, $result['base_pay']);
        $this->assertSame(9000, $result['credited_minutes']);
        $this->assertSame(27000000, $result['gross_amount']);
    }

    public function test_hourly_pay_adds_overtime_at_contract_multiplier(): void
    {
        $result = StaffPayrollCalculator::calculate([
            'compensation_type'=>'hourly',
            'hourly_rate'=>600000,
            'overtime_multiplier_basis_points'=>15000,
        ], 480, 420, 0, 60, 100000, 50000);

        $this->assertSame(4800000, $result['base_pay']);
        $this->assertSame(900000, $result['overtime_pay']);
        $this->assertSame(5800000, $result['gross_amount']);
        $this->assertSame(5750000, $result['net_amount']);
    }

    public function test_deductions_are_capped_at_gross_and_values_never_become_negative(): void
    {
        $result = StaffPayrollCalculator::calculate([
            'compensation_type'=>'monthly',
            'base_monthly_salary'=>1000000,
            'standard_monthly_minutes'=>10000,
        ], 0, 0, 0, 0, 0, 5000000);

        $this->assertSame(0, $result['gross_amount']);
        $this->assertSame(0, $result['deduction_amount']);
        $this->assertSame(0, $result['net_amount']);
    }

    public function test_overtime_multiplier_cannot_reduce_hourly_rate(): void
    {
        $result = StaffPayrollCalculator::calculate([
            'compensation_type'=>'hourly',
            'hourly_rate'=>600000,
            'overtime_multiplier_basis_points'=>5000,
        ], 0, 0, 0, 60);

        $this->assertSame(600000, $result['overtime_pay']);
    }
}
