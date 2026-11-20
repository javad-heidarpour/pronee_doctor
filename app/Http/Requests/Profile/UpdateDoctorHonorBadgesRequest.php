<?php

declare(strict_types=1);

namespace App\Http\Requests\Profile;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;

final class UpdateDoctorHonorBadgesRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user('web');

        return $user instanceof User
            && $user->isDoctor()
            && $user->doctorProfile !== null;
    }

    public function rules(): array
    {
        return [
            'honor_badges' => ['nullable', 'array', 'max:20'],
            'honor_badges.*.title' => ['required', 'string', 'max:255'],
            'honor_badges.*.icon' => ['nullable', 'string', 'max:24'],
        ];
    }

    public function messages(): array
    {
        return [
            'honor_badges.array' => 'ساختار سوابق حرفه‌ای معتبر نیست.',
            'honor_badges.max' => 'حداکثر ۲۰ سابقه یا افتخار قابل ثبت است.',
            'honor_badges.*.title.required' => 'عنوان هر سابقه را وارد کنید.',
            'honor_badges.*.title.max' => 'عنوان سابقه نباید بیشتر از ۲۵۵ نویسه باشد.',
            'honor_badges.*.icon.max' => 'نماد سابقه نباید بیشتر از ۲۴ نویسه باشد.',
        ];
    }
}
