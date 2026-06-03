<?php

namespace App\Http\Requests\Web\Account;

use Illuminate\Foundation\Http\FormRequest;

class StoreAccountRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'name'                => ['required', 'string', 'max:255'],
            'ccp_number'          => ['required', 'string', 'max:255', 'unique:accounts,ccp_number'],
            'ccp_key'             => ['required', 'string', 'max:255'],
            'draw_day'            => ['required', 'integer', 'min:1', 'max:31'],
            'min_withdraw_amount' => ['required', 'integer', 'min:0'],
            'max_withdraw_count'  => ['required', 'integer', 'min:1'],
        ];
    }
}
