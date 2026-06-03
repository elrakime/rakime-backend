<?php

namespace App\Http\Requests\Web\Role;

use Illuminate\Foundation\Http\FormRequest;

class StoreRoleRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'name'          => ['required', 'string', 'max:255', 'unique:roles,name'],
            'permissions'   => ['nullable', 'array'],
            'permissions.*' => ['string', 'exists:permissions,name'],
        ];
    }
}
