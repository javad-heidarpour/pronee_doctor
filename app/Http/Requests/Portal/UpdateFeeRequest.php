<?php

namespace App\Http\Requests\Portal;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;

class UpdateFeeRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user('web');

        return $user instanceof User && $user->is_active;
    }

    public function rules(): array
    {
        return [
            'fee_rial' => [
                'required',
                'integer',
                'min:1',
                'max:999999999999',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'fee_rial.required' => 'مبلغ تعرفه را وارد کنید.',
            'fee_rial.integer' => 'مبلغ تعرفه باید عدد صحیح باشد.',
            'fee_rial.min' => 'مبلغ تعرفه باید بیشتر از صفر باشد.',
            'fee_rial.max' => 'مبلغ واردشده بیش از حد مجاز است.',
        ];
    }
}
