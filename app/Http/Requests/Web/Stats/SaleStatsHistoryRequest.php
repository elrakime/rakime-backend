<?php

declare(strict_types=1);

namespace App\Http\Requests\Web\Stats;

use Illuminate\Foundation\Http\FormRequest;

class SaleStatsHistoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'branch_id'  => ['nullable', 'integer', 'exists:branches,id'],
            'from_month' => ['nullable', 'date_format:Y-m'],
            'to_month'   => ['nullable', 'date_format:Y-m'],
        ];
    }
}
