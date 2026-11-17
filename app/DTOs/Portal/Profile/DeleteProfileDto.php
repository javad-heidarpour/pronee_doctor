<?php

declare(strict_types=1);

namespace App\DTOs\Portal\Profile;

use App\Http\Requests\Profile\DeleteProfileRequest;
use App\Models\Doctor;
use Illuminate\Validation\ValidationException;

final readonly class DeleteProfileDto
{
    public function __construct(
        public Doctor $doctor,
    ) {
    }

    public static function fromRequest(DeleteProfileRequest $request): self
    {
        $doctor = $request->user('web')?->doctorProfile;

        if (! $doctor instanceof Doctor) {
            throw ValidationException::withMessages([
                'profile' => 'پروفایل پزشک برای این حساب کاربری یافت نشد.',
            ]);
        }

        return new self(doctor: $doctor);
    }
}
