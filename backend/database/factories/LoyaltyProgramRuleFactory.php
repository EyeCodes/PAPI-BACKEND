<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\LoyaltyProgramRule;
use App\Models\LoyaltyProgram;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\LoyaltyProgramRule>
 */
class LoyaltyProgramRuleFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    protected $model = LoyaltyProgramRule::class;

    public function definition(): array
    {
        return [
        'loyalty_program_id' => LoyaltyProgramFactory::new()->create()->id,
        'rule_name' => $this->faker->word,
        'rule_type'=> $this->faker->randomElement(['purchase_based', 'referral_bonus', 'birthday']),
        'points_earned'=> $this->faker->numberBetween(1, 100),
        'amount_per_point' => $this->faker->randomFloat(2, 0.01, 10.00),
        'min_purchase_amount' => $this->faker->randomFloat(2, 10.00, 1000.00),
        'product_category_id'=> null, // Assuming product category is optional
        'product_item_id'=> null, // Assuming product item is optional
        'is_active' => $this->faker->boolean,
        'active_from_date' => $this->faker->dateTimeBetween('-1 month', '+1 month'),
        'active_to_date' => $this->faker->dateTimeBetween('+1 month', '+6 months'),
        'usage_limit' => $this->faker->numberBetween(1, 1000),
        ];
    }
}
