<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Support\StaffObligationCalculator;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

final class StaffObligationCalculatorTest extends TestCase
{
    public function test_installments_preserve_the_exact_principal_and_distribute_remainder(): void
    {
        $parts = StaffObligationCalculator::split(1001, 3);
        $this->assertSame([334,334,333], $parts);
        $this->assertSame(1001, array_sum($parts));
    }

    public function test_invalid_principal_or_installment_count_is_rejected(): void
    {
        $this->expectException(InvalidArgumentException::class);
        StaffObligationCalculator::split(0, 2);
    }
}
