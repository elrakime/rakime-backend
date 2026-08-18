<?php

declare(strict_types=1);

namespace App\Http\Requests\Web\FinancialRecord;

use Illuminate\Foundation\Http\FormRequest;

class StoreFinancialRecordRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'client_id'   => ['required', 'integer', 'exists:clients,id'],
            'contract_id' => ['nullable', 'integer', 'exists:contracts,id'],
            'revenues'    => ['nullable', 'array'],
            'expenses'    => ['nullable', 'array'],
            'revenues.*'  => ['numeric'],
            'expenses.*'  => ['numeric'],
            'note'        => ['nullable', 'string'],
        ];
    }
}
