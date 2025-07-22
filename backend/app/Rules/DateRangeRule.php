<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class DateRangeRule implements ValidationRule
{
    /**
     * Run the validation rule.
     *
     * @param  \Closure(string, ?string=): \Illuminate\Translation\PotentiallyTranslatedString  $fail
     */
    
    protected ?string $from;
    protected ?string $to;

    public function __construct(?string $from, ?string $to)
    {
        $this->from = $from;
        $this->to = $to;
    }
    
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if ($this->from && $this->to && $this->from > $this->to) {
            $fail('The active_from_date must be before or equal to active_to_date.');
        }
    }
}
