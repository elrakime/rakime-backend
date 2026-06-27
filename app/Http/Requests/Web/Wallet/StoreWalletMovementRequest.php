<?php

namespace App\Http\Requests\Web\Wallet;

use Illuminate\Foundation\Http\FormRequest;

class StoreWalletMovementRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'amount' => ['required', 'numeric', 'min:0.01'],
            'note'   => ['nullable', 'string', 'max:255'],
        ];
    }
}
