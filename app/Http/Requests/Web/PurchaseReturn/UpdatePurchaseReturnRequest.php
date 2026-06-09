<?php

namespace App\Http\Requests\Web\PurchaseReturn;

use Illuminate\Foundation\Http\FormRequest;

class UpdatePurchaseReturnRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'reference'                    => ['nullable', 'string', 'max:255'],
            'note'                         => ['nullable', 'string', 'max:65535'],
            'returned_at'                  => ['nullable', 'date'],
            'items'                        => ['nullable', 'array', 'min:1'],
            'items.*.purchase_item_id'     => ['required_with:items', 'integer', 'exists:purchase_items,id'],
            'items.*.quantity'             => ['required_with:items', 'integer', 'min:1'],
            'items.*.reason'               => ['nullable', 'string'],
        ];
    }
}
