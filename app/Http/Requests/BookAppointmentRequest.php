<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Support\PatientIdentityNormalizer;
use Illuminate\Foundation\Http\FormRequest;

class BookAppointmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        // رزرو عمومی است؛ کنترل دسترسی به پزشک و مطب فعال در Action انجام می‌شود.
        return true;
    }

    public function rules(): array
    {
        return [
            'time_slot_id' => ['required', 'integer', 'exists:time_slots,id'],
            'patient_name' => ['required', 'string', 'min:3', 'max:255'],
            'patient_phone' => ['required', 'string', 'regex:/^09[0-9]{9}$/'],
            'patient_national_code' => ['nullable', 'string', 'size:10', 'regex:/^[0-9]{10}$/'],
            'description' => ['nullable', 'string', 'max:1000'],
            'visit_reason_id' => ['nullable', 'integer', 'exists:visit_reasons,id'],
            'new_visit_reason' => ['nullable', 'string', 'max:150'],
        ];
    }

    public function messages(): array
    {
        return [
            'time_slot_id.required' => 'لطفاً ساعت مراجعه را انتخاب کنید.',
            'time_slot_id.integer' => 'شناسه ساعت انتخاب‌شده معتبر نیست.',
            'time_slot_id.exists' => 'ساعت انتخاب‌شده دیگر در دسترس نیست؛ لطفاً زمان دیگری انتخاب کنید.',
            'patient_name.required' => 'نام و نام خانوادگی بیمار را وارد کنید.',
            'patient_name.min' => 'نام بیمار باید حداقل ۳ نویسه باشد.',
            'patient_name.max' => 'نام بیمار بیش از حد طولانی است.',
            'patient_name.string' => 'نام بیمار باید به‌صورت متن وارد شود.',
            'patient_phone.required' => 'شماره همراه بیمار را وارد کنید.',
            'patient_phone.regex' => 'شماره همراه باید ۱۱ رقم و با ۰۹ شروع شود.',
            'patient_phone.string' => 'شماره همراه معتبر نیست.',
            'patient_national_code.string' => 'کد ملی معتبر نیست.',
            'patient_national_code.size' => 'کد ملی باید دقیقاً ۱۰ رقم باشد.',
            'patient_national_code.regex' => 'کد ملی باید فقط شامل ۱۰ رقم باشد.',
            'description.string' => 'توضیحات باید به‌صورت متن وارد شود.',
            'description.max' => 'توضیحات نباید بیشتر از ۱۰۰۰ کاراکتر باشد.',
            'visit_reason_id.integer' => 'دلیل مراجعه انتخاب‌شده معتبر نیست.',
            'visit_reason_id.exists' => 'دلیل مراجعه انتخاب‌شده معتبر نیست.',
            'new_visit_reason.string' => 'دلیل مراجعه باید به‌صورت متن وارد شود.',
            'new_visit_reason.max' => 'دلیل مراجعه نباید بیشتر از ۱۵۰ کاراکتر باشد.',
        ];
    }

    protected function prepareForValidation(): void
    {
        $normalizer = app(PatientIdentityNormalizer::class);

        $this->merge([
            'patient_name' => $normalizer->normalizeName($this->input('patient_name')),
            'patient_phone' => $normalizer->normalizePhone($this->input('patient_phone')),
            'patient_national_code' => $normalizer->normalizeNationalCode($this->input('patient_national_code')),
            'new_visit_reason' => is_string($this->input('new_visit_reason'))
                ? trim($this->input('new_visit_reason'))
                : $this->input('new_visit_reason'),
            'description' => is_string($this->input('description'))
                ? trim($this->input('description'))
                : $this->input('description'),
        ]);
    }
}
