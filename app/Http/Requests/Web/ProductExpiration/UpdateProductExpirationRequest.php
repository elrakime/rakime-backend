<?php

namespace App\Http\Requests\Web\ProductExpiration;

use Illuminate\Foundation\Http\FormRequest;

class UpdateProductExpirationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'inventory_id'              => ['sometimes', 'integer', 'exists:inventories,id'],
            'reference'                 => ['sometimes', 'string', 'max:255'],
            'note'                      => ['nullable', 'string'],
            'reported_at'               => ['nullable', 'date'],
            'items'                     => ['nullable', 'array'],
            'items.*.product_id'        => ['required_with:items', 'integer', 'exists:products,id'],
            'items.*.stock_id'          => ['required_with:items', 'integer', 'exists:stocks,id'],
            'items.*.batch_id'          => ['nullable', 'integer', 'exists:batches,id'],
            'items.*.quantity'          => ['required_with:items', 'integer', 'min:1'],
            'items.*.reason'            => ['nullable', 'string'],
        ];
    }
}
