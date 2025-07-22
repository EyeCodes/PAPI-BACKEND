<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class ValidRuleTypeFields implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $data = request()->all();
        $type = $data['rule_type'] ?? null;

         if ($type === 'point_based') {
            if (empty($data['amount_per_point']) || !is_numeric($data['amount_per_point'])) {
                $fail('Amount per point is required and must be numeric for point-based rules.');
            }
        }

        if ($type === 'birthday_bonus') {
            if (empty($data['points_earned']) || !is_numeric($data['points_earned'])) {
                $fail('Points earned is required and must be numeric for birthday bonus rules.');
            }
        }
    }
}
