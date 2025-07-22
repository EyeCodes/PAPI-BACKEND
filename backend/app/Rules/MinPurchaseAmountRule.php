<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class MinPurchaseAmountRule implements ValidationRule
{
    /**
     * Run the validation rule.
     *
     * @param  \Closure(string, ?string=): \Illuminate\Translation\PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
            if ($value !== null && (!is_numeric($value) || $value < 0)) {
            $fail("The $attribute must be a non-negative number or null.");
        }
    }
}
