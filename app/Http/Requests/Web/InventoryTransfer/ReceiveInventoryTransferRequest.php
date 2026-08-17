<?php

namespace App\Http\Requests\Web\InventoryTransfer;

use Illuminate\Foundation\Http\FormRequest;

class ReceiveInventoryTransferRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'items'                        => ['nullable', 'array'],
            'items.*.stock_id'             => ['required_with:items', 'integer', 'exists:stocks,id', 'distinct:strict'],
            'items.*.selling_prices'       => ['nullable', 'array'],
            'items.*.selling_prices.*'     => ['integer', 'gt:0'],
            'items.*.installment_prices'   => ['nullable', 'array'],
            'items.*.installment_prices.*' => ['integer', 'gt:0'],
        ];
    }
}
