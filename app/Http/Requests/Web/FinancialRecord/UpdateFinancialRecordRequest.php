<?php

declare(strict_types=1);

namespace App\Http\Requests\Web\FinancialRecord;

use Illuminate\Foundation\Http\FormRequest;

class UpdateFinancialRecordRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'revenues'   => ['sometimes', 'nullable', 'array'],
            'expenses'   => ['sometimes', 'nullable', 'array'],
            'revenues.*' => ['numeric'],
            'expenses.*' => ['numeric'],
            'note'       => ['sometimes', 'nullable', 'string'],
        ];
    }
}
