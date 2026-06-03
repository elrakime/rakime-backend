<?php

namespace App\Http\Requests\Web\Color;

use Illuminate\Foundation\Http\FormRequest;

class StoreColorRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255', 'unique:colors,name'],
            'code' => ['required', 'string', 'max:10', 'unique:colors,code'],
        ];
    }
}
