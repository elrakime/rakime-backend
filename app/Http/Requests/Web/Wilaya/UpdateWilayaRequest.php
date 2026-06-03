<?php

namespace App\Http\Requests\Web\Wilaya;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateWilayaRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255', Rule::unique('wilayas', 'name')->ignore($this->route('wilaya')?->id)],
        ];
    }
}
