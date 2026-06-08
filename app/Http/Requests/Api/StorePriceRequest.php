<?php

namespace App\Http\Requests\Api;

use App\Enums\PriceType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;

class StorePriceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'stock_id' => ['required', 'exists:stocks,id'],
            'type'     => ['required', new Enum(PriceType::class)],
            'amount'   => ['required', 'integer', 'min:0'],
        ];
    }
}
