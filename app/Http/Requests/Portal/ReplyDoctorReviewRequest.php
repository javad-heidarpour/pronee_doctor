<?php

declare(strict_types=1);

namespace App\Http\Requests\Portal;

use Illuminate\Foundation\Http\FormRequest;

class ReplyDoctorReviewRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user('web')?->isDoctor() ?? false;
    }

    public function rules(): array
    {
        return [
            'doctor_reply' => ['required', 'string', 'min:3', 'max:2000'],
        ];
    }

    public function messages(): array
    {
        return [
            'doctor_reply.required' => 'متن پاسخ پزشک الزامی است.',
            'doctor_reply.min' => 'پاسخ باید حداقل ۳ کاراکتر باشد.',
            'doctor_reply.max' => 'متن پاسخ نباید بیشتر از ۲۰۰۰ کاراکتر باشد.',
        ];
    }
}
