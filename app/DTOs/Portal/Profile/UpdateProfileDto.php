<?php

declare(strict_types=1);

namespace App\DTOs\Portal\Profile;

use App\Http\Requests\Profile\UpdateProfileRequest;
use App\Models\Doctor;
use Illuminate\Http\UploadedFile;

final readonly class UpdateProfileDto
{
    public function __construct(
        public Doctor $doctor,
        public string $name,
        public string $family,
        public string $email,
        public ?string $phone,
        public ?string $caption,
        public ?string $specialty_title,
        public ?int $years_of_experience,
        public ?string $Medical_system_number,
        public ?string $description,
        public ?string $meta_title,
        public ?string $meta_description,
        public ?UploadedFile $photo = null,
    ) {
    }

    public static function fromRequest(UpdateProfileRequest $request): self
    {
        return new self(
            doctor: auth('web')->user()->doctorProfile,
            name: $request->string('name')->toString(),
            family: $request->string('family')->toString(),
            email: $request->string('email')->toString(),
            phone: $request->input('phone'),
            caption: $request->input('caption'),
            specialty_title: $request->input('specialty_title'),
            years_of_experience: $request->filled('years_of_experience')
                ? (int) $request->input('years_of_experience')
                : null,
            Medical_system_number: $request->input('Medical_system_number'),
            description: $request->input('description'),
            meta_title: $request->input('meta_title'),
            meta_description: $request->input('meta_description'),
            photo: $request->file('photo'),
        );
    }

    public function toArray(): array
    {
        return [
            'name' => $this->name,
            'family' => $this->family,
            'email' => $this->email,
            'phone' => $this->phone,
            'caption' => $this->caption,
            'specialty_title' => $this->specialty_title,
            'years_of_experience' => $this->years_of_experience,
            'Medical_system_number' => $this->Medical_system_number,
            'description' => $this->description,
            'meta_title' => $this->meta_title,
            'meta_description' => $this->meta_description,
        ];
    }
}