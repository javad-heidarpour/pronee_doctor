<?php

declare(strict_types=1);

namespace App\DTOs\Auth;

final readonly class RegisterDoctorData
{
    public function __construct(
        public string $name,
        public string $family,
        public int $cityId,
        public int $specialtyId,
        public string $slug,
        public string $phone,
        public string $email,
        public ?string $medicalSystemNumber,
    ) {
    }

    public static function fromValidated(array $data, string $normalizedPhone): self
    {
        return new self(
            name: $data['name'],
            family: $data['family'],
            cityId: (int) $data['city_id'],
            specialtyId: (int) $data['specialty_id'],
            slug: $data['slug'],
            phone: $normalizedPhone,
            email: mb_strtolower($data['email']),
            medicalSystemNumber: $data['medical_system_number'] ?? null,
        );
    }
}
