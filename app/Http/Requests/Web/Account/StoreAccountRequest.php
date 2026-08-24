<?php

namespace App\Http\Requests\Web\Account;

use App\Rules\CcpKey;
use Illuminate\Foundation\Http\FormRequest;

class StoreAccountRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'name'                => ['required', 'string', 'max:255'],
            'ccp_number'          => ['required', 'string', 'max:255', 'unique:accounts,ccp_number'],
            'ccp_key'             => ['required', 'string', 'max:255', new CcpKey],
            'draw_day'            => ['required', 'integer', 'min:1', 'max:31'],
            'min_withdraw_amount' => ['required', 'numeric', 'min:0'],
            'max_withdraw_count'  => ['required', 'integer', 'min:1'],
        ];
    }
}
