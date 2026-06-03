<?php

namespace App\Http\Requests\Web\Wilaya;

use Illuminate\Foundation\Http\FormRequest;

class StoreWilayaRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255', 'unique:wilayas,name'],
        ];
    }
}
