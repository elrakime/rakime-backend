<?php

namespace App\Http\Requests\Web\Transfer;

use Illuminate\Foundation\Http\FormRequest;

class StoreTransferRequest extends FormRequest
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
            'transferred_at'     => ['nullable', 'date'],
            'items'              => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['required', 'integer', 'exists:products,id'],
            'items.*.quantity'   => ['required', 'integer', 'min:1'],
        ];
    }
}
