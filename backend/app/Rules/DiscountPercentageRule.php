<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class DiscountPercentageRule implements ValidationRule
{
    /**
     * Run the validation rule.
     *
     * @param  \Closure(string, ?string=): \Illuminate\Translation\PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
            if ($value !== null && (!is_numeric($value) || $value < 0 || $value > 100)) {
            $fail("The {$attribute} must be a percentage between 0 and 100.");
        }
    }
}
