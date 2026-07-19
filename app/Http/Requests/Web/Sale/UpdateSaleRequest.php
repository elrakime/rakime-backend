<?php

namespace App\Http\Requests\Web\Sale;

use Illuminate\Foundation\Http\FormRequest;

class UpdateSaleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'branch_id'      => ['sometimes', 'integer', 'exists:branches,id'],
            'client_id'      => ['sometimes', 'integer', 'exists:clients,id'],
            'note'           => ['nullable', 'string'],
            'tax_rate'       => ['nullable', 'integer', 'min:0', 'max:100'],
            'discount_type'  => ['nullable', 'string', 'in:percentage,fixed'],
            'discount_value' => ['nullable', 'integer', 'min:0'],
        ];
    }
}
