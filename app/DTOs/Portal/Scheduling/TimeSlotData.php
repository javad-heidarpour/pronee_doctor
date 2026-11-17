<?php

declare(strict_types=1);

namespace App\DTOs\Portal\Scheduling;

final readonly class TimeSlotData
{
    public function __construct(
        public int $clinicId,
        public string $date,
        public string $startTime,
        public string $endTime,
    ) {
    }

    public static function fromValidated(array $data): self
    {
        return new self(
            clinicId: (int) $data['clinic_id'],
            date: (string) $data['date'],
            startTime: (string) $data['start_time'],
            endTime: (string) $data['end_time'],
        );
    }
}
