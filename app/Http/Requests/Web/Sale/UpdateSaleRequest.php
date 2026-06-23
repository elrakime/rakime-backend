<?php

namespace App\Http\Requests\Web\Sale;

use Illuminate\Foundation\Http\FormRequest;

class UpdateSaleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'branch_id' => ['sometimes', 'integer', 'exists:branches,id'],
            'client_id' => ['sometimes', 'integer', 'exists:clients,id'],
            'note'      => ['nullable', 'string'],
            'sold_at'   => ['nullable', 'date'],
        ];
    }
}
