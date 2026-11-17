<?php

namespace App\Http\Requests\Portal;

use Illuminate\Foundation\Http\FormRequest;

class UpdateTimeSlotRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user('web');\n\n        return $user instanceof User && $user->is_active;
    }

    public function rules(): array
    {
        return [
            'clinic_id' => ['required', 'exists:addresses,id'],
            'date' => ['required', 'date'],
            'start_time' => ['required', 'date_format:H:i'],
            'end_time' => ['required', 'date_format:H:i', 'after:start_time'],
        ];
    }
}
