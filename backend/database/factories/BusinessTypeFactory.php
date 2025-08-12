<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\BusinessType>
 */
class BusinessTypeFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $businessTypes = [
            'Restaurant',
            'Information Technology (IT)',
            'Retail Store',
            'Construction',
            'Healthcare',
            'Automotive',
            'Beauty & Wellness',
            'Education & Training',
            'Real Estate',
            'Manufacturing',
            'Transportation & Logistics',
            'Financial Services',
            'Marketing & Advertising',
            'Legal Services',
            'Consulting',
            'E-commerce',
            'Agriculture',
            'Entertainment',
            'Hospitality',
            'Fitness & Sports',
            'Photography',
            'Cleaning Services',
            'Security Services',
            'Telecommunications',
            'Travel & Tourism',
            'Printing & Publishing',
            'Pet Services',
            'Home Services',
            'Grocery & Supermarket',
            'Pharmacy',
        ];

        return [
            'type' => fake()->randomElement($businessTypes),
            'description' => fake()->sentence(),
            'is_active' => true,
        ];
    }
}
