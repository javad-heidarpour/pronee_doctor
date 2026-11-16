<?php

namespace App\Http\Requests\Portal;

use Illuminate\Foundation\Http\FormRequest;

/**
 * قوانین اعتبارسنجی مطب (برای ایجاد و ویرایش یکسان است).
 */
class ClinicRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'city_id' => 'required|exists:cities,id',
            'name' => 'required|string|max:255',
            'full_address' => 'required|string',
            'phone' => 'nullable|string',
            'lat' => 'nullable|numeric',
            'lng' => 'nullable|numeric',
        ];
    }
}
