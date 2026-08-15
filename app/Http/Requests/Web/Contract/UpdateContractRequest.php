<?php

declare(strict_types=1);

namespace App\Http\Requests\Web\Contract;

use Illuminate\Foundation\Http\FormRequest;

class UpdateContractRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'items'               => ['sometimes', 'array', 'min:1'],
            'items.*.product_id'  => ['required_with:items', 'integer', 'exists:products,id', 'distinct:strict'],
            'items.*.stock_id'    => ['required_with:items', 'integer', 'exists:stocks,id', 'distinct:strict'],
            'items.*.quantity'    => ['required_with:items', 'integer', 'min:1'],
            'items.*.price'       => ['required_with:items', 'integer', 'min:0'],
            'advance_amount'      => ['sometimes', 'integer', 'min:0'],
            'months_count'        => ['sometimes', 'integer', 'min:1'],
            'max_amount'          => ['sometimes', 'integer', 'min:1'],
        ];
    }
}
