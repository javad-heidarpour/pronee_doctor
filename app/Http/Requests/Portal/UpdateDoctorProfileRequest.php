<?php

namespace App\Http\Requests\Portal;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;

class UpdateDoctorProfileRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user('web')?->isDoctor() ?? false;
    }

    public function rules(): array
    {
        $doctor = Auth::guard('web')->user()->doctorProfile;

        return [
            'name' => 'required|string|max:255',
            'family' => 'required|string|max:255',
            'caption' => 'nullable|string|max:255',
            'specialty_title' => 'required|string|max:255',
            'years_of_experience' => 'required|integer|min:0',
            'Medical_system_number' => 'required|string',
            'description' => 'nullable|string',
            'meta_title' => 'nullable|string|max:255',
            'meta_description' => 'nullable|string',
            'photo' => 'nullable|image|max:2048',
            'email' => 'required|email|unique:doctors,email,' . $doctor->id,
            'phone' => 'nullable|string',
        ];
    }
}
