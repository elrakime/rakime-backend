<?php

namespace App\Http\Requests\Web\Wallet;

use App\Enums\WalletMovementType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class DepositWalletRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'type'   => ['required', Rule::in([
                WalletMovementType::DEPOSIT->value,
                WalletMovementType::ADJUSTMENT->value,
            ])],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'note'   => ['nullable', 'string', 'max:255'],
        ];
    }
}
