<?php

namespace App\Http\Requests\Web\Account;

use Illuminate\Foundation\Http\FormRequest;

class ImportAccountRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'file' => ['required', 'file', 'mimes:txt,text,plain'],
        ];
    }
}
