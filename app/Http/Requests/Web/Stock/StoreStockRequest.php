<?php

namespace App\Http\Requests\Web\Stock;

use Illuminate\Foundation\Http\FormRequest;

class StoreStockRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'inventory_id'           => ['required', 'integer', 'exists:inventories,id'],
            'product_id'             => ['required', 'integer', 'exists:products,id'],
            'source_id'              => ['nullable', 'integer'],
            'source_type'            => ['nullable', 'string'],
            'initial_quantity'       => ['nullable', 'integer', 'min:0'],
            'current_quantity'       => ['nullable', 'integer', 'min:0'],
            'purchase_price'         => ['nullable', 'integer', 'min:0'],
            'purchased_at'              => ['nullable', 'date'],
            'selling_prices'            => ['nullable', 'array'],
            'selling_prices.*'          => ['integer', 'min:0'],
            'installment_prices'        => ['nullable', 'array'],
            'installment_prices.*'      => ['integer', 'min:0'],
            'wholesale_prices'          => ['nullable', 'array'],
            'wholesale_prices.*'        => ['integer', 'min:0'],
        ];
    }
}
