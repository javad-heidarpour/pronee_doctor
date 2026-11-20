<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class CancelPublicAppointmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        // The route's temporary signature and the stored mobile number are both verified.
        return true;
    }

    public function rules(): array
    {
        return [
            'patient_phone' => ['required', 'string', 'regex:/^09[0-9]{9}$/'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $phone = $this->input('patient_phone');

        if (is_string($phone)) {
            $phone = strtr(trim($phone), [
                '۰' => '0', '۱' => '1', '۲' => '2', '۳' => '3', '۴' => '4',
                '۵' => '5', '۶' => '6', '۷' => '7', '۸' => '8', '۹' => '9',
                '٠' => '0', '١' => '1', '٢' => '2', '٣' => '3', '٤' => '4',
                '٥' => '5', '٦' => '6', '٧' => '7', '٨' => '8', '٩' => '9',
            ]);

            $this->merge(['patient_phone' => $phone]);
        }
    }
}
