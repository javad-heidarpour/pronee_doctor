<?php

namespace App\\Http\\Requests\\Portal;

use Illuminate\\Foundation\\Http\\FormRequest;

class UpdateAppointmentStatusRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        // وضعیت paid فقط باید توسط callback تأییدشده درگاه ثبت شود.
        // این endpoint فقط برای وضعیت‌های مدیریتی نوبت است.
        return [
            'status' => ['required', 'in:pending,confirmed,cancelled,visited'],
        ];
    }
}
