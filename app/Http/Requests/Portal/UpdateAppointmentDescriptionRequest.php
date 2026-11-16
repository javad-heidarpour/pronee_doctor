<?php

namespace App\Http\Requests\Portal;

use Illuminate\Foundation\Http\FormRequest;

class UpdateAppointmentDescriptionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'description' => ['nullable', 'string', 'max:1000'],
            'surgery_type_id' => ['nullable', 'exists:surgery_types,id'],
            'new_surgery_type' => ['nullable', 'string', 'max:255'],
            'surgery_date' => ['nullable', 'date'],
        ];
    }
}
