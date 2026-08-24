<?php

declare(strict_types=1);

namespace App\Http\Requests\Web\Contract;

use Illuminate\Foundation\Http\FormRequest;

class StoreContractRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'client_id'  => ['required', 'integer', 'exists:clients,id'],
            'account_id' => ['required', 'integer', 'exists:accounts,id'],
            'branch_id'  => ['required', 'integer', 'exists:branches,id'],
            'note'       => ['nullable', 'string'],
            'advance_amount'      => ['sometimes', 'numeric', 'min:0'],
            'months_count'        => ['sometimes', 'integer', 'min:1'],
            'items'               => ['sometimes', 'array', 'min:1'],
            'items.*.product_id'  => ['required_with:items', 'integer', 'exists:products,id', 'distinct:strict'],
            'items.*.stock_id'    => ['required_with:items', 'integer', 'exists:stocks,id', 'distinct:strict'],
            'items.*.quantity'    => ['required_with:items', 'integer', 'min:1'],
            'items.*.price'       => ['required_with:items', 'numeric', 'min:0'],
        ];
    }
}
