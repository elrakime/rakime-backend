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
            'branch_id'            => ['sometimes', 'required', 'integer', 'exists:branches,id'],
            'supplier_id'          => ['sometimes', 'required', 'integer', 'exists:suppliers,id'],
            'note'                 => ['sometimes', 'nullable', 'string'],
            'items'                => ['sometimes', 'required', 'array', 'min:1'],
            'items.*.product_id'   => ['required_with:items', 'integer', 'exists:products,id', 'distinct:strict'],
            'items.*.quantity'     => ['required_with:items', 'integer', 'min:1'],
            'items.*.price'        => ['required_with:items', 'numeric', 'min:0'],
        ];
    }
}
