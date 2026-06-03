<?php

namespace App\Http\Requests\Web\Account;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateAccountRequest extends FormRequest
{
    public function rules(): array
    {
        $id = $this->route('account')?->id;

        return [
            'name'                => ['sometimes', 'required', 'string', 'max:255'],
            'ccp_number'          => ['sometimes', 'required', 'string', 'max:255', Rule::unique('accounts', 'ccp_number')->ignore($id)],
            'ccp_key'             => ['sometimes', 'required', 'string', 'max:255'],
            'draw_day'            => ['sometimes', 'required', 'integer', 'min:1', 'max:31'],
            'min_withdraw_amount' => ['sometimes', 'required', 'integer', 'min:0'],
            'max_withdraw_count'  => ['sometimes', 'required', 'integer', 'min:1'],
        ];
    }
}
