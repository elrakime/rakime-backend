<?php

namespace App\Http\Requests\Web\Treasury;

use Illuminate\Foundation\Http\FormRequest;

class UpdateTreasuryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['sometimes', 'required', 'string', 'max:255'],
        ];
    }
}
