<?php

namespace App\Http\Requests\Web\Restock;

use Illuminate\Foundation\Http\FormRequest;

class UpdateRestockRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'branch_id'                    => ['nullable', 'integer', 'exists:branches,id'],
            'reference'                    => ['nullable', 'string', 'max:255'],
            'note'                         => ['nullable', 'string', 'max:65535'],
            'items'                        => ['nullable', 'array', 'min:1'],
            'items.*.product_id'           => ['required_with:items', 'integer', 'exists:products,id'],
            'items.*.requested_quantity'   => ['required_with:items', 'integer', 'min:1'],
        ];
    }
}
