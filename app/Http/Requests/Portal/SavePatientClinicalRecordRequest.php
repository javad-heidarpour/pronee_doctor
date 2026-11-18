<?php

declare(strict_types=1);

namespace App\Http\Requests\Portal;

use App\Models\PatientClinicalRecord;
use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class SavePatientClinicalRecordRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user('web');

        return $user instanceof User && (bool) $user->is_active;
    }

    public function rules(): array
    {
        return [
            'kind' => ['required', Rule::in(PatientClinicalRecord::KINDS)],
            'title' => ['required', 'string', 'max:180'],
            'description' => ['nullable', 'string', 'max:5000'],
            'dosage' => ['nullable', 'string', 'max:120'],
            'frequency' => ['nullable', 'string', 'max:120'],
            'start_date' => ['nullable', 'date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
            'is_active' => ['sometimes', 'boolean'],
            'related_allergy_record_id' => [
                'nullable',
                'integer',
                'exclude_unless:kind,adverse_reaction',
                Rule::exists('patient_clinical_records', 'id')
                    ->where('patient_id', $this->route('patient')?->id)
                    ->where('kind', PatientClinicalRecord::KIND_ALLERGY),
            ],
            'related_record_id' => [
                'nullable',
                'integer',
                'exclude_unless:kind,adverse_reaction',
                Rule::exists('patient_clinical_records', 'id')
                    ->where('patient_id', $this->route('patient')?->id)
                    ->where('kind', PatientClinicalRecord::KIND_MEDICATION),
            ],
        ];
    }
}
