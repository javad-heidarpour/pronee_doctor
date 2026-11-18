<?php

declare(strict_types=1);

namespace App\Http\Requests\Portal;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class StorePatientEncounterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'note_type' => ['required', Rule::in(['consultation', 'progress', 'emergency', 'procedure', 'discharge'])],
            'subjective' => ['nullable', 'string', 'max:12000'],
            'objective' => ['nullable', 'string', 'max:12000'],
            'assessment' => ['required', 'string', 'max:12000'],
            'plan' => ['required', 'string', 'max:12000'],
            'diagnosis' => ['nullable', 'string', 'max:500'],
            'diagnosis_code' => ['nullable', 'string', 'max:40'],
            'reviewed_allergies' => ['required', 'boolean'],
            'reviewed_medications' => ['required', 'boolean'],
            'reviewed_vitals' => ['required', 'boolean'],
            'prescriptions' => ['sometimes', 'array', 'max:30'],
            'prescriptions.*.medication_name' => ['required', 'string', 'max:180'],
            'prescriptions.*.strength' => ['nullable', 'string', 'max:120'],
            'prescriptions.*.dosage' => ['required', 'string', 'max:120'],
            'prescriptions.*.route' => ['required', 'string', 'max:80'],
            'prescriptions.*.frequency' => ['required', 'string', 'max:120'],
            'prescriptions.*.duration_days' => ['nullable', 'integer', 'min:1', 'max:3650'],
            'prescriptions.*.quantity' => ['nullable', 'string', 'max:60'],
            'prescriptions.*.instructions' => ['nullable', 'string', 'max:2000'],
        ];
    }

    public function messages(): array
    {
        return [
            'assessment.required' => 'ثبت ارزیابی پزشک برای ذخیره یادداشت الزامی است.',
            'plan.required' => 'ثبت برنامه درمانی برای ذخیره یادداشت الزامی است.',
            'reviewed_allergies.required' => 'وضعیت بازبینی حساسیت‌ها را مشخص کنید.',
            'reviewed_medications.required' => 'وضعیت بازبینی داروها را مشخص کنید.',
            'reviewed_vitals.required' => 'وضعیت بازبینی علائم حیاتی را مشخص کنید.',
        ];
    }
}