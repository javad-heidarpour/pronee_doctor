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
            'template_id' => ['nullable', 'integer', 'exists:patient_follow_up_templates,id'],
            'title' => ['required_without:template_id', 'nullable', 'string', 'max:160'],
            'description' => ['nullable', 'string', 'max:3000'],
            'due_at' => ['required', 'date', 'after_or_equal:now'],
            'priority' => ['required', Rule::in(['low', 'normal', 'high', 'urgent'])],
            'contact_method' => ['required', Rule::in(['call', 'sms', 'in_person', 'review'])],
            'assigned_to' => ['nullable', 'integer', 'exists:users,id'],
            'send_sms' => ['sometimes', 'boolean'],
        ];
    }
}