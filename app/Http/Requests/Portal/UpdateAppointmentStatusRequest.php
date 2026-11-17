<?php

namespace App\Http\Requests\Portal;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;

class UpdateAppointmentStatusRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user('web');

        return $user instanceof User && $user->is_active;
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
