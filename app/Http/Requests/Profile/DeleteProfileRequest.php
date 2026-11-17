<?php

declare(strict_types=1);

namespace App\Http\Requests\Profile;

use Illuminate\Foundation\Http\FormRequest;

class DeleteProfileRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user('web');

        return $user !== null
            && $user->isDoctor()
            && $user->doctorProfile !== null;
    }

    public function rules(): array
    {
        return [
            'password' => ['required', 'current_password:web'],
        ];
    }
}
