<?php

declare(strict_types=1);

namespace App\Http\Requests\Web\FinancialRecord;

use Illuminate\Foundation\Http\FormRequest;

class UpdateFinancialRecordRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'revenues'          => ['sometimes', 'nullable', 'array'],
            'expenses'          => ['sometimes', 'nullable', 'array'],
            'revenues.*.amount' => ['required_with:revenues', 'numeric'],
            'revenues.*.count'  => ['nullable', 'integer', 'min:1'],
            'expenses.*.amount' => ['required_with:expenses', 'numeric'],
            'expenses.*.count'  => ['nullable', 'integer', 'min:1'],
            'note'              => ['sometimes', 'nullable', 'string'],
        ];
    }
}
