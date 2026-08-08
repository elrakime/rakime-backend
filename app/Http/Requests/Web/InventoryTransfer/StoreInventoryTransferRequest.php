<?php

namespace App\Http\Requests\Web\InventoryTransfer;

use Illuminate\Foundation\Http\FormRequest;

class StoreInventoryTransferRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'from_inventory_id'  => ['required', 'integer', 'exists:inventories,id', 'different:to_inventory_id'],
            'to_inventory_id'    => ['required', 'integer', 'exists:inventories,id', 'different:from_inventory_id'],
            'note'               => ['nullable', 'string'],
            'items'              => ['required', 'array', 'min:1'],
            'items.*.stock_id'   => ['required', 'integer', 'exists:stocks,id', 'distinct:strict'],
            'items.*.quantity'   => ['required', 'integer', 'min:1'],
        ];
    }
}
