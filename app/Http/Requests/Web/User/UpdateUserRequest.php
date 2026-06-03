<?php

namespace App\Http\Requests\Web\User;

use Illuminate\Foundation\Http\FormRequest;

class UpdateUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $userId = $this->route('user')?->id;

        return [
            'name'       => ['sometimes', 'string', 'max:255'],
            'email'      => ['sometimes', 'email', 'unique:users,email,' . $userId],
            'password'   => ['sometimes', 'string', 'min:8', 'confirmed'],
            'phone'      => ['nullable', 'string', 'max:20'],
            'is_active'  => ['boolean'],
            'roles'      => ['nullable', 'array'],
            'roles.*'    => ['string', 'exists:roles,name'],
            'branches'   => ['nullable', 'array'],
            'branches.*' => ['integer', 'exists:branches,id'],
        ];
    }
}
