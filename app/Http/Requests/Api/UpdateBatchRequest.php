<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

class UpdateBatchRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'source_id'        => ['nullable', 'integer'],
            'source_type'      => ['nullable', 'string', 'max:50'],
            'purchase_price'   => ['sometimes', 'integer', 'min:0'],
            'initial_quantity' => ['sometimes', 'integer', 'min:0'],
            'current_quantity' => ['sometimes', 'integer', 'min:0'],
            'purchased_at'     => ['nullable', 'date'],
        ];
    }
}
