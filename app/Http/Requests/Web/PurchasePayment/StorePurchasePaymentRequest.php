<?php

namespace App\Http\Requests\Web\PurchasePayment;

use Illuminate\Foundation\Http\FormRequest;

class StorePurchasePaymentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'wallet_id' => ['nullable', 'integer', 'exists:wallets,id'],
            'amount'    => ['required', 'numeric', 'min:0.01'],
            'note'      => ['nullable', 'string'],
        ];
    }
}
