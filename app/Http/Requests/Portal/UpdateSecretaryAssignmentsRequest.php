<?php

namespace App\\Http\\Requests\\Portal;

use App\\Actions\\Portal\\Users\\ListManagedUserPermissionsAction;
use Illuminate\\Foundation\\Http\\FormRequest;
use Illuminate\\Validation\\Rule;

class UpdateSecretaryAssignmentsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
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
