<?php

namespace App\Http\Requests\Web\PurchaseReturn;

use Illuminate\Foundation\Http\FormRequest;

class ApprovePurchaseReturnRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'wallet_id' => ['required', 'integer', 'exists:wallets,id'],
        ];
    }
}
