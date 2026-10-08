<?php

declare(strict_types=1);

namespace App\Http\Requests\Web\Stats;

use Illuminate\Foundation\Http\FormRequest;

class ContractStatsLiveRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'branch_id'  => ['nullable', 'integer', 'exists:branches,id'],
            'account_id' => ['nullable', 'integer', 'exists:accounts,id'],
            'from_date'  => ['nullable', 'date'],
            'to_date'    => ['nullable', 'date'],
        ];
    }
}
