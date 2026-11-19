<?php

declare(strict_types=1);

namespace App\Support;

use InvalidArgumentException;

final class StaffObligationCalculator
{
    /** @return list<int> */
    public static function split(int $principal, int $installments): array
    {
        if ($principal < 1 || $installments < 1 || $installments > 60) {
            throw new InvalidArgumentException('Principal and installment count are out of range.');
        }
        $base = intdiv($principal, $installments);
        $remainder = $principal % $installments;
        $amounts = [];
        for ($i = 0; $i < $installments; $i++) {
            $amounts[] = $base + ($i < $remainder ? 1 : 0);
        }
        return $amounts;
    }
}
