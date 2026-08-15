<?php

namespace App\Http\Requests\Web\AccountDrawLock;

use Illuminate\Foundation\Http\FormRequest;

class StoreAccountDrawLockRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'account_id' => ['required', 'integer', 'exists:accounts,id'],
        ];
    }
}
