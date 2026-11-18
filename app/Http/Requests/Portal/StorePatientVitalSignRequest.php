<?php

declare(strict_types=1);

namespace App\Http\Requests\Portal;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class StorePatientVitalSignRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user('web');

        return $user instanceof User && (bool) $user->is_active;
    }

    public function rules(): array
    {
        return [
            'measurement_type' => ['required', 'string', Rule::in(array_keys(RecordPatientVitalSignAction::UNITS))],
            'value' => ['required', 'numeric', 'between:0,1000'],
            'measured_at' => ['required', 'date', 'before_or_equal:now'],
            'note' => ['nullable', 'string', 'max:1000'],
        ];
    }
}
