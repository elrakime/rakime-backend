<?php

namespace App\Http\Requests\Web\Supplier;

use Illuminate\Foundation\Http\FormRequest;

class UpdateSupplierRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name'      => ['sometimes', 'required', 'string', 'max:255'],
            'phone'     => ['sometimes', 'required', 'string', 'max:20'],
            'email'     => ['sometimes', 'nullable', 'email', 'max:255'],
            'address'   => ['sometimes', 'required', 'string', 'max:500'],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }
}
