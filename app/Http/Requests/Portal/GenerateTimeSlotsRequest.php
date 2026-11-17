<?php

namespace App\Http\Requests\Portal;

use Illuminate\Foundation\Http\FormRequest;

class GenerateTimeSlotsRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user('web');\n\n        return $user instanceof User && $user->is_active;
    }

    public function rules(): array
    {
        return [
            'weeks_count' => ['required', 'integer', 'min:1', 'max:52'],
            'clinic_id' => auth('web')->user()?->role === 'secretary'
                ? ['required', 'exists:addresses,id']
                : ['nullable', 'exists:addresses,id'],
        ];
    }

    public function messages(): array
    {
        return [
            'weeks_count.required' => 'تعداد هفته‌ها الزامی است',
            'weeks_count.min' => 'حداقل ۱ هفته',
            'weeks_count.max' => 'حداکثر ۵۲ هفته (یک سال)',
        ];
    }
}
