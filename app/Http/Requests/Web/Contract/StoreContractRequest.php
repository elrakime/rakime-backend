<?php

declare(strict_types=1);

namespace App\Http\Requests\Web\Contract;

use Illuminate\Foundation\Http\FormRequest;

class StoreContractRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'client_id'  => ['required', 'integer', 'exists:clients,id'],
            'account_id' => ['required', 'integer', 'exists:accounts,id'],
            'branch_id'  => ['required', 'integer', 'exists:branches,id'],
            'note'       => ['nullable', 'string'],
        ];
    }
}
