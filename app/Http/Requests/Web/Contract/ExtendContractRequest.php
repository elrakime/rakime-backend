<?php

declare(strict_types=1);

namespace App\Http\Requests\Web\Contract;

use Illuminate\Foundation\Http\FormRequest;

class ExtendContractRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'months_count' => ['required', 'integer', 'min:1'],
        ];
    }
}
