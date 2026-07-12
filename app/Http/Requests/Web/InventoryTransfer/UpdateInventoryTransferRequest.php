<?php

namespace App\Http\Requests\Web\InventoryTransfer;

use Illuminate\Foundation\Http\FormRequest;

class UpdateInventoryTransferRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'from_inventory_id'  => ['sometimes', 'integer', 'exists:inventories,id', 'different:to_inventory_id'],
            'to_inventory_id'    => ['sometimes', 'integer', 'exists:inventories,id', 'different:from_inventory_id'],
            'note'               => ['nullable', 'string'],
            'items'              => ['nullable', 'array'],
            'items.*.stock_id'   => ['required_with:items', 'integer', 'exists:stocks,id'],
            'items.*.quantity'   => ['required_with:items', 'integer', 'min:1'],
        ];
    }
}
