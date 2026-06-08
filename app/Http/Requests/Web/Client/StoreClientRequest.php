<?php

namespace App\Http\Requests\Web\Client;

use Illuminate\Foundation\Http\FormRequest;

class StoreClientRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'branch_id'  => ['required', 'integer', 'exists:branches,id'],
            'wilaya_id'  => ['required', 'integer', 'exists:wilayas,id'],
            'firstname'  => ['required', 'string', 'max:255'],
            'lastname'   => ['required', 'string', 'max:255'],
            'phone'      => ['required', 'string', 'max:20', 'unique:clients,phone'],
            'birthdate'  => ['nullable', 'date'],
            'address'    => ['nullable', 'string', 'max:500'],
            'occupation' => ['nullable', 'string', 'max:255'],
            'employer'   => ['nullable', 'string', 'max:255'],
            'salary'     => ['required', 'numeric', 'min:0'],
            'nin'        => ['required', 'string', 'max:50', 'unique:clients,nin'],
            'ccp_number' => ['required', 'string', 'max:50', 'unique:clients,ccp_number'],
            'ccp_key'    => ['required', 'string', 'max:10'],
            'eccp'       => ['nullable', 'string', 'max:255'],
        ];
    }
}
