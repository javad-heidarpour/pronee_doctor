<?php

declare(strict_types=1);

namespace App\Services\ClinicBrain;

final class WaitlistOfferEligibility
{
    public function matches(
        string $status,
        bool $contactConsent,
        ?int $preferredClinicId,
        int $slotClinicId,
        string $preferredFrom,
        string $preferredUntil,
        string $slotDate,
        string $preferredPeriod,
        string $slotStartTime
    ): bool {
        if ($status !== 'waiting' || !$contactConsent) return false;
        if ($preferredClinicId !== null && $preferredClinicId !== $slotClinicId) return false;
        if ($slotDate < $preferredFrom || $slotDate > $preferredUntil) return false;

        $hour = (int) substr($slotStartTime, 0, 2);

        return match ($preferredPeriod) {
            'morning' => $hour < 12,
            'afternoon' => $hour >= 12,
            'any' => true,
            default => false,
        };
    }
}
