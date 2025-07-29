<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\FinancialAdvisorService;
use App\Models\User;

class TestFinancialAdvisorPoints extends Command
{
    protected $signature = 'financial-advisor:test-points {--user= : User ID to test with}';

    protected $description = 'Test the financial advisor points and product functionality';

    public function handle()
    {
        $userId = $this->option('user');
        $user = $userId ? User::find($userId) : User::where('email', 'like', '%customer%')->first();

        if (!$user) {
            $this->error('No user found for testing');
            return 1;
        }

        $this->info("Testing with user: {$user->name} ({$user->email})");

        $advisorService = new FinancialAdvisorService($user);

        // Test 1: Check merchant products
        $this->info("\n=== Test 1: Merchant Products ===");
        $response1 = $advisorService->processMessage("What products does SM Supermarket have?");
        $this->line("Response: " . ($response1['message'] ?? 'No response'));

        // Test 2: Calculate purchase points
        $this->info("\n=== Test 2: Purchase Calculation ===");
        $response2 = $advisorService->processMessage("I want to buy 2 bags of rice and 3 bottles of milk from SM Supermarket. How much will it cost and how many points can I earn?");
        $this->line("Response: " . ($response2['message'] ?? 'No response'));

        // Test 3: Check points
        $this->info("\n=== Test 3: Check Points ===");
        $response3 = $advisorService->processMessage("How many points do I have at SM Supermarket?");
        $this->line("Response: " . ($response3['message'] ?? 'No response'));

        // Test 4: Purchase confirmation
        $this->info("\n=== Test 4: Purchase Confirmation ===");
        $response4 = $advisorService->processMessage("I bought groceries for 500 pesos at SM Supermarket today");
        $this->line("Response: " . ($response4['message'] ?? 'No response'));

        // Test 5: External merchant purchase
        $this->info("\n=== Test 5: External Merchant ===");
        $response5 = $advisorService->processMessage("I bought a coffee for 150 pesos at Starbucks yesterday");
        $this->line("Response: " . ($response5['message'] ?? 'No response'));

        $this->info("\n=== Test Complete ===");
        return 0;
    }
}
