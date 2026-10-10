<?php

namespace App\Http\Requests\Web\Client;

use Illuminate\Foundation\Http\FormRequest;

class StoreClientDocumentRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'document' => ['required', 'file', 'max:10240'],
        ];
    }
}
