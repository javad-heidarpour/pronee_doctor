<?php

namespace App\Http\Requests\Portal;

use Illuminate\Foundation\Http\FormRequest;

/**
 * ثبت نوبت توسط منشی/پزشک از داخل پنل (بدون درگاه پرداخت).
 */
class StorePortalAppointmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'time_slot_id' => ['required', 'integer', 'exists:time_slots,id'],
            'patient_name' => ['required', 'string', 'min:3', 'max:255'],
            'patient_phone' => [
                'required',
                'string',
                'regex:/^09[0-9]{9}$/',
            ],
            'patient_national_code' => [
                'nullable',
                'string',
                'size:10',
                'regex:/^[0-9]{10}$/',
            ],
            'description' => ['nullable', 'string', 'max:1000'],
            'visit_reason_id' => [
                'nullable',
                'integer',
                'exists:visit_reasons,id',
            ],
            'new_visit_reason' => ['nullable', 'string', 'max:150'],
        ];
    }
}
