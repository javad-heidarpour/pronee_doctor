<?php

declare(strict_types=1);

namespace App\Services;

use InvalidArgumentException;

final class ClinicProfessionalSettlementCalculator
{
    /**
     * Split the net service revenue using integer rial arithmetic.
     *
     * @return array{professional:int, clinic:int}
     */
    public function split(string $model, int $netAmount, int $professionalShareBps, int $fixedFeeRial): array
    {
        if ($netAmount < 0 || $fixedFeeRial < 0 || $professionalShareBps < 0 || $professionalShareBps > 10000) {
            throw new InvalidArgumentException('Invalid partnership settlement inputs.');
        }

        $professional = match ($model) {
            'percentage' => intdiv($netAmount * $professionalShareBps, 10000),
            'fixed_per_session' => min($netAmount, $fixedFeeRial),
            'hybrid' => min($netAmount, intdiv($netAmount * $professionalShareBps, 10000) + $fixedFeeRial),
            default => throw new InvalidArgumentException('This partnership model is not settled per session.'),
        };

        return [
            'professional' => $professional,
            'clinic' => $netAmount - $professional,
        ];
    }

    public function payableAmount(array $shares, string $paymentDirection): int
    {
        if (!in_array($paymentDirection, ['clinic_to_professional', 'professional_to_clinic'], true)) {
            throw new InvalidArgumentException('Invalid payment direction.');
        }

        return $paymentDirection === 'clinic_to_professional'
            ? (int) ($shares['professional'] ?? 0)
            : (int) ($shares['clinic'] ?? 0);
    }
}
