<?php

declare(strict_types=1);

namespace App\Services\PopulationHealth;

final class PopulationHealthDisclosure
{
    public const MINIMUM_COHORT_SIZE = 5;

    public function canDisclose(int $distinctPatients): bool
    {
        return $distinctPatients >= self::MINIMUM_COHORT_SIZE;
    }
}
