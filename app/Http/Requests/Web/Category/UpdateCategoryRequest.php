<?php

namespace App\Http\Requests\Web\Category;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateCategoryRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'name'  => ['required', 'string', 'max:255', Rule::unique('categories', 'name')->ignore($this->route('category')?->id)],
            'image' => ['nullable', 'image', 'max:2048'],
        ];
    }
}
