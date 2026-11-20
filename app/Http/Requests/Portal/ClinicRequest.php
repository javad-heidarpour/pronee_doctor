<?php

declare(strict_types=1);

namespace App\Http\Requests\Portal;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;

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

    public function messages(): array
    {
        return [
            'city_id.required' => 'انتخاب شهر الزامی است.',
            'city_id.exists' => 'شهر انتخاب‌شده معتبر نیست.',
            'name.required' => 'وارد کردن نام مطب الزامی است.',
            'name.max' => 'نام مطب نباید بیشتر از ۲۵۵ نویسه باشد.',
            'full_address.required' => 'وارد کردن نشانی کامل الزامی است.',
            'full_address.max' => 'نشانی مطب نباید بیشتر از ۱۰۰۰ نویسه باشد.',
            'phone.max' => 'شماره تماس نباید بیشتر از ۴۵ نویسه باشد.',
            'lat.numeric' => 'عرض جغرافیایی باید عددی باشد.',
            'lat.between' => 'عرض جغرافیایی باید بین ۹۰- و ۹۰ باشد.',
            'lng.numeric' => 'طول جغرافیایی باید عددی باشد.',
            'lng.between' => 'طول جغرافیایی باید بین ۱۸۰- و ۱۸۰ باشد.',
        ];
    }

    public function attributes(): array
    {
        return [
            'city_id' => 'شهر',
            'name' => 'نام مطب',
            'full_address' => 'نشانی',
            'phone' => 'تلفن',
            'lat' => 'عرض جغرافیایی',
            'lng' => 'طول جغرافیایی',
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
