<?php

namespace App\Http\Requests\Web\Restock;

use Illuminate\Foundation\Http\FormRequest;

class StoreRestockRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'branch_id'                    => ['required', 'integer', 'exists:branches,id'],
            'reference'                    => ['nullable', 'string', 'max:255'],
            'note'                         => ['nullable', 'string', 'max:65535'],
            'items'                        => ['required', 'array', 'min:1'],
            'items.*.product_id'           => ['required', 'integer', 'exists:products,id'],
            'items.*.requested_quantity'   => ['required', 'integer', 'min:1'],
        ];
    }
}
