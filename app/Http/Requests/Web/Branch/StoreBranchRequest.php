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
            'accounts'   => ['nullable', 'array'],
            'accounts.*' => ['integer', 'exists:accounts,id'],
        ];
    }
}
