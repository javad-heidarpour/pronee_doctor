<?php

declare(strict_types=1);

namespace App\\Http\\Requests\\Portal;

use App\\Models\\User;
use Illuminate\\Foundation\\Http\\FormRequest;

/**
 * قوانین اعتبارسنجی مطب (برای ایجاد و ویرایش یکسان است).
 */
class ClinicRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user('web');

        return $user instanceof User && $user->is_active;
    }

    public function rules(): array
    {
        return [
            'city_id' => ['required', 'integer', 'exists:cities,id'],
            'name' => ['required', 'string', 'max:255'],
            'full_address' => ['required', 'string', 'max:1000'],
            'phone' => ['nullable', 'string', 'max:45'],
            'lat' => ['nullable', 'numeric', 'between:-90,90'],
            'lng' => ['nullable', 'numeric', 'between:-180,180'],
        ];
    }

    /**
     * Convert the form's standard coordinate names to the legacy address
     * columns, which are already present in the database.
     */
    public function clinicAttributes(): array
    {
        $data = $this->validated();

        $data['Latitude'] = $data['lat'] ?? null;
        $data['Longitude'] = $data['lng'] ?? null;

        unset($data['lat'], $data['lng']);

        return $data;
    }
}
