<?php

namespace App\Http\Requests\Portal;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;

class UpdateAppointmentDescriptionRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user('web');

        return $user instanceof User && $user->is_active;
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
