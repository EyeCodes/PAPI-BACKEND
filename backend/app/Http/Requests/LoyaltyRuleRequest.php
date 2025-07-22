<?php

namespace App\Http\Requests;

use App\Rules\MinPurchaseAmountRule;
use App\Rules\RuleTypeRule;
use Illuminate\Foundation\Http\FormRequest;
use App\Rules\ValidRuleTypeFields;

class LoyaltyRuleRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'rule_type' => ['required', 'string', new RuleTypeRule],
            new ValidRuleTypeFields(),
            'loyalty_program_id' => 'required|exists:loyaltyPrograms,id',
            'rule_name' => 'required|string|max:255',
            'points_earned' => 'nullable|numeric|min:0',
            'amount_per_point' => 'nullable|numeric|min:0.01',
            'min_purchase_amount' => ['nullable','numeric','min:0', new MinPurchaseAmountRule],
            'product_category_id' => 'nullable|integer',
            'product_item_id' => 'nullable|integer',
            'active_from_date' => 'nullable|date',
            'active_to_date' => 'nullable|date|after_or_equal:active_from_date'
        ];
    }

    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            $type = $this->input('rule_type');

            if (in_array($type, ['purchase_based', 'birthday_bonus', 'referral_bonus'])) {
                if (!$this->filled('amount_per_point')) {
                    $validator->errors()->add('amount_per_point', 'amount_per_point is required for this rule type.');
                }
            } else {
                if (!$this->filled('points_earned')) {
                    $validator->errors()->add('points_earned', 'points_earned is required for bonus-type rules.');
                }
            }
        });
    }
}
