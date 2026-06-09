<?php

namespace App\Http\Requests\Web\Expiration;

use Illuminate\Foundation\Http\FormRequest;

class StoreExpirationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'inventory_id'              => ['required', 'integer', 'exists:inventories,id'],
            'note'                      => ['nullable', 'string'],
            'reported_at'               => ['nullable', 'date'],
            'items'                     => ['required', 'array', 'min:1'],
            'items.*.stock_id'          => ['required', 'integer', 'exists:stocks,id'],
            'items.*.batch_id'          => ['nullable', 'integer', 'exists:batches,id'],
            'items.*.quantity'          => ['required', 'integer', 'min:1'],
            'items.*.reason'            => ['nullable', 'string'],
        ];
    }
}
