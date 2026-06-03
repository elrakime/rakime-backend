<?php

namespace App\Http\Requests\Web\Category;

use Illuminate\Foundation\Http\FormRequest;

class StoreCategoryRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'name'  => ['required', 'string', 'max:255', 'unique:categories,name'],
            'image' => ['nullable', 'image', 'max:2048'],
        ];
    }
}
