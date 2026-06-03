<?php

namespace App\Http\Requests\Web\Type;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateTypeRequest extends FormRequest
{
    public function rules(): array
    {
        $categoryId = $this->input('category_id', $this->route('type')?->category_id);

        return [
            'category_id' => ['sometimes', 'required', 'integer', 'exists:categories,id'],
            'name'        => ['sometimes', 'required', 'string', 'max:255', Rule::unique('types')->where('category_id', $categoryId)->ignore($this->route('type')?->id)],
        ];
    }
}
