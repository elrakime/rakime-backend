<?php

namespace App\Http\Requests\Web\Brand;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateBrandRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'name'  => ['required', 'string', 'max:255', Rule::unique('brands', 'name')->ignore($this->route('brand')?->id)],
            'image' => ['nullable', 'image', 'max:2048'],
        ];
    }
}
