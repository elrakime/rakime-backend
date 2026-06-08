<?php

namespace App\Http\Requests\Web\Client;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateClientRequest extends FormRequest
{
    public function rules(): array
    {
        $id = $this->route('client')?->id;

        return [
            'branch_id'  => ['sometimes', 'required', 'integer', 'exists:branches,id'],
            'wilaya_id'  => ['sometimes', 'required', 'integer', 'exists:wilayas,id'],
            'firstname'  => ['sometimes', 'required', 'string', 'max:255'],
            'lastname'   => ['sometimes', 'required', 'string', 'max:255'],
            'phone'      => ['sometimes', 'required', 'string', 'max:20', Rule::unique('clients', 'phone')->ignore($id)],
            'birthdate'  => ['sometimes', 'nullable', 'date'],
            'address'    => ['sometimes', 'nullable', 'string', 'max:500'],
            'occupation' => ['sometimes', 'nullable', 'string', 'max:255'],
            'employer'   => ['sometimes', 'nullable', 'string', 'max:255'],
            'salary'     => ['sometimes', 'required', 'numeric', 'min:0'],
            'nin'        => ['sometimes', 'required', 'string', 'max:50', Rule::unique('clients', 'nin')->ignore($id)],
            'ccp_number' => ['sometimes', 'required', 'string', 'max:50', Rule::unique('clients', 'ccp_number')->ignore($id)],
            'ccp_key'    => ['sometimes', 'required', 'string', 'max:10'],
            'eccp'       => ['sometimes', 'nullable', 'string', 'max:255'],
        ];
    }
}
