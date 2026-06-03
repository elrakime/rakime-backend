<?php

namespace App\Http\Requests\Web\Treasury;

use Illuminate\Foundation\Http\FormRequest;

class StoreTreasuryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'branch_id' => ['nullable', 'integer', 'exists:branches,id'],
            'name'      => ['required', 'string', 'max:255'],
            'balance'   => ['nullable', 'numeric', 'min:0'],
        ];
    }
}
