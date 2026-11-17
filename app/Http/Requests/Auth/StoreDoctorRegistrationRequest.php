<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreDoctorRegistrationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'family' => ['required', 'string', 'max:255'],
            'phone' => ['required', 'regex:/^09\d{9}$/'],
            'email' => [
                'required',
                'string',
                'lowercase',
                'email',
                'max:255',
                Rule::unique('users', 'email'),
                Rule::unique('doctors', 'email'),
            ],
            'medical_system_number' => ['nullable', 'string', 'max:50'],
            'specialty_id' => [
                'required',
                Rule::exists('specialties', 'id')->where('is_active', 1),
            ],
            'city_id' => [
                'required',
                Rule::exists('cities', 'id')->where('is_active', 1),
            ],
            'slug' => [
                'required',
                'string',
                'max:255',
                'alpha_dash',
                'unique:doctors,slug',
            ],
            'registration_token' => ['required', 'string'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'نام پزشک الزامی است.',
            'family.required' => 'نام خانوادگی الزامی است.',
            'email.unique' => 'این ایمیل قبلاً ثبت شده است.',
            'specialty_id.required' => 'تخصص را انتخاب کنید.',
            'city_id.required' => 'شهر را انتخاب کنید.',
            'slug.unique' => 'این آدرس سایت قبلاً استفاده شده است.',
        ];
    }
}
