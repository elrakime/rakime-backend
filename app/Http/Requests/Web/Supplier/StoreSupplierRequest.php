<?php

namespace App\Http\Requests\Web\Supplier;

use Illuminate\Foundation\Http\FormRequest;

class StoreSupplierRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name'      => ['required', 'string', 'max:255'],
            'phone'     => ['required', 'string', 'max:20'],
            'email'     => ['nullable', 'email', 'max:255'],
            'address'   => ['required', 'string', 'max:500'],
            'is_active' => ['nullable', 'boolean'],
            'wilaya_id' => ['nullable', 'integer', 'exists:wilayas,id'],
            'image'     => ['nullable', 'image', 'max:2048'],
            'metadata'  => ['nullable', 'json'],
        ];
    }
}
