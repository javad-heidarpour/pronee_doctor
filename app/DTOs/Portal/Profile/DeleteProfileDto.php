<?php

declare(strict_types=1);

namespace App\DTOs\Portal\Profile;

use App\Http\Requests\Profile\DeleteProfileRequest;
use App\Models\Doctor;

final readonly class DeleteProfileDto
{
    public function __construct(
        public Doctor $doctor,
        public string $password,
    ) {
    }

    public static function fromRequest(DeleteProfileRequest $request): self
    {
        return new self(
            doctor: auth('web')->user(),
            password: $request->string('password')->toString(),
        );
    }
}