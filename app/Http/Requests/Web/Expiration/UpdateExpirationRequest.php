<?php

namespace App\Http\Requests\Web\Expiration;

use Illuminate\Foundation\Http\FormRequest;

class UpdateExpirationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'inventory_id'              => ['sometimes', 'integer', 'exists:inventories,id'],
            'note'                      => ['nullable', 'string'],
            'reported_at'               => ['nullable', 'date'],
            'items'                     => ['nullable', 'array'],
            'items.*.stock_id'          => ['required_with:items', 'integer', 'exists:stocks,id'],
            'items.*.quantity'          => ['required_with:items', 'integer', 'min:1'],
            'items.*.reason'            => ['nullable', 'string'],
        ];
    }
}
