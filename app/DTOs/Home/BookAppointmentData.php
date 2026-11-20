<?php

declare(strict_types=1);

namespace App\DTOs\Home;

final readonly class BookAppointmentData
{
    public function __construct(
        public int $timeSlotId,
        public string $patientName,
        public string $patientPhone,
        public ?string $patientNationalCode,
        public ?string $description,
        public ?int $visitReasonId,
        public ?string $newVisitReason,
        public ?string $discountCode = null,
    ) {
    }

    public static function fromValidated(array $data): self
    {
        return new self(
            timeSlotId: (int) $data['time_slot_id'],
            patientName: (string) $data['patient_name'],
            patientPhone: (string) $data['patient_phone'],
            patientNationalCode: isset($data['patient_national_code'])
                ? (string) $data['patient_national_code']
                : null,
            description: isset($data['description'])
                ? (string) $data['description']
                : null,
            visitReasonId: isset($data['visit_reason_id'])
                ? (int) $data['visit_reason_id']
                : null,
            newVisitReason: isset($data['new_visit_reason'])
                ? (string) $data['new_visit_reason']
                : null,
            discountCode: isset($data['discount_code']) && trim((string) $data['discount_code']) !== ''
                ? strtoupper(trim((string) $data['discount_code']))
                : null,
        );
    }
}
