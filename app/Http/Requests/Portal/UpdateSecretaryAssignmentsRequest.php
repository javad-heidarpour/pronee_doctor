<?php

namespace App\Http\Requests\Portal;

use App\Actions\Portal\Users\ListManagedUserPermissionsAction;
use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateSecretaryAssignmentsRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user('web');

        return $user instanceof User && $user->is_active;
    }

    public function rules(ListManagedUserPermissionsAction $permissionsAction): array
    {
        $availablePermissions = array_keys($permissionsAction->handle());

        return [
            'doctors' => ['present', 'array'],
            'doctors.*.id' => [
                'required',
                'integer',
                'distinct',
                Rule::exists('doctors', 'id'),
            ],
            'doctors.*.permissions' => ['required', 'array'],
            'doctors.*.clinic_ids' => ['required', 'array', 'min:1'],
            'doctors.*.clinic_ids.*' => [
                'required',
                'integer',
                'distinct',
                Rule::exists('addresses', 'id'),
            ],
            'doctors.*.permissions.*' => [
                'required',
                'string',
                'distinct',
                Rule::in($availablePermissions),
            ],
        ];
    }
}
