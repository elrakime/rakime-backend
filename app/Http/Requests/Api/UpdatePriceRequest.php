<?php

namespace App\Http\Requests\Api;

use App\Enums\PriceType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;

class UpdatePriceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'type'     => ['sometimes', new Enum(PriceType::class)],
            'amount'   => ['sometimes', 'integer', 'min:0'],
        ];
    }
}
