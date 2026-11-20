<?php

declare(strict_types=1);

namespace App\Http\Requests\Portal;

use App\Models\PatientProblem;
use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class SavePatientProblemRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user('web');

        return $user instanceof User && (bool) $user->is_active;
    }

    public function rules(): array
    {
        return [
            'encounter_id' => ['nullable', 'integer'],
            'category' => ['required', Rule::in(PatientProblem::CATEGORIES)],
            'title' => ['required', 'string', 'max:180'],
            'code' => ['nullable', 'string', 'max:64'],
            'code_system' => ['required', Rule::in(PatientProblem::CODE_SYSTEMS)],
            'description' => ['nullable', 'string', 'max:5000'],
            'status' => ['required', Rule::in(PatientProblem::STATUSES)],
            'severity' => ['nullable', Rule::in(PatientProblem::SEVERITIES)],
            'onset_date' => ['nullable', 'date', 'before_or_equal:today'],
            'resolved_on' => [
                'nullable',
                'exclude_unless:status,resolved',
                'date',
                'after_or_equal:onset_date',
                'before_or_equal:today',
            ],
            'source' => ['required', Rule::in(PatientProblem::SOURCES)],
        ];
    }

    public function messages(): array
    {
        return [
            'title.required' => 'عنوان تشخیص یا مشکل بیمار الزامی است.',
            'category.required' => 'دسته‌بندی مشکل را انتخاب کنید.',
            'status.required' => 'وضعیت مشکل را مشخص کنید.',
            'onset_date.before_or_equal' => 'تاریخ شروع نمی‌تواند در آینده باشد.',
            'resolved_on.after_or_equal' => 'تاریخ رفع مشکل نمی‌تواند پیش از تاریخ شروع باشد.',
        ];
    }
}