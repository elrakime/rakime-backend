<?php

namespace App\Http\Requests\Web\Type;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreTypeRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'category_id' => ['required', 'integer', 'exists:categories,id'],
            'name'        => ['required', 'string', 'max:255', Rule::unique('types')->where('category_id', $this->input('category_id'))],
        ];
    }
}
