<?php

namespace App\Http\Requests\Profile;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;

class UpdateProfilePasswordRequest extends FormRequest
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
            'current_password' => ['required', 'string'],
            'password' => [
                'required',
                'string',
                'confirmed',
                Password::defaults(),
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'current_password.required' => 'رمز عبور فعلی الزامی است.',
            'password.required' => 'رمز عبور جدید الزامی است.',
            'password.confirmed' => 'تکرار رمز عبور جدید مطابقت ندارد.',
        ];
    }
}
