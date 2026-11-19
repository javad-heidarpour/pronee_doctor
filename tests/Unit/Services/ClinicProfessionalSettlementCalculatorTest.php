<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\Services\ClinicProfessionalSettlementCalculator;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

final class ClinicProfessionalSettlementCalculatorTest extends TestCase
{
    private ClinicProfessionalSettlementCalculator $calculator;

    protected function setUp(): void
    {
        parent::setUp();
        $this->calculator = new ClinicProfessionalSettlementCalculator();
    }

    public function test_percentage_split_uses_integer_rial_arithmetic(): void
    {
        self::assertSame(
            ['professional' => 700000, 'clinic' => 300000],
            $this->calculator->split('percentage', 1000000, 7000, 0)
        );
    }

    public function test_fixed_fee_cannot_exceed_net_revenue(): void
    {
        self::assertSame(
            ['professional' => 800000, 'clinic' => 0],
            $this->calculator->split('fixed_per_session', 800000, 7000, 1200000)
        );
    }

    public function test_hybrid_split_is_capped_at_net_revenue(): void
    {
        self::assertSame(
            ['professional' => 1000000, 'clinic' => 0],
            $this->calculator->split('hybrid', 1000000, 7000, 500000)
        );
    }

    public function test_payment_direction_selects_the_correct_receivable(): void
    {
        $shares = ['professional' => 700000, 'clinic' => 300000];

        self::assertSame(700000, $this->calculator->payableAmount($shares, 'clinic_to_professional'));
        self::assertSame(300000, $this->calculator->payableAmount($shares, 'professional_to_clinic'));
    }

    public function test_unsupported_models_are_rejected(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->calculator->split('monthly_fixed', 1000000, 7000, 0);
    }
}
