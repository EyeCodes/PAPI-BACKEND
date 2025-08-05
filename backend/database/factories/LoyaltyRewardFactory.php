<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\LoyaltyReward;
use App\Models\LoyaltyProgramRule;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\LoyaltyReward>
 */
class LoyaltyRewardFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
        'loyalty_program_rule_id' => LoyaltyProgramRuleFactory::new()->create()->id,
        'reward_name' => $this->faker->word,
        'description' => $this->faker->sentence,
        'reward_type' => $this->faker->randomElement(['discount', 'voucher']),
        'point_cost'=> $this->faker->numberBetween(1, 100),
        'discount_value' => $this->faker->randomFloat(2, 0, 100),
        'discount_percentage' => $this->faker->randomFloat(2, 0, 100),
        'voucher_code'=> $this->faker->uuid,
        'is_active' => $this->faker->boolean,
        'max_redemption_rate' => $this->faker->numberBetween(1, 1000),
        'expiration_days'=> $this->faker->numberBetween(1, 365),
        ];
    }
}
