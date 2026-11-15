<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreCommentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [

            'name' => [
                'required',
                'string',
                'max:100'
            ],

            'email' => [
                'nullable',
                'email',
                'max:255'
            ],

            'comment' => [
                'required',
                'string',
                'min:10',
                'max:3000'
            ],

            'rating' => [
                'required',
                'integer',
                'between:1,5'
            ],

        ];
    }

    public function messages(): array
    {
        return [

            'name.required' => 'نام خود را وارد کنید.',

            'comment.required' => 'متن نظر را وارد کنید.',

            'comment.min' => 'نظر باید حداقل ۱۰ کاراکتر باشد.',

            'rating.required' => 'امتیاز را انتخاب کنید.',

            'rating.between' => 'امتیاز بین ۱ تا ۵ است.',

        ];
    }
}