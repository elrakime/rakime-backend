<?php

namespace App\Http\Requests\Web\Product;

use Illuminate\Foundation\Http\FormRequest;

class StoreProductRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'type_id'      => ['required', 'integer', 'exists:types,id'],
            'color_id'     => ['required', 'integer', 'exists:colors,id'],
            'brand_id'     => ['required', 'integer', 'exists:brands,id'],
            'name'         => ['required', 'string', 'max:255'],
            'barcode'      => ['nullable', 'string', 'max:100', 'unique:products,barcode'],
            'image'        => ['nullable', 'image', 'max:5120'],
            'min_quantity' => ['nullable', 'integer', 'min:0'],
        ];
    }
}
