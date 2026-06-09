<?php

namespace App\Http\Requests\Web\Purchase;

use Illuminate\Foundation\Http\FormRequest;

class StorePurchaseRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'supplier_id'          => ['required', 'integer', 'exists:suppliers,id'],
            'note'                 => ['nullable', 'string'],
            'purchased_at'         => ['required', 'date'],
            'items'                => ['required', 'array', 'min:1'],
            'items.*.product_id'   => ['required', 'integer', 'exists:products,id'],
            'items.*.quantity'     => ['required', 'integer', 'min:1'],
            'items.*.price'        => ['required', 'integer', 'min:0'],
        ];
    }
}
