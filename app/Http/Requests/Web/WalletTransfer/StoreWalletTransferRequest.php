<?php

namespace App\Http\Requests\Web\WalletTransfer;

use Illuminate\Foundation\Http\FormRequest;

class StoreWalletTransferRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'from_wallet_id' => ['required', 'exists:wallets,id'],
            'to_wallet_id'   => ['required', 'exists:wallets,id', 'different:from_wallet_id'],
            'amount'         => ['required', 'numeric', 'min:0.01'],
            'note'           => ['nullable', 'string', 'max:255'],
        ];
    }
}
