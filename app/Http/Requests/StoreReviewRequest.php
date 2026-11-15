<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreReviewRequest extends FormRequest
{
    /**
     * آیا کاربر مجاز به ثبت نظر است؟
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * قبل از اعتبارسنجی
     */
    protected function prepareForValidation(): void
    {
        $this->merge([

            'is_anonymous' => filter_var(
                $this->is_anonymous,
                FILTER_VALIDATE_BOOLEAN
            ),

        ]);
    }

    /**
     * قوانین اعتبارسنجی
     */
    public function rules(): array
    {
        return [

            'patient_name' => [

                'required',
                'string',
                'min:2',
                'max:100',

            ],

            'rating' => [

                'required',
                'integer',
                'between:1,5',

            ],

            'comment' => [

                'required',
                'string',
                'min:10',
                'max:2000',

            ],

            'is_anonymous' => [

                'nullable',
                'boolean',

            ],

        ];
    }

    /**
     * پیام‌های فارسی
     */
    public function messages(): array
    {
        return [

            'patient_name.required' =>
                'نام خود را وارد کنید.',

            'patient_name.min' =>
                'نام باید حداقل ۲ کاراکتر باشد.',

            'patient_name.max' =>
                'نام نمی‌تواند بیشتر از ۱۰۰ کاراکتر باشد.',

            'rating.required' =>
                'امتیاز خود را انتخاب کنید.',

            'rating.integer' =>
                'امتیاز نامعتبر است.',

            'rating.between' =>
                'امتیاز باید بین ۱ تا ۵ باشد.',

            'comment.required' =>
                'متن نظر را وارد کنید.',

            'comment.min' =>
                'نظر باید حداقل ۱۰ کاراکتر باشد.',

            'comment.max' =>
                'نظر نمی‌تواند بیشتر از ۲۰۰۰ کاراکتر باشد.',

            'is_anonymous.boolean' =>
                'مقدار ناشناس بودن نامعتبر است.',

        ];
    }

    /**
     * نام فیلدها
     */
    public function attributes(): array
    {
        return [

            'patient_name' => 'نام',

            'rating' => 'امتیاز',

            'comment' => 'نظر',

            'is_anonymous' => 'ارسال ناشناس',

        ];
    }
}