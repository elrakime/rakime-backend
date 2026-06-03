<?php

namespace App\Http\Requests\Web\Brand;

use Illuminate\Foundation\Http\FormRequest;

class StoreBrandRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'name'  => ['required', 'string', 'max:255', 'unique:brands,name'],
            'image' => ['nullable', 'image', 'max:2048'],
        ];
    }
}
