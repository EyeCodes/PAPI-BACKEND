<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class CurrencyCodeRule implements ValidationRule
{
    /**
     * Run the validation rule.
     *
     * @param  \Closure(string, ?string=): \Illuminate\Translation\PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $allowed = ['PHP'];

        if(!in_array(strtoupper($value), $allowed, true)){
            $fail('The :attribute must be one of: ' . implode(',', $allowed));
        }
    }
}
