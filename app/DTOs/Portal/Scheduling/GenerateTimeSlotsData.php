<?php

declare(strict_types=1);

namespace App\DTOs\Portal\Scheduling;

final readonly class GenerateTimeSlotsData
{
    public function __construct(
        public int $weeksCount,
        public ?int $clinicId,
    ) {
    }

    public static function fromValidated(array $data): self
    {
        $clinicId = $data['clinic_id'] ?? null;

        return new self(
            weeksCount: (int) $data['weeks_count'],
            clinicId: $clinicId !== null && $clinicId !== ''
                ? (int) $clinicId
                : null,
        );
    }
}
