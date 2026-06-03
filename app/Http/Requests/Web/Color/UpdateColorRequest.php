<?php

namespace App\Http\Requests\Web\Color;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateColorRequest extends FormRequest
{
    public function rules(): array
    {
        $id = $this->route('color')?->id;

        return [
            'name' => ['sometimes', 'required', 'string', 'max:255', Rule::unique('colors', 'name')->ignore($id)],
            'code' => ['sometimes', 'required', 'string', 'max:10', Rule::unique('colors', 'code')->ignore($id)],
        ];
    }
}
