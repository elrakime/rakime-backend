<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

class StoreBatchRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'stock_id'         => ['required', 'exists:stocks,id'],
            'source_id'        => ['nullable', 'integer'],
            'source_type'      => ['nullable', 'string', 'max:50'],
            'purchase_price'   => ['required', 'integer', 'min:0'],
            'initial_quantity' => ['required', 'integer', 'min:0'],
            'current_quantity' => ['nullable', 'integer', 'min:0'],
            'purchased_at'     => ['nullable', 'date'],
        ];
    }
}
