<?php

declare(strict_types=1);

namespace App\Http\Requests\Web\FinancialRecord;

use Illuminate\Foundation\Http\FormRequest;

class StoreFinancialRecordRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'client_id'         => ['required', 'integer', 'exists:clients,id'],
            'contract_id'       => ['nullable', 'integer', 'exists:contracts,id'],
            'revenues'          => ['nullable', 'array'],
            'expenses'          => ['nullable', 'array'],
            'revenues.*.amount' => ['required_with:revenues', 'numeric'],
            'revenues.*.count'  => ['nullable', 'integer', 'min:1'],
            'expenses.*.amount' => ['required_with:expenses', 'numeric'],
            'expenses.*.count'  => ['nullable', 'integer', 'min:1'],
            'note'              => ['nullable', 'string'],
        ];
    }
}
