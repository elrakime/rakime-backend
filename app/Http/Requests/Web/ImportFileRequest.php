<?php

declare(strict_types=1);

namespace App\Http\Requests\Web;

use Illuminate\Foundation\Http\FormRequest;

class ImportFileRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'file'     => ['required', 'file', 'mimes:txt,text,plain'],
            'draw_day' => ['required', 'integer', 'min:1', 'max:30'],
        ];
    }
}
