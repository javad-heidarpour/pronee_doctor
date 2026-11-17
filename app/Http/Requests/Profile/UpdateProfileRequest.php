<?php

declare(strict_types=1);

namespace App\Http\Requests\Profile;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateProfileRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user('web')?->doctorProfile !== null;
    }

    public function rules(): array
    {
        $doctor = $this->user('web')?->doctorProfile;

        return [
            'name' => ['required', 'string', 'max:255'],
            'family' => ['required', 'string', 'max:255'],
            'email' => [
                'required',
                'email',
                'max:255',
                Rule::unique('doctors', 'email')->ignore($doctor?->getKey()),
            ],
            'phone' => ['nullable', 'string', 'max:20'],
            'caption' => ['nullable', 'string', 'max:255'],
            'specialty_title' => ['required', 'string', 'max:255'],
            'years_of_experience' => ['required', 'integer', 'min:0'],
            'Medical_system_number' => ['required', 'string', 'max:50'],
            'description' => ['nullable', 'string'],
            'photo' => ['nullable', 'image', 'max:2048'],
        ];
    }
}
