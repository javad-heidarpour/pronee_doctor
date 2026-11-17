<?php

namespace App\Http\Requests\Portal;

use Illuminate\Foundation\Http\FormRequest;

class StoreTimeSlotRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user('web');\n\n        return $user instanceof User && $user->is_active;
    }

    public function rules(): array
    {
        return [
            'clinic_id' => ['required', 'exists:addresses,id'],
            'date' => ['required', 'date', 'after_or_equal:today'],
            'start_time' => ['required', 'date_format:H:i'],
            'end_time' => ['required', 'date_format:H:i', 'after:start_time'],
        ];
    }

    public function messages(): array
    {
        return [
            'clinic_id.required' => 'انتخاب مطب الزامی است',
            'date.required' => 'انتخاب تاریخ الزامی است',
            'date.after_or_equal' => 'تاریخ نمی‌تواند در گذشته باشد',
            'start_time.required' => 'ساعت شروع الزامی است',
            'end_time.required' => 'ساعت پایان الزامی است',
            'end_time.after' => 'ساعت پایان باید بعد از ساعت شروع باشد',
        ];
    }
}
