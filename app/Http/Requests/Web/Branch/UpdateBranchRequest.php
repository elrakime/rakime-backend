<?php

namespace App\Http\Requests\Web\Branch;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateBranchRequest extends FormRequest
{
    public function rules(): array
    {
        $id = $this->route('branch')?->id;

        return [
            'name'       => ['sometimes', 'required', 'string', 'max:255', Rule::unique('branches', 'name')->ignore($id)],
            'code'       => ['sometimes', 'required', 'string', 'max:1', Rule::unique('branches', 'code')->ignore($id)],
            'shop_name'  => ['sometimes', 'required', 'string', 'max:255'],
            'address'    => ['nullable', 'string'],
            'phone'      => ['nullable', 'string', 'max:255'],
            'accounts'   => ['nullable', 'array'],
            'accounts.*' => ['integer', 'exists:accounts,id'],
        ];
    }
}
