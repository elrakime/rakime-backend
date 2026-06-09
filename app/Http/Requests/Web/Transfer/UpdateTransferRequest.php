<?php

namespace App\Http\Requests\Web\Transfer;

use Illuminate\Foundation\Http\FormRequest;

class UpdateTransferRequest extends FormRequest
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
            'transferred_at'     => ['nullable', 'date'],
            'items'              => ['nullable', 'array'],
            'items.*.product_id' => ['required_with:items', 'integer', 'exists:products,id'],
            'items.*.quantity'   => ['required_with:items', 'integer', 'min:1'],
        ];
    }
}
