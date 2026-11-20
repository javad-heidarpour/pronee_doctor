<?php

declare(strict_types=1);

namespace App\Support;

use Carbon\CarbonImmutable;
use InvalidArgumentException;

final class StaffObligationInstallmentSchedule
{
    /**
     * Split a principal amount into exact integer installments.
     * Any remainder is distributed one rial at a time to the earliest installments.
     *
     * @return array<int, array{sequence:int,due_date:string,amount:int}>
     */
    public static function build(int $principal, int $count, string $firstDueDate): array
    {
        if ($principal < 1) {
            throw new InvalidArgumentException('Principal must be greater than zero.');
        }

        if ($count < 1 || $count > 60) {
            throw new InvalidArgumentException('Installment count must be between 1 and 60.');
        }

        $firstDue = CarbonImmutable::parse($firstDueDate)->startOfDay();
        $base = intdiv($principal, $count);
        $remainder = $principal % $count;
        $installments = [];

        for ($sequence = 1; $sequence <= $count; $sequence++) {
            $installments[] = [
                'sequence' => $sequence,
                'due_date' => $firstDue->addMonthsNoOverflow($sequence - 1)->toDateString(),
                'amount' => $base + ($sequence <= $remainder ? 1 : 0),
            ];
        }

        return $installments;
    }
}
