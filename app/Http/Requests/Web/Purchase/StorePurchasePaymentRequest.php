<?php

namespace App\Http\Requests\Web\Purchase;

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
            'treasury_id'    => ['required', 'integer', 'exists:treasuries,id'],
            'amount'         => ['required', 'integer', 'min:1'],
            'payment_method' => ['required', 'string', 'in:CASH,BANK'],
            'paid_at'        => ['required', 'date'],
            'note'           => ['nullable', 'string'],
        ];
    }
}
