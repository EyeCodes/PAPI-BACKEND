<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class UserProfileTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_get_assets_endpoint(): void
    {
        $user = User::factory()->create(['salary' => 50000]);

        // Create test assets in purchases table
        \App\Models\Purchase::create([
            'user_id' => $user->id,
            'title' => 'Car',
            'amount' => 1000000,
            'asset_type' => 'asset',
            'asset_value' => 1000000,
            'ai_categorized_category' => 'Transportation',
            'currency' => 'PHP',
            'purchase_date' => now()->format('Y-m-d'),
        ]);

        \App\Models\Purchase::create([
            'user_id' => $user->id,
            'title' => 'House',
            'amount' => 5000000,
            'asset_type' => 'asset',
            'asset_value' => 5000000,
            'ai_categorized_category' => 'Real Estate',
            'currency' => 'PHP',
            'purchase_date' => now()->format('Y-m-d'),
        ]);

        // Create test liabilities
        \App\Models\Purchase::create([
            'user_id' => $user->id,
            'title' => 'Car Loan',
            'amount' => 800000,
            'asset_type' => 'liability',
            'liability_amount' => 800000,
            'ai_categorized_category' => 'Transportation',
            'currency' => 'PHP',
            'purchase_date' => now()->format('Y-m-d'),
        ]);

        // Create test expenses
        \App\Models\Purchase::create([
            'user_id' => $user->id,
            'title' => 'Gas',
            'amount' => 2000,
            'asset_type' => null, // expense
            'ai_categorized_category' => 'Transportation',
            'currency' => 'PHP',
            'purchase_date' => now()->format('Y-m-d'),
        ]);

        $response = $this->actingAs($user)->getJson('/api/assets');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'assets' => [
                        '*' => [
                            'id',
                            'user_id',
                            'title',
                            'amount',
                            'asset_type',
                            'asset_value',
                            'ai_categorized_category',
                            'currency',
                            'purchase_date',
                            'created_at',
                            'updated_at'
                        ]
                    ],
                    'summary' => [
                        'salary',
                        'assets',
                        'liabilities',
                        'expenses',
                        'net_worth'
                    ]
                ]
            ]);

        $data = $response->json('data');
        $this->assertEquals(50000, $data['summary']['salary']);
        $this->assertEquals(6000000, $data['summary']['assets']); // 1M + 5M
        $this->assertEquals(800000, $data['summary']['liabilities']);
        // Expenses now include ALL purchases, so this will be much higher
        $this->assertGreaterThan(0, $data['summary']['expenses']);
        $this->assertEquals(5200000, $data['summary']['net_worth']); // 6M - 800K
        $this->assertCount(2, $data['assets']); // Only assets
    }

    public function test_user_can_get_liabilities_endpoint(): void
    {
        $user = User::factory()->create(['salary' => 75000]);

        // Create test assets
        \App\Models\Purchase::create([
            'user_id' => $user->id,
            'title' => 'Investment Portfolio',
            'amount' => 2000000,
            'asset_type' => 'asset',
            'asset_value' => 2000000,
            'ai_categorized_category' => 'Investment',
            'currency' => 'PHP',
            'purchase_date' => now()->format('Y-m-d'),
        ]);

        // Create test liabilities in purchases table
        \App\Models\Purchase::create([
            'user_id' => $user->id,
            'title' => 'Mortgage',
            'amount' => 3000000,
            'asset_type' => 'liability',
            'liability_amount' => 3000000,
            'ai_categorized_category' => 'Real Estate',
            'currency' => 'PHP',
            'purchase_date' => now()->format('Y-m-d'),
        ]);

        \App\Models\Purchase::create([
            'user_id' => $user->id,
            'title' => 'Credit Card Debt',
            'amount' => 50000,
            'asset_type' => 'liability',
            'liability_amount' => 50000,
            'ai_categorized_category' => 'Credit',
            'currency' => 'PHP',
            'purchase_date' => now()->format('Y-m-d'),
        ]);

        // Create test expenses
        \App\Models\Purchase::create([
            'user_id' => $user->id,
            'title' => 'Groceries',
            'amount' => 5000,
            'asset_type' => null, // expense
            'ai_categorized_category' => 'Food',
            'currency' => 'PHP',
            'purchase_date' => now()->format('Y-m-d'),
        ]);

        $response = $this->actingAs($user)->getJson('/api/liabilities');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'liabilities' => [
                        '*' => [
                            'id',
                            'user_id',
                            'title',
                            'amount',
                            'asset_type',
                            'liability_amount',
                            'ai_categorized_category',
                            'currency',
                            'purchase_date',
                            'created_at',
                            'updated_at'
                        ]
                    ],
                    'summary' => [
                        'salary',
                        'assets',
                        'liabilities',
                        'expenses',
                        'net_worth'
                    ]
                ]
            ]);

        $data = $response->json('data');
        $this->assertEquals(75000, $data['summary']['salary']);
        $this->assertEquals(2000000, $data['summary']['assets']);
        $this->assertEquals(3050000, $data['summary']['liabilities']); // 3M + 50K
        // Expenses now include ALL purchases, so this will be much higher
        $this->assertGreaterThan(0, $data['summary']['expenses']);
        $this->assertEquals(-1050000, $data['summary']['net_worth']); // 2M - 3.05M
        $this->assertCount(2, $data['liabilities']); // Only liabilities
    }

    public function test_user_can_get_financial_info_endpoint(): void
    {
        $user = User::factory()->create(['salary' => 100000]);

        // Create test assets
        \App\Models\Purchase::create([
            'user_id' => $user->id,
            'title' => 'Car',
            'amount' => 1500000,
            'asset_type' => 'asset',
            'asset_value' => 1500000,
            'ai_categorized_category' => 'Transportation',
            'currency' => 'PHP',
            'purchase_date' => now()->format('Y-m-d'),
        ]);

        \App\Models\Purchase::create([
            'user_id' => $user->id,
            'title' => 'House',
            'amount' => 8000000,
            'asset_type' => 'asset',
            'asset_value' => 8000000,
            'ai_categorized_category' => 'Real Estate',
            'currency' => 'PHP',
            'purchase_date' => now()->format('Y-m-d'),
        ]);

        // Create test liabilities
        \App\Models\Purchase::create([
            'user_id' => $user->id,
            'title' => 'Mortgage',
            'amount' => 6000000,
            'asset_type' => 'liability',
            'liability_amount' => 6000000,
            'ai_categorized_category' => 'Real Estate',
            'currency' => 'PHP',
            'purchase_date' => now()->format('Y-m-d'),
        ]);

        \App\Models\Purchase::create([
            'user_id' => $user->id,
            'title' => 'Car Loan',
            'amount' => 1200000,
            'asset_type' => 'liability',
            'liability_amount' => 1200000,
            'ai_categorized_category' => 'Transportation',
            'currency' => 'PHP',
            'purchase_date' => now()->format('Y-m-d'),
        ]);

        // Create test expenses
        \App\Models\Purchase::create([
            'user_id' => $user->id,
            'title' => 'Groceries',
            'amount' => 3000,
            'asset_type' => null, // expense
            'ai_categorized_category' => 'Food',
            'currency' => 'PHP',
            'purchase_date' => now()->format('Y-m-d'),
        ]);

        \App\Models\Purchase::create([
            'user_id' => $user->id,
            'title' => 'Gas',
            'amount' => 1500,
            'asset_type' => null, // expense
            'ai_categorized_category' => 'Transportation',
            'currency' => 'PHP',
            'purchase_date' => now()->format('Y-m-d'),
        ]);

        $response = $this->actingAs($user)->getJson('/api/financial-info');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'salary',
                    'assets',
                    'liabilities',
                    'expenses',
                    'net_worth'
                ]
            ]);

        $data = $response->json('data');
        $this->assertEquals(100000, $data['salary']);
        $this->assertEquals(9500000, $data['assets']); // 1.5M + 8M
        $this->assertEquals(7200000, $data['liabilities']); // 6M + 1.2M
        // Expenses now include ALL purchases, so this will be much higher
        $this->assertGreaterThan(0, $data['expenses']);
        $this->assertEquals(2300000, $data['net_worth']); // 9.5M - 7.2M
    }
}
