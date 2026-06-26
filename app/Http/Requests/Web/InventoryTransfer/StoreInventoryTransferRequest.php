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
            'transferred_at'     => ['nullable', 'date'],
            'items'              => ['required', 'array', 'min:1'],
            'items.*.stock_id'   => ['required', 'integer', 'exists:stocks,id'],
            'items.*.quantity'   => ['required', 'integer', 'min:1'],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $fromInventoryId = $this->input('from_inventory_id');
            $items = $this->input('items', []);

            foreach ($items as $index => $item) {
                if (!isset($item['stock_id'])) {
                    continue;
                }

                $exists = \App\Models\Stock::where('id', $item['stock_id'])
                    ->where('inventory_id', $fromInventoryId)
                    ->exists();

                if (!$exists) {
                    $validator->errors()->add(
                        "items.{$index}.stock_id",
                        __('validation.exists', ['attribute' => "items.{$index}.stock_id"]),
                    );
                }
            }
        });
    }
}
