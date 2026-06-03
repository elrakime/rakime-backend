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
            'accounts'   => ['nullable', 'array'],
            'accounts.*' => ['integer', 'exists:accounts,id'],
        ];
    }
}
