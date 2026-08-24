<?php

namespace App\Http\Requests\Web\Purchase;

use Illuminate\Foundation\Http\FormRequest;

class ReceivePurchaseRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'inventory_id'                   => ['nullable', 'integer', 'exists:inventories,id'],
            'items'                          => ['nullable', 'array'],
            'items.*.product_id'             => ['required_with:items', 'integer', 'exists:products,id', 'distinct:strict'],
            'items.*.selling_prices'          => ['nullable', 'array'],
            'items.*.selling_prices.*'        => ['numeric', 'gt:0'],
            'items.*.installment_prices'      => ['nullable', 'array'],
            'items.*.installment_prices.*'    => ['numeric', 'gt:0'],
        ];
    }
}
