<?php

declare(strict_types=1);

namespace App\DTOs\Auth;

final readonly class RegisterPublicUserData
{
    public function __construct(
        public string $name,
        public string $family,
        public string $email,
        public string $password,
    ) {
    }

    public static function fromValidated(array $data): self
    {
        return new self(
            name: $data['name'],
            family: $data['family'],
            email: mb_strtolower($data['email']),
            password: $data['password'],
        );
    }
}
