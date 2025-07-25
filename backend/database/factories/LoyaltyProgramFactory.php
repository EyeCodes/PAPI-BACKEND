<?php

namespace Database\Factories;

use App\Models\LoyaltyProgram;
use App\Models\Company;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\LoyaltyProgram>
 */
class LoyaltyProgramFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */

     protected $model = LoyaltyProgram::class;
    public function definition(): array
    {
        return [
        'company_id' => Company::factory()->create()->id,
        'program_name' => $this->faker->word,
        'description' => $this->faker->sentence,
        'program_type' => $this->faker->randomElement(['Point-Base']),
        'is_active' => $this->faker->boolean,
        'start_date' => $this->faker->dateTimeBetween('-1 month', '+1 month'),
        'end_date' => $this->faker->dateTimeBetween('+1 month', '+6 months'),
        'instructions' => $this->faker->paragraph,
        ];
    }
}
