<?php

namespace App\Http\Requests\Web\Sale;

use App\Enums\DiscountType;
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
            'branch_id'       => ['sometimes', 'integer', 'exists:branches,id'],
            'client_id'       => ['sometimes', 'integer', 'exists:clients,id'],
            'note'            => ['nullable', 'string'],
            'tax_rate'        => ['nullable', 'numeric', 'min:0', 'max:100'],
            'discount_type'   => ['nullable', 'string', 'in:' . implode(',', DiscountType::keys())],
            'discount_value'  => ['nullable', 'numeric', 'min:0'],
            'items'           => ['nullable', 'array', 'min:1'],
            'items.*.stock_id' => ['required_with:items', 'integer', 'exists:stocks,id', 'distinct:strict'],
            'items.*.quantity' => ['required_with:items', 'integer', 'min:1'],
            'items.*.price_id' => ['nullable', 'integer', 'exists:prices,id', 'distinct:strict'],
        ];
    }
}
