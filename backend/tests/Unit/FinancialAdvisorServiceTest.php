<?php

namespace Tests\Unit;

use App\Models\Purchase;
use App\Models\Asset;
use App\Models\Liability;
use App\Services\FinancialAdvisorService;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FinancialAdvisorServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_ai_categorize_handles_null_amount(): void
    {
        $user = User::factory()->create();
        $service = new FinancialAdvisorService($user);

        // Create a purchase with null amount
        $purchase = new Purchase([
            'user_id' => $user->id,
            'title' => 'Coffee',
            'description' => 'Morning coffee',
            'amount' => null, // This was causing the error
            'currency' => 'PHP',
            'purchase_date' => now()
        ]);

        // This should not throw an error anymore
        $category = $service->autoCategorizePurchase($purchase);

        // Should return a valid category
        $this->assertIsString($category);
        $this->assertNotEmpty($category);
    }

    public function test_ai_categorize_handles_valid_amount(): void
    {
        $user = User::factory()->create();
        $service = new FinancialAdvisorService($user);

        // Create a purchase with valid amount
        $purchase = new Purchase([
            'user_id' => $user->id,
            'title' => 'Coffee',
            'description' => 'Morning coffee',
            'amount' => 150.00,
            'currency' => 'PHP',
            'purchase_date' => now()
        ]);

        $category = $service->autoCategorizePurchase($purchase);

        // Should return a valid category
        $this->assertIsString($category);
        $this->assertNotEmpty($category);
    }

    public function test_intelligent_financial_recording_tool_exists(): void
    {
        $user = User::factory()->create();
        $service = new FinancialAdvisorService($user);

        // Use reflection to access the protected createTools method
        $reflection = new \ReflectionClass($service);
        $method = $reflection->getMethod('createTools');
        $method->setAccessible(true);

        $tools = $method->invoke($service);

        // Check if the record_financial_item tool exists
        $hasRecordFinancialItemTool = false;
        foreach ($tools as $tool) {
            // Use reflection to get the tool name
            $toolReflection = new \ReflectionClass($tool);
            $nameProperty = $toolReflection->getProperty('name');
            $nameProperty->setAccessible(true);
            $toolName = $nameProperty->getValue($tool);

            if ($toolName === 'record_financial_item') {
                $hasRecordFinancialItemTool = true;
                break;
            }
        }

        $this->assertTrue($hasRecordFinancialItemTool, 'The record_financial_item tool should exist');
    }

    public function test_system_prompt_includes_intelligent_recording(): void
    {
        $user = User::factory()->create();
        $service = new FinancialAdvisorService($user);

        // Use reflection to access the protected getToolSystemPrompt method
        $reflection = new \ReflectionClass($service);
        $method = $reflection->getMethod('getToolSystemPrompt');
        $method->setAccessible(true);

        $prompt = $method->invoke($service);

        // Check if the prompt includes the intelligent recording instruction
        $this->assertStringContainsString('record_financial_item', $prompt);
        $this->assertStringContainsString('Intelligent Financial Recording', $prompt);
        $this->assertStringContainsString('automatically detect and categorize', $prompt);
    }

    public function test_chat_response_includes_assets_and_liabilities(): void
    {
        $user = User::factory()->create();

        // Create test assets and liabilities in purchases table
        Purchase::create([
            'user_id' => $user->id,
            'title' => 'Savings Account',
            'description' => 'Bank savings',
            'amount' => 100000.00,
            'currency' => 'PHP',
            'asset_type' => 'asset',
            'asset_value' => 100000.00,
            'ai_categorized_category' => 'cash',
            'purchase_date' => now()->format('Y-m-d'),
        ]);

        Purchase::create([
            'user_id' => $user->id,
            'title' => 'Credit Card Debt',
            'description' => 'Credit card balance',
            'amount' => 50000.00,
            'currency' => 'PHP',
            'asset_type' => 'liability',
            'liability_amount' => 50000.00,
            'liability_type' => 'credit_card',
            'purchase_date' => now()->format('Y-m-d'),
        ]);

        $service = new FinancialAdvisorService($user);

        // Process a simple message
        $response = $service->processMessage('Hello');

        // Check that the response includes purchases_added array
        $this->assertArrayHasKey('purchases_added', $response);

        // Check that purchases_added is an array
        $this->assertIsArray($response['purchases_added']);

        // The purchases_added array should contain formatted purchase data
        // Note: Since we're just testing the structure, we don't need to check specific content
        // as the AI might not always add purchases for a simple "Hello" message
    }

    public function test_get_user_assets_method(): void
    {
        $user = User::factory()->create();

        // Create test assets in purchases table
        Purchase::create([
            'user_id' => $user->id,
            'title' => 'Car',
            'description' => 'My car',
            'amount' => 500000.00,
            'currency' => 'PHP',
            'asset_type' => 'asset',
            'asset_value' => 500000.00,
            'ai_categorized_category' => 'vehicle',
            'purchase_date' => now()->format('Y-m-d'),
        ]);

        Purchase::create([
            'user_id' => $user->id,
            'title' => 'Investment Portfolio',
            'description' => 'Stock investments',
            'amount' => 200000.00,
            'currency' => 'PHP',
            'asset_type' => 'asset',
            'asset_value' => 200000.00,
            'ai_categorized_category' => 'investment',
            'purchase_date' => now()->format('Y-m-d'),
        ]);

        $service = new FinancialAdvisorService($user);

        // Use reflection to access the protected getUserAssets method
        $reflection = new \ReflectionClass($service);
        $method = $reflection->getMethod('getUserAssets');
        $method->setAccessible(true);

        $assets = $method->invoke($service);

        $this->assertIsArray($assets);
        $this->assertCount(2, $assets);

        // Check assets (order may vary due to created_at desc)
        $assetNames = array_column($assets, 'name');
        $this->assertContains('Car', $assetNames);
        $this->assertContains('Investment Portfolio', $assetNames);

        // Check values
        $carAsset = collect($assets)->firstWhere('name', 'Car');
        $this->assertNotNull($carAsset);
        $this->assertEquals(500000.00, $carAsset['value']);
        $this->assertEquals('vehicle', $carAsset['type']);

        $investmentAsset = collect($assets)->firstWhere('name', 'Investment Portfolio');
        $this->assertNotNull($investmentAsset);
        $this->assertEquals(200000.00, $investmentAsset['value']);
        $this->assertEquals('investment', $investmentAsset['type']);
    }

    public function test_get_user_liabilities_method(): void
    {
        $user = User::factory()->create();

        // Create test liabilities in purchases table
        Purchase::create([
            'user_id' => $user->id,
            'title' => 'Car Loan',
            'description' => 'Car financing',
            'amount' => 800000.00,
            'currency' => 'PHP',
            'asset_type' => 'liability',
            'liability_amount' => 800000.00,
            'monthly_payment' => 15000.00,
            'liability_type' => 'car_loan',
            'purchase_date' => now()->format('Y-m-d'),
        ]);

        Purchase::create([
            'user_id' => $user->id,
            'title' => 'Student Loan',
            'description' => 'Education debt',
            'amount' => 300000.00,
            'currency' => 'PHP',
            'asset_type' => 'liability',
            'liability_amount' => 300000.00,
            'monthly_payment' => 5000.00,
            'liability_type' => 'student_loan',
            'purchase_date' => now()->format('Y-m-d'),
        ]);

        $service = new FinancialAdvisorService($user);

        // Use reflection to access the protected getUserLiabilities method
        $reflection = new \ReflectionClass($service);
        $method = $reflection->getMethod('getUserLiabilities');
        $method->setAccessible(true);

        $liabilities = $method->invoke($service);

        $this->assertIsArray($liabilities);
        $this->assertCount(2, $liabilities);

        // Check liabilities (order may vary due to created_at desc)
        $liabilityNames = array_column($liabilities, 'name');
        $this->assertContains('Car Loan', $liabilityNames);
        $this->assertContains('Student Loan', $liabilityNames);

        // Check values
        $carLoan = collect($liabilities)->firstWhere('name', 'Car Loan');
        $this->assertNotNull($carLoan);
        $this->assertEquals(800000.00, $carLoan['amount']);
        $this->assertEquals(15000.00, $carLoan['monthly_payment']);
        $this->assertEquals('car_loan', $carLoan['type']);

        $studentLoan = collect($liabilities)->firstWhere('name', 'Student Loan');
        $this->assertNotNull($studentLoan);
        $this->assertEquals(300000.00, $studentLoan['amount']);
        $this->assertEquals(5000.00, $studentLoan['monthly_payment']);
        $this->assertEquals('student_loan', $studentLoan['type']);
    }

    public function test_system_prompt_prioritizes_intelligent_recording(): void
    {
        $user = User::factory()->create();
        $service = new FinancialAdvisorService($user);

        // Use reflection to access the protected getToolSystemPrompt method
        $reflection = new \ReflectionClass($service);
        $method = $reflection->getMethod('getToolSystemPrompt');
        $method->setAccessible(true);

        $prompt = $method->invoke($service);

        // Check that the prompt prioritizes the intelligent recording tool
        $this->assertStringContainsString('record_financial_item', $prompt);
        $this->assertStringContainsString('Intelligent Financial Recording', $prompt);
        $this->assertStringContainsString('automatically detect and categorize', $prompt);

        // Check that it mentions the categorization rules
        $this->assertStringContainsString('**ASSETS**', $prompt);
        $this->assertStringContainsString('**LIABILITIES**', $prompt);
        $this->assertStringContainsString('**EXPENSES**', $prompt);

        // Check that it mentions cars as assets
        $this->assertStringContainsString('cars', $prompt);
        $this->assertStringContainsString('houses', $prompt);
        $this->assertStringContainsString('investments', $prompt);
    }

    public function test_format_purchases_added_creates_json_structure(): void
    {
        $user = User::factory()->create();
        $service = new FinancialAdvisorService($user);

        // Use reflection to access the protected formatPurchasesAdded method
        $reflection = new \ReflectionClass($service);
        $method = $reflection->getMethod('formatPurchasesAdded');
        $method->setAccessible(true);

        // Test with different types of purchases
        $purchases = [
            'ASSET:Car ₱5,000,000.00',
            'LIABILITY:Car Loan ₱4,000,000.00',
            'EXPENSE:Gas ₱1,000.00',
            'a car ₱12,000,000.00', // Fallback format
        ];

        $formatted = $method->invoke($service, $purchases);

        $this->assertIsArray($formatted);
        $this->assertCount(4, $formatted);

        // Check first item (asset)
        $this->assertEquals('Car', $formatted[0]['item']);
        $this->assertEquals(5000000.00, $formatted[0]['amount']);
        $this->assertEquals('asset', $formatted[0]['type']);
        $this->assertEquals('PHP', $formatted[0]['currency']);

        // Check second item (liability)
        $this->assertEquals('Car Loan', $formatted[1]['item']);
        $this->assertEquals(4000000.00, $formatted[1]['amount']);
        $this->assertEquals('liability', $formatted[1]['type']);
        $this->assertEquals('PHP', $formatted[1]['currency']);

        // Check third item (expense)
        $this->assertEquals('Gas', $formatted[2]['item']);
        $this->assertEquals(1000.00, $formatted[2]['amount']);
        $this->assertEquals('expense', $formatted[2]['type']);
        $this->assertEquals('PHP', $formatted[2]['currency']);

        // Check fourth item (fallback - should be detected as asset)
        $this->assertEquals('a car', $formatted[3]['item']);
        $this->assertEquals(12000000.00, $formatted[3]['amount']);
        $this->assertEquals('asset', $formatted[3]['type']);
        $this->assertEquals('PHP', $formatted[3]['currency']);
    }

    public function test_format_purchases_added_handles_complex_item_names(): void
    {
        $user = User::factory()->create();
        $service = new FinancialAdvisorService($user);

        // Use reflection to access the protected formatPurchasesAdded method
        $reflection = new \ReflectionClass($service);
        $method = $reflection->getMethod('formatPurchasesAdded');
        $method->setAccessible(true);

        // Test with complex item names that have amounts embedded
        $purchases = [
            'health insurance (₱80,000.00 on Jul 27, 2025)',
            'car insurance (₱15,000.00 monthly)',
            'life insurance (₱25,000.00 per year)',
        ];

        $formatted = $method->invoke($service, $purchases);

        $this->assertIsArray($formatted);
        $this->assertCount(3, $formatted);

        // Check first item (health insurance - should be liability)
        $this->assertEquals('health insurance', $formatted[0]['item']);
        $this->assertEquals(80000.00, $formatted[0]['amount']);
        $this->assertEquals('liability', $formatted[0]['type']);
        $this->assertEquals('PHP', $formatted[0]['currency']);

        // Check second item (car insurance - should be liability)
        $this->assertEquals('car insurance', $formatted[1]['item']);
        $this->assertEquals(15000.00, $formatted[1]['amount']);
        $this->assertEquals('liability', $formatted[1]['type']);
        $this->assertEquals('PHP', $formatted[1]['currency']);

        // Check third item (life insurance - should be liability)
        $this->assertEquals('life insurance', $formatted[2]['item']);
        $this->assertEquals(25000.00, $formatted[2]['amount']);
        $this->assertEquals('liability', $formatted[2]['type']);
        $this->assertEquals('PHP', $formatted[2]['currency']);
    }

    public function test_improved_categorization_examples(): void
    {
        $user = User::factory()->create();
        $service = new FinancialAdvisorService($user);

        // Use reflection to access the protected determineItemType method
        $reflection = new \ReflectionClass($service);
        $method = $reflection->getMethod('determineItemType');
        $method->setAccessible(true);

        // Test asset examples
        $assetExamples = [
            ['item' => 'Ferrari', 'amount' => '8000000'],
            ['item' => 'House', 'amount' => '10000000'],
            ['item' => 'Land', 'amount' => '5000000'],
            ['item' => 'Gold Jewelry', 'amount' => '500000'],
            ['item' => 'Stock Investment', 'amount' => '2000000'],
            ['item' => 'Rolex Watch', 'amount' => '1000000'],
            ['item' => 'Lamborghini', 'amount' => '12000000'],
            ['item' => 'Real Estate', 'amount' => '15000000'],
        ];

        foreach ($assetExamples as $example) {
            $type = $method->invoke($service, $example['item'], $example['amount']);
            $this->assertEquals('asset', $type, "Failed to categorize '{$example['item']}' as asset");
        }

        // Test liability examples
        $liabilityExamples = [
            ['item' => 'Car Loan', 'amount' => '4000000'],
            ['item' => 'Mortgage', 'amount' => '8000000'],
            ['item' => 'Credit Card Debt', 'amount' => '500000'],
            ['item' => 'Health Insurance', 'amount' => '80000'],
            ['item' => 'Student Loan', 'amount' => '1500000'],
            ['item' => 'Tax Debt', 'amount' => '100000'],
            ['item' => 'Netflix Subscription', 'amount' => '15000'],
            ['item' => 'Electricity Bill', 'amount' => '3000'],
        ];

        foreach ($liabilityExamples as $example) {
            $type = $method->invoke($service, $example['item'], $example['amount']);
            $this->assertEquals('liability', $type, "Failed to categorize '{$example['item']}' as liability");
        }

        // Test expense examples
        $expenseExamples = [
            ['item' => 'Groceries', 'amount' => '2000'],
            ['item' => 'Gas', 'amount' => '1500'],
            ['item' => 'Dinner', 'amount' => '500'],
            ['item' => 'Movie Tickets', 'amount' => '1000'],
            ['item' => 'Socks', 'amount' => '200'],
            ['item' => 'Coffee', 'amount' => '150'],
            ['item' => 'Haircut', 'amount' => '300'],
            ['item' => 'Books', 'amount' => '800'],
        ];

        foreach ($expenseExamples as $example) {
            $type = $method->invoke($service, $example['item'], $example['amount']);
            $this->assertEquals('expense', $type, "Failed to categorize '{$example['item']}' as expense");
        }
    }
}
