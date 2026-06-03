<?php

namespace App\Http\Requests\Web\Role;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateRoleRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'name'          => ['sometimes', 'required', 'string', 'max:255', Rule::unique('roles', 'name')->ignore($this->route('role')?->id)],
            'permissions'   => ['nullable', 'array'],
            'permissions.*' => ['string', 'exists:permissions,name'],
        ];
    }
}
