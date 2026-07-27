<?php

namespace App\Http\Requests\Web\SaleReturn;

use Illuminate\Foundation\Http\FormRequest;

class StoreSaleReturnRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'note'                     => ['nullable', 'string', 'max:65535'],
            'items'                    => ['required', 'array', 'min:1'],
            'items.*.sale_item_id'     => ['required', 'integer', 'exists:sale_items,id'],
            'items.*.quantity'         => ['required', 'integer', 'min:1'],
            'items.*.reason'           => ['nullable', 'string'],
        ];
    }
}
