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
            'accounts'   => ['nullable', 'array'],
            'accounts.*' => ['integer', 'exists:accounts,id'],
        ];
    }
}
