<?php

declare(strict_types=1);

namespace App\DTOs\Portal\Scheduling;

final readonly class WorkingHourData
{
    public function __construct(
        public int $clinicId,
        public int $dayOfWeek,
        public int $durationMinutes,
        public string $startTime,
        public string $endTime,
    ) {
    }

    public static function fromValidated(array $data): self
    {
        return new self(
            clinicId: (int) $data['clinic_id'],
            dayOfWeek: (int) $data['day_of_week'],
            durationMinutes: (int) $data['duration_minutes'],
            startTime: (string) $data['start_time'],
            endTime: (string) $data['end_time'],
        );
    }

    public function toArray(): array
    {
        return [
            'clinic_id' => $this->clinicId,
            'day_of_week' => $this->dayOfWeek,
            'duration_minutes' => $this->durationMinutes,
            'start_time' => $this->startTime,
            'end_time' => $this->endTime,
        ];
    }
}
