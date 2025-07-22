<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class RewardTypeRule implements ValidationRule
{
    /**
     * Run the validation rule.
     *
     * @param  \Closure(string, ?string=): \Illuminate\Translation\PotentiallyTranslatedString  $fail
     */


    protected array $allowed = [
        'fixed_amount',
        'percentage',
        'free_item' //not sure
    ];

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if(!in_array($value, $this->allowed, true)){
            $fail("The :attribute must be one of: " . implode(', ', $this->allowed) . '.');
        }
    }
}
