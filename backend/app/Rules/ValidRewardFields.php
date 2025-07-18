<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class ValidRewardFields implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $data = request()->all();
        $type = $data['reward_type'] ?? null;

        if ($type === 'discount') {
            if (empty($data['discount_value']) && empty($data['discount_percentage'])) {
                $fail('Either discount value or discount percentage must be provided for discount rewards.');
            }
        }

        if ($type === 'item' && empty($data['item_id'])) {
            $fail('Item ID must be provided for item-type rewards.');
        }

        if ($type === 'voucher' && empty($data['voucher_code'])) {
            $fail('Voucher code must be provided for voucher-type rewards.');
        }
    }
}
