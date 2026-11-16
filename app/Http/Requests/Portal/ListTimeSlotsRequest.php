<?php

namespace App\Http\Requests\Portal;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ListTimeSlotsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'clinic_id' => ['nullable', 'integer', 'min:1'],
            'status' => ['nullable', Rule::in(['available', 'booked', 'cancelled'])],
            'date_from' => ['nullable', 'date'],
            'date_to' => ['nullable', 'date', 'after_or_equal:date_from'],
            'search' => ['nullable', 'string', 'max:100'],
            'sort_by' => [
                'nullable',
                Rule::in(['date', 'start_time', 'end_time', 'status', 'created_at']),
            ],
            'sort_dir' => ['nullable', Rule::in(['asc', 'desc'])],
        ];
    }
}
