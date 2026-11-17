<?php

namespace App\Http\Requests\Portal;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;

class ListSecurityAuditLogsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return Auth::guard('web')->user()?->role === 'admin';
    }

    public function rules(): array
    {
        return [
            'event' => ['nullable', 'string', 'max:100'],
            'actor_id' => ['nullable', 'integer', 'min:1'],
            'from' => ['nullable', 'date_format:Y-m-d'],
            'to' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:from'],
        ];
    }
}
