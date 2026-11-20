<?php

declare(strict_types=1);

namespace Tests\Unit\Finance;

use App\Support\StaffObligationInstallmentSchedule;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

final class StaffObligationInstallmentScheduleTest extends TestCase
{
    public function test_installment_amounts_sum_exactly_to_principal_and_distribute_remainder(): void
    {
        $rows = StaffObligationInstallmentSchedule::build(1000, 3, '2026-11-20');

        self::assertSame([334, 333, 333], array_column($rows, 'amount'));
        self::assertSame(1000, array_sum(array_column($rows, 'amount')));
        self::assertSame([1, 2, 3], array_column($rows, 'sequence'));
    }

    public function test_month_end_due_dates_do_not_overflow_into_the_following_month(): void
    {
        $rows = StaffObligationInstallmentSchedule::build(300, 3, '2027-01-31');

        self::assertSame(['2027-01-31', '2027-02-28', '2027-03-31'], array_column($rows, 'due_date'));
    }

    public function test_schedule_rejects_zero_principal_and_out_of_range_installment_count(): void
    {
        try {
            StaffObligationInstallmentSchedule::build(0, 3, '2026-11-20');
            self::fail('A zero principal must be rejected.');
        } catch (InvalidArgumentException) {
            self::assertTrue(true);
        }

        $this->expectException(InvalidArgumentException::class);
        StaffObligationInstallmentSchedule::build(100, 61, '2026-11-20');
    }
}
