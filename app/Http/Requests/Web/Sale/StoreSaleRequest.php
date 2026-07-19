<?php

namespace App\Http\Requests\Web\Sale;

use Illuminate\Foundation\Http\FormRequest;

class StoreSaleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'branch_id'       => ['required', 'integer', 'exists:branches,id'],
            'client_id'       => ['nullable', 'integer', 'exists:clients,id'],
            'note'            => ['nullable', 'string'],
            'tax_rate'        => ['nullable', 'integer', 'min:0', 'max:100'],
            'discount_type'   => ['nullable', 'string', 'in:percentage,fixed'],
            'discount_value'  => ['nullable', 'integer', 'min:0'],
            'items'                 => ['required', 'array', 'min:1'],
            'items.*.stock_id'      => ['required', 'integer', 'exists:stocks,id'],
            'items.*.quantity'      => ['required', 'integer', 'min:1'],
            'items.*.price_id'      => ['nullable', 'integer', 'exists:prices,id'],
        ];
    }
}
