<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class NonNegativeNumberRule implements ValidationRule
{
    /**
     * Run the validation rule.
     *
     * @param  \Closure(string, ?string=): \Illuminate\Translation\PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
         if (!is_numeric($value)) {
            $fail("The {$attribute} must be a number.");
            return;
        }

        if ($value < 0) {
            $fail("The {$attribute} must be zero or greater.");
        }
    }
}
