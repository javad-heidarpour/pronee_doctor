<?php

declare(strict_types=1);

namespace App\Http\Requests\Portal;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StorePatientFollowUpRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user('web') !== null;
    }

    public function rules(): array
    {
        return [
            'patient_id' => ['required', 'integer', 'exists:patients,id'],
            'appointment_id' => ['nullable', 'integer', 'exists:appointments,id'],
            'treatment_plan_id' => ['nullable', 'integer', 'exists:patient_treatment_plans,id'],
            'template_id' => ['nullable', 'integer', 'exists:patient_follow_up_templates,id'],
            'title' => ['required_without_all:template_id,steps', 'nullable', 'string', 'max:160'],
            'description' => ['nullable', 'string', 'max:3000'],
            'due_at' => ['required', 'date', 'after_or_equal:now'],
            'priority' => ['required', Rule::in(['low', 'normal', 'high', 'urgent'])],
            'contact_method' => ['required', Rule::in(['call', 'sms', 'in_person', 'review'])],
            'assigned_to' => ['nullable', 'integer', 'exists:users,id'],
            'send_sms' => ['sometimes', 'boolean'],
            'sms_consent_confirmed' => ['sometimes', 'boolean'],
            'steps' => ['nullable', 'array', 'min:1', 'max:12'],
            'steps.*.title' => ['required_with:steps', 'string', 'max:160'],
            'steps.*.days' => ['required_with:steps', 'integer', 'min:0', 'max:365'],
            'steps.*.contact_method' => ['required_with:steps', Rule::in(['call', 'sms', 'in_person', 'review'])],
            'steps.*.send_sms' => ['sometimes', 'boolean'],
            'steps.*.sms_consent_confirmed' => ['sometimes', 'boolean'],
        ];
    }
}