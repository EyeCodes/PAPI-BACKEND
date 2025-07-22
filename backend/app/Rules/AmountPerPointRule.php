<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class AmountPerPointRule implements ValidationRule
{
    /**
     * Run the validation rule.
     *
     * @param  \Closure(string, ?string=): \Illuminate\Translation\PotentiallyTranslatedString  $fail
     */

    protected string $ruleType;

    public function __construct(string $ruleType)
    {
        $this->ruleType = $ruleType;
    }


    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (
            in_array($this->ruleType, ['purchase_based', 'birthday_bonus', 'referral_bonus'])
            && (empty($value) || !is_numeric($value) || $value <= 0)
        ) {
            $fail("The $attribute is required and must not be a negative number for {$this->ruleType}.");
        }
    }
}
