<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SendDoctorRegistrationOtpRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'phone' => [
                'required',
                'regex:/^09\d{9}$/',
                Rule::unique('users', 'phone'),
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'phone.required' => 'شماره موبایل را وارد کنید.',
            'phone.regex' => 'شماره موبایل معتبر نیست.',
            'phone.unique' => 'این شماره موبایل قبلاً ثبت شده است.',
        ];
    }
}
