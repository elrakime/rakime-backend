<?php

namespace App\Rules;

use App\Services\CcpService;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class CcpKey implements ValidationRule
{
    public function __construct(
        private string $ccpNumberField = 'ccp_number'
    ) {}

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $ccpNumber = request()->input($this->ccpNumberField);

        if (empty($ccpNumber)) {
            $fail(__('validation.required', ['attribute' => $this->ccpNumberField]));

            return;
        }

        $expectedKey = (new CcpService($ccpNumber))->getKey();

        if ($value !== $expectedKey) {
            $fail('The :attribute is invalid.');
        }
    }
}
