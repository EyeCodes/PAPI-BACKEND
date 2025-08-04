<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\FinancialCategory;

class FinancialCategorySeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            [
                'name' => 'Food & Dining',
                'description' => 'Restaurants, cafes, and food delivery',
                'color' => '#FF6B6B',
                'icon' => '🍽️',
                'sort_order' => 1,
            ],
            [
                'name' => 'Shopping',
                'description' => 'Retail purchases and online shopping',
                'color' => '#4ECDC4',
                'icon' => '🛍️',
                'sort_order' => 2,
            ],
            [
                'name' => 'Transportation',
                'description' => 'Public transport, fuel, and ride-sharing',
                'color' => '#45B7D1',
                'icon' => '🚗',
                'sort_order' => 3,
            ],
            [
                'name' => 'Entertainment',
                'description' => 'Movies, games, and leisure activities',
                'color' => '#96CEB4',
                'icon' => '🎬',
                'sort_order' => 4,
            ],
            [
                'name' => 'Healthcare',
                'description' => 'Medical expenses and health-related purchases',
                'color' => '#FFEAA7',
                'icon' => '🏥',
                'sort_order' => 5,
            ],
            [
                'name' => 'Utilities',
                'description' => 'Electricity, water, internet, and phone bills',
                'color' => '#DDA0DD',
                'icon' => '💡',
                'sort_order' => 6,
            ],
            [
                'name' => 'Education',
                'description' => 'Books, courses, and educational materials',
                'color' => '#98D8C8',
                'icon' => '📚',
                'sort_order' => 7,
            ],
            [
                'name' => 'Travel',
                'description' => 'Vacations, flights, and accommodation',
                'color' => '#F7DC6F',
                'icon' => '✈️',
                'sort_order' => 8,
            ],
            [
                'name' => 'Other',
                'description' => 'Miscellaneous expenses',
                'color' => '#BDC3C7',
                'icon' => '📦',
                'sort_order' => 9,
            ],
        ];

        foreach ($categories as $category) {
            FinancialCategory::updateOrCreate(
                ['name' => $category['name']],
                $category
            );
        }
    }
}
