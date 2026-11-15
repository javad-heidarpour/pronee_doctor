<?php

namespace App\Http\Requests\Portal;

use Illuminate\Foundation\Http\FormRequest;

class ReplyDoctorReviewRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }


    public function rules(): array
    {
        return [

            'doctor_reply' => [
                'required',
                'string',
                'max:2000',
            ],

        ];
    }


    public function messages(): array
    {
        return [

            'doctor_reply.required' =>
                'متن پاسخ پزشک الزامی است.',

            'doctor_reply.max' =>
                'متن پاسخ نباید بیشتر از ۲۰۰۰ کاراکتر باشد.',

        ];
    }
}