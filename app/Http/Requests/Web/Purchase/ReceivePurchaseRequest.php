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
            'inventory_id'                   => ['required', 'integer', 'exists:inventories,id'],
            'received_at'                    => ['nullable', 'date'],
            'items'                          => ['nullable', 'array'],
            'items.*.product_id'             => ['required_with:items', 'integer', 'exists:products,id'],
            'items.*.selling_price'          => ['nullable', 'integer', 'min:0'],
            'items.*.installment_price'      => ['nullable', 'integer', 'min:0'],
        ];
    }
}
