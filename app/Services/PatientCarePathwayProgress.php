<?php

declare(strict_types=1);

namespace App\Services;

use Illuminate\Support\Collection;

final class PatientCarePathwayProgress
{
    public function canActivate(string $status, int $stepCount): bool
    {
        return $status === 'draft' && $stepCount > 0;
    }

    public function isComplete(Collection $steps): bool
    {
        return $steps->isNotEmpty() && $steps->every(fn ($step) => data_get($step, 'status') === 'verified');
    }
}
