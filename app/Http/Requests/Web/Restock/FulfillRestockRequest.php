<?php

namespace App\Http\Requests\Web\Restock;

use Illuminate\Foundation\Http\FormRequest;

class FulfillRestockRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'type'              => ['required', 'string', 'in:purchase,transfer,none'],
            'supplier_id'       => ['required_if:type,purchase', 'integer', 'exists:suppliers,id'],
            'from_inventory_id' => ['required_if:type,transfer', 'integer', 'exists:inventories,id'],
            'reference'         => ['nullable', 'string', 'max:255'],
            'note'              => ['nullable', 'string', 'max:65535'],
        ];
    }
}
