<?php

declare(strict_types=1);

namespace Tests\Unit\Finance;

use App\Support\Finance\CashFlowForecastCalculator;
use PHPUnit\Framework\TestCase;

final class CashFlowForecastCalculatorTest extends TestCase
{
    public function test_it_projects_daily_closing_balances_and_collection_scenarios(): void
    {
        $calculator = new CashFlowForecastCalculator();
        $movements = [
            '2026-11-20' => ['inflows' => 1000, 'outflows' => 400],
            '2026-11-21' => ['inflows' => 500, 'outflows' => 900],
        ];

        $result = $calculator->project(500, $movements, 2, 0.5);

        self::assertSame(500, $result['starting_balance']);
        self::assertSame(-50, $result['ending_balance']);
        self::assertSame(-50, $result['minimum_balance']);
        self::assertSame(750, $result['total_inflows']);
        self::assertSame(1300, $result['total_outflows']);
        self::assertSame('2026-11-21', $result['first_deficit_date']);
        self::assertSame(-50, $result['days'][1]['closing_balance']);
    }

    public function test_it_identifies_the_first_day_cash_becomes_negative(): void
    {
        $result = (new CashFlowForecastCalculator())->project(100, [
            '2026-11-20' => ['inflows' => 0, 'outflows' => 150],
            '2026-11-21' => ['inflows' => 100, 'outflows' => 0],
        ], 2);

        self::assertSame('2026-11-20', $result['first_deficit_date']);
        self::assertSame(-50, $result['minimum_balance']);
        self::assertSame(50, $result['ending_balance']);
    }
}
