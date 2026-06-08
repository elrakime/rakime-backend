<?php

namespace App\Http\Requests\Web\Purchase;

use Illuminate\Foundation\Http\FormRequest;

class StorePurchaseReturnRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'reference'                    => ['nullable', 'string', 'max:255'],
            'returned_at'                  => ['nullable', 'date'],
            'items'                        => ['required', 'array', 'min:1'],
            'items.*.purchase_item_id'     => ['required', 'integer', 'exists:purchase_items,id'],
            'items.*.quantity'             => ['required', 'integer', 'min:1'],
            'items.*.reason'               => ['nullable', 'string'],
        ];
    }
}
