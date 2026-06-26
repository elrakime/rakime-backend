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
            'wallet_id'     => ['required', 'integer', 'exists:wallets,id'],
            'amount'         => ['required', 'integer', 'min:1'],
            'payment_method' => ['required', 'string'],
            'paid_at'        => ['required', 'date'],
            'note'           => ['nullable', 'string'],
        ];
    }
}
