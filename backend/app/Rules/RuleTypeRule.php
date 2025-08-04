<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class RuleTypeRule implements ValidationRule
{
    /**
     * Run the validation rule.
     *
     * @param  \Closure(string, ?string=): \Illuminate\Translation\PotentiallyTranslatedString  $fail
     */
 protected array $allowed = [
        'purchase_based',
        'birthday_bonus',
        'referral_bonus',
    ];

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (!in_array($value, $this->allowed, true)) {
            $fail("The $attribute must be one of: " . implode(', ', $this->allowed) . '.');
        }
    }
}
