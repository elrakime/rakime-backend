<?php

declare(strict_types=1);

namespace App\Http\Requests\Web\Contract;

use Illuminate\Foundation\Http\FormRequest;

class ConfirmContractRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'advance_amount'      => ['required', 'integer', 'min:0'],
            'months_count'        => ['required', 'integer', 'min:1'],
            'items'               => ['required', 'array', 'min:1'],
            'items.*.product_id'  => ['required', 'integer', 'exists:products,id'],
            'items.*.stock_id'    => ['required', 'integer', 'exists:stocks,id'],
            'items.*.quantity'    => ['required', 'integer', 'min:1'],
            'items.*.price'       => ['required', 'integer', 'min:0'],
        ];
    }
}
