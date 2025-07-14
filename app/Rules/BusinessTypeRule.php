<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class BusinessTypeRule implements ValidationRule
{
    /**
     * Run the validation rule.
     *
     * @param  \Closure(string, ?string=): \Illuminate\Translation\PotentiallyTranslatedString  $fail
     */


    protected array $allowedTypes = [
        'Restaurant',
        'Retailer',
        'Service Provider',
        'E-Commerce Business',
        'Manufacturing',
        'Construction',
        'Heathcare',
        'Education',
        'Hospitality',
        'Agriculture',
        'Real Estate',
        'Financial Services',
    ];


    //this will restrict business types
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if(! in_array($value, $this->allowedTypes, true)){
            $fail('The :attribute must be one of: ' . implode(', ', $this->allowedTypes));
        }
    }
}
