<?php

declare(strict_types=1);

namespace App\Http\Requests\Profile;

use Illuminate\Foundation\Http\FormRequest;

class DeleteProfileRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user('web')?->doctorProfile !== null;
    }

    public function rules(): array
    {
        return [
            'password' => ['required', 'current_password:web'],
        ];
    }
}
