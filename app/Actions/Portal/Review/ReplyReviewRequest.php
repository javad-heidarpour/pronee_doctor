<?php

namespace App\Actions\Portal\Review;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;

class ReplyReviewRequest extends FormRequest
{
    public function authorize(): bool
    {
        return Auth::guard('web')->check();
    }

    public function rules(): array
    {
        return [
            'doctor_reply' => [
                'required',
                'string',
                'min:3',
                'max:5000',
            ],
        ];
    }

    public function messages(): array
    {
        return [

            'doctor_reply.required' => 'پاسخ پزشک را وارد کنید.',

            'doctor_reply.min' => 'پاسخ باید حداقل ۳ کاراکتر باشد.',

            'doctor_reply.max' => 'پاسخ نمی‌تواند بیشتر از ۵۰۰۰ کاراکتر باشد.',

        ];
    }
}