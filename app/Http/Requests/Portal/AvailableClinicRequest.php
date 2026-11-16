<?php

namespace App\\Http\\Requests\\Portal;

use Illuminate\\Foundation\\Http\\FormRequest;

class AvailableClinicRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'clinic_id' => ['required', 'integer', 'min:1'],
        ];
    }
}
