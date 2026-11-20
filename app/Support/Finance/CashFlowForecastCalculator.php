<?php

declare(strict_types=1);

namespace App\Support\Finance;

final class CashFlowForecastCalculator
{
    /**
     * @param array<string, array{inflows:int|float,outflows:int|float}> $movements
     * @return array{starting_balance:int,ending_balance:int,minimum_balance:int,first_deficit_date:?string,total_inflows:int,total_outflows:int,days:array<int,array<string,int|string>>}
     */
    public function project(int $startingBalance, array $movements, int $days, float $collectionFactor = 1.0): array
    {
        $days = max(0, $days);
        $collectionFactor = max(0.0, min(1.0, $collectionFactor));
        $movements = array_slice($movements, 0, $days, true);

        $balance = $startingBalance;
        $minimum = $balance;
        $firstDeficit = $balance < 0 ? array_key_first($movements) : null;
        $totalInflows = 0;
        $totalOutflows = 0;
        $rows = [];

        foreach ($movements as $date => $movement) {
            $inflows = (int) round(max(0, (float) $movement['inflows']) * $collectionFactor);
            $outflows = (int) max(0, (float) $movement['outflows']);
            $balance += $inflows - $outflows;
            $totalInflows += $inflows;
            $totalOutflows += $outflows;
            $minimum = min($minimum, $balance);
            if ($firstDeficit === null && $balance < 0) {
                $firstDeficit = $date;
            }
            $rows[] = [
                'date' => $date,
                'inflows' => $inflows,
                'outflows' => $outflows,
                'net' => $inflows - $outflows,
                'closing_balance' => $balance,
            ];
        }

        return [
            'starting_balance' => $startingBalance,
            'ending_balance' => $balance,
            'minimum_balance' => $minimum,
            'first_deficit_date' => $firstDeficit,
            'total_inflows' => $totalInflows,
            'total_outflows' => $totalOutflows,
            'days' => $rows,
        ];
    }
}
