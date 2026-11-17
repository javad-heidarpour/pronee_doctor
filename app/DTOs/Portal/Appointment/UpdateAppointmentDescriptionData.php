<?php

declare(strict_types=1);

namespace App\DTOs\Portal\Appointment;

final readonly class UpdateAppointmentDescriptionData
{
    public function __construct(
        public ?string $description,
        public ?int $surgeryTypeId,
        public ?string $newSurgeryType,
        public ?string $surgeryDate,
    ) {
    }

    public static function fromValidated(array $data): self
    {
        return new self(
            description: isset($data['description'])
                ? (string) $data['description']
                : null,
            surgeryTypeId: isset($data['surgery_type_id'])
                ? (int) $data['surgery_type_id']
                : null,
            newSurgeryType: isset($data['new_surgery_type'])
                ? (string) $data['new_surgery_type']
                : null,
            surgeryDate: isset($data['surgery_date'])
                ? (string) $data['surgery_date']
                : null,
        );
    }
}
