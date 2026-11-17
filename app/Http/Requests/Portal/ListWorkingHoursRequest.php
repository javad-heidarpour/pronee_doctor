<?php

namespace App\Http\Requests\Portal;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ListWorkingHoursRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user('web');

        return $user instanceof User && $user->is_active;
    }

    public function rules(): array
    {
        return [
            'clinic_id' => ['sometimes', 'nullable', 'integer', 'exists:addresses,id'],
            'is_active' => ['sometimes', 'nullable', Rule::in(['true', 'false', '1', '0', 1, 0])],
            'search' => ['sometimes', 'nullable', 'string', 'max:100'],
            'sort_by' => ['sometimes', 'nullable', Rule::in([
                'day_of_week',
                'start_time',
                'end_time',
                'duration_minutes',
                'is_active',
                'clinic_id',
                'created_at',
            ])],
            'sort_dir' => ['sometimes', 'nullable', Rule::in(['asc', 'desc'])],
        ];
    }
}
