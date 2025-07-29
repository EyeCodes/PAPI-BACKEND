<?php

namespace Database\Seeders;

use App\Models\FinancialCategory;
use Illuminate\Database\Seeder;

class FinancialCategorySeeder extends Seeder
{
    public function run(): void
    {
        // Clear existing categories
        FinancialCategory::truncate();

        $categories = [
            [
                'name' => 'Asset',
                'slug' => 'asset',
                'description' => 'Items that retain or increase in value over time',
                'icon' => '💰',
                'color' => '#32CD32',
                'is_default' => true,
                'sort_order' => 1,
                'ai_keywords' => ['asset', 'investment', 'savings', 'property', 'vehicle', 'car', 'house', 'land', 'jewelry', 'gold', 'silver', 'stocks', 'bonds', 'mutual fund', 'crypto', 'bitcoin', 'real estate', 'business', 'equipment', 'machinery', 'furniture', 'electronics', 'art', 'collectibles', 'antiques', 'wine', 'watches', 'diamonds', 'precious metals']
            ],
            [
                'name' => 'Liability',
                'slug' => 'liability',
                'description' => 'Debts and financial obligations',
                'icon' => '💳',
                'color' => '#FF6B6B',
                'is_default' => true,
                'sort_order' => 2,
                'ai_keywords' => ['liability', 'debt', 'loan', 'credit', 'mortgage', 'car loan', 'student loan', 'personal loan', 'business loan', 'credit card', 'overdraft', 'payday loan', 'home equity', 'line of credit', 'borrowed', 'owe', 'debtor', 'creditor', 'interest', 'principal', 'payment', 'installment', 'financing', 'lease', 'rental', 'subscription', 'bill', 'outstanding', 'balance', 'arrears', 'default', 'collection']
            ]
        ];

        foreach ($categories as $category) {
            FinancialCategory::create($category);
        }
    }
}
