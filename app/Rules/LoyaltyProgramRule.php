<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class LoyaltyProgramRule implements ValidationRule
{
    /**
     * Run the validation rule.
     *
     * @param  \Closure(string, ?string=): \Illuminate\Translation\PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
      
    }

    public function rules(mixed $value, Closure $fail){
        
        if (empty($value['rule_name'])) {
            $fail('The rule_name field is required.');
        }

        if (empty($value['rule_type']) || !in_array($value['rule_type'], ['purchase_based', 'referral', 'bonus'])) {
            $fail('The rule_type must be one of: purchase_based, referral, bonus.');
        }

        if (isset($value['points_earned']) && (!is_numeric($value['points_earned']) || $value['points_earned'] < 0)) {
            $fail('The points_earned must be a non-negative number.');
        }

        if (isset($value['amount_per_point']) && $value['amount_per_point'] !== null && (!is_numeric($value['amount_per_point']) || $value['amount_per_point'] < 0)) {
            $fail('The amount_per_point must be a non-negative number or null.');
        }

        if (isset($value['min_purchase_amount']) && $value['min_purchase_amount'] !== null && (!is_numeric($value['min_purchase_amount']) || $value['min_purchase_amount'] < 0)) {
            $fail('The min_purchase_amount must be a non-negative number or null.');
        }

        if (isset($value['usage_limit']) && $value['usage_limit'] !== null && (!is_numeric($value['usage_limit']) || $value['usage_limit'] < 0)) {
            $fail('The usage_limit must be a non-negative integer or null.');
        }

        if (isset($value['active_from_date']) && isset($value['active_to_date'])) {
            if (!empty($value['active_from_date']) && !empty($value['active_to_date']) && $value['active_from_date'] > $value['active_to_date']) {
                $fail('The active_from_date must be before or equal to active_to_date.');
            }
        }
    }
}
