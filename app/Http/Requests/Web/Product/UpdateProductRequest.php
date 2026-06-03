<?php

namespace App\Http\Requests\Web\Product;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateProductRequest extends FormRequest
{
    public function rules(): array
    {
        $id = $this->route('product')?->id;

        return [
            'type_id'      => ['sometimes', 'required', 'integer', 'exists:types,id'],
            'color_id'     => ['sometimes', 'required', 'integer', 'exists:colors,id'],
            'brand_id'     => ['sometimes', 'required', 'integer', 'exists:brands,id'],
            'name'         => ['sometimes', 'required', 'string', 'max:255'],
            'barcode'      => ['sometimes', 'nullable', 'string', 'max:100', Rule::unique('products', 'barcode')->ignore($id)],
            'image'        => ['sometimes', 'nullable', 'image', 'max:5120'],
            'min_quantity' => ['sometimes', 'nullable', 'integer', 'min:0'],
        ];
    }
}
