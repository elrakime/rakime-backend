<?php

namespace App\Http\Requests\Web\Branch;

use Illuminate\Foundation\Http\FormRequest;

class StoreBranchRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'name'       => ['required', 'string', 'max:255', 'unique:branches,name'],
            'code'       => ['required', 'string', 'max:1', 'unique:branches,code'],
            'shop_name'  => ['required', 'string', 'max:255'],
            'address'    => ['nullable', 'string'],
            'phone'      => ['nullable', 'string', 'max:255'],
            'wilaya_id'  => ['nullable', 'integer', 'exists:wilayas,id'],
            'image'      => ['nullable', 'image', 'max:2048'],
            'metadata'   => ['nullable', 'json'],
            'accounts'   => ['nullable', 'array'],
            'accounts.*' => ['integer', 'exists:accounts,id'],
        ];
    }
}
