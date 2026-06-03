<?php

namespace App\Http\Requests\Web\Purchase;

use Illuminate\Foundation\Http\FormRequest;

class UpdatePurchaseRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'supplier_id'          => ['sometimes', 'required', 'integer', 'exists:suppliers,id'],
            'reference'            => ['sometimes', 'nullable', 'string', 'max:100'],
            'note'                 => ['sometimes', 'nullable', 'string'],
            'purchased_at'         => ['sometimes', 'required', 'date'],
            'items'                => ['sometimes', 'required', 'array', 'min:1'],
            'items.*.product_id'   => ['required_with:items', 'integer', 'exists:products,id'],
            'items.*.quantity'     => ['required_with:items', 'integer', 'min:1'],
            'items.*.price'        => ['required_with:items', 'integer', 'min:0'],
        ];
    }
}
