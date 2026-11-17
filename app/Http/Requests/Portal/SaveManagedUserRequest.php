<?php

namespace App\Http\Requests\Portal;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SaveManagedUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user('web');

        return $user instanceof User && $user->is_active;
    }

    public function rules(): array
    {
        $user = $this->route('user');
        $user = $user instanceof User ? $user : null;
        $authUser = $this->user('web');

        // Doctor accounts must be created through the doctor registration flow.
        // Existing doctor accounts remain editable by administrators.
        $allowedRoles = $authUser?->role === 'admin'
            ? ($user ? ['doctor', 'secretary', 'admin', 'user'] : ['secretary', 'admin', 'user'])
            : ['secretary'];

        return [
            'name' => ['required', 'string', 'max:100'],
            'family' => ['required', 'string', 'max:100'],
            'phone' => [
                'required',
                'string',
                'max:11',
                Rule::unique('users', 'phone')->ignore($user?->id),
            ],
            'email' => [
                'nullable',
                'email',
                'max:150',
                Rule::unique('users', 'email')->ignore($user?->id),
            ],
            'username' => [
                'required',
                'string',
                'max:100',
                Rule::unique('users', 'username')->ignore($user?->id),
            ],
            'password' => $user
                ? ['nullable', 'string', 'min:8', 'confirmed']
                : ['required', 'string', 'min:8', 'confirmed'],
            'role' => ['required', Rule::in($allowedRoles)],
            'is_active' => ['required', 'boolean'],
        ];
    }
}
