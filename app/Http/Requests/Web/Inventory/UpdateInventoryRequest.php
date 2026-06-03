<?php

namespace App\Http\Requests\Web\Inventory;

use Illuminate\Foundation\Http\FormRequest;

class UpdateInventoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'branch_id' => ['sometimes', 'nullable', 'integer', 'exists:branches,id'],
            'name'      => ['sometimes', 'required', 'string', 'max:255'],
        ];
    }
}
