<?php

declare(strict_types=1);

namespace Tests\Unit\Finance;

use App\Services\Finance\ClinicRevenueOptimizer;
use PHPUnit\Framework\TestCase;

class ClinicRevenueOptimizerTest extends TestCase
{
    private ClinicRevenueOptimizer $optimizer;

    protected function setUp(): void
    {
        parent::setUp();
        $this->optimizer = new ClinicRevenueOptimizer();
    }

    public function test_it_calculates_capacity_and_cancellation_rates_from_real_counts(): void
    {
        $result = $this->optimizer->summarize(
            totalSlots: 20,
            occupiedSlots: 12,
            statuses: ['visited' => 8, 'cancelled' => 2, 'pending' => 1, 'confirmed' => 1],
            paidRevenue: 2400000,
            paidAppointments: 4,
        );

        self::assertSame(60.0, $result['capacity']['utilization_rate']);
        self::assertSame(8, $result['capacity']['available_slots']);
        // Two cancellations out of twelve total appointments = 16.7%.
        self::assertSame(16.7, $result['appointments']['cancellation_rate']);
        self::assertSame(600000, $result['revenue']['average_paid_amount']);
    }

    public function test_it_does_not_invent_rates_or_average_when_source_data_is_missing(): void
    {
        $result = $this->optimizer->summarize(0, 0, [], 0, 0);

        self::assertNull($result['capacity']['utilization_rate']);
        self::assertNull($result['appointments']['cancellation_rate']);
        self::assertNull($result['revenue']['average_paid_amount']);
        self::assertSame('insufficient_data', $result['capacity']['data_status']);
        self::assertSame('insufficient_data', $result['appointments']['data_status']);
        self::assertSame('insufficient_data', $result['revenue']['data_status']);
    }

    public function test_occupied_slots_never_exceed_total_capacity(): void
    {
        $result = $this->optimizer->summarize(5, 8, ['visited' => 8], 0, 0);

        self::assertSame(100.0, $result['capacity']['utilization_rate']);
        self::assertSame(0, $result['capacity']['available_slots']);
    }
}
