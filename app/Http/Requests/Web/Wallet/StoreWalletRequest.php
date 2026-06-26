<?php

namespace App\Http\Requests\Web\Wallet;

use Illuminate\Foundation\Http\FormRequest;

class StoreWalletRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'owner_type' => ['nullable', 'string', 'max:255'],
            'owner_id'   => ['nullable', 'integer'],
            'name'       => ['required', 'string', 'max:255'],
            'balance'    => ['nullable', 'numeric', 'min:0'],
        ];
    }
}
