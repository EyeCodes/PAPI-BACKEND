<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\FinancialAdvisorConfigService;
use App\Models\User;

class TestFinancialAdvisorModels extends Command
{
    protected $signature = 'financial-advisor:test-models
                            {--provider= : Specific provider to test}
                            {--model= : Specific model to test}
                            {--message= : Test message to send}';

    protected $description = 'Test different AI models for the financial advisor';

    public function handle()
    {
        $configService = new FinancialAdvisorConfigService();
        $testMessage = $this->option('message') ?? 'Hello! I purchased a car, its a BMW i8, I bought it for 400k pesos today';

        $this->info('🧪 Testing Financial Advisor Models');
        $this->info('=====================================');
        $this->info("Test Message: {$testMessage}");
        $this->info('');

        // Get a test user
        $user = User::first();
        if (!$user) {
            $this->error('No users found in database. Please create a user first.');
            return 1;
        }

        $providers = $configService->getAvailableProviders();
        $specificProvider = $this->option('provider');
        $specificModel = $this->option('model');

        if ($specificProvider) {
            if (!isset($providers[$specificProvider])) {
                $this->error("Provider '{$specificProvider}' not found.");
                return 1;
            }
            $providers = [$specificProvider => $providers[$specificProvider]];
        }

        $results = [];

        foreach ($providers as $providerKey => $provider) {
            $this->info("🔍 Testing Provider: {$provider['name']} ({$providerKey})");

            $models = $specificModel ? [$specificModel] : array_keys($provider['models']);

            foreach ($models as $modelKey) {
                if (!isset($provider['models'][$modelKey])) {
                    $this->warn("Model '{$modelKey}' not available for provider '{$providerKey}'");
                    continue;
                }

                $this->info("  📝 Testing Model: {$modelKey}");

                try {
                    // Change to this provider/model
                    $success = $configService->changeProvider($providerKey, $modelKey);

                    if (!$success) {
                        $this->error("    ❌ Failed to change to provider {$providerKey} model {$modelKey}");
                        continue;
                    }

                    // Test the model
                    $testResult = $configService->testProvider();

                    if ($testResult['success']) {
                        $this->info("    ✅ Basic connection: OK");

                        // Test with financial advisor service
                        $advisorService = new \App\Services\FinancialAdvisorService($user);
                        $startTime = microtime(true);

                        try {
                            $response = $advisorService->processMessage($testMessage);
                            $endTime = microtime(true);
                            $duration = round(($endTime - $startTime) * 1000, 2);

                            if ($response['success']) {
                                $this->info("    ✅ Financial Advisor: OK ({$duration}ms)");
                                $this->info("    📊 Response: " . substr($response['message'], 0, 100) . "...");

                                $results[] = [
                                    'provider' => $providerKey,
                                    'model' => $modelKey,
                                    'status' => 'SUCCESS',
                                    'duration' => $duration,
                                    'message' => $response['message']
                                ];
                            } else {
                                $this->error("    ❌ Financial Advisor: Failed - {$response['message']}");
                                $results[] = [
                                    'provider' => $providerKey,
                                    'model' => $modelKey,
                                    'status' => 'FAILED',
                                    'error' => $response['message']
                                ];
                            }
                        } catch (\Exception $e) {
                            $this->error("    ❌ Financial Advisor: Exception - {$e->getMessage()}");
                            $results[] = [
                                'provider' => $providerKey,
                                'model' => $modelKey,
                                'status' => 'EXCEPTION',
                                'error' => $e->getMessage()
                            ];
                        }
                    } else {
                        $this->error("    ❌ Basic connection: Failed - {$testResult['error']}");
                        $results[] = [
                            'provider' => $providerKey,
                            'model' => $modelKey,
                            'status' => 'CONNECTION_FAILED',
                            'error' => $testResult['error']
                        ];
                    }
                } catch (\Exception $e) {
                    $this->error("    ❌ Test failed: {$e->getMessage()}");
                    $results[] = [
                        'provider' => $providerKey,
                        'model' => $modelKey,
                        'status' => 'TEST_FAILED',
                        'error' => $e->getMessage()
                    ];
                }

                $this->info('');
            }
        }

        // Summary
        $this->info('📋 Test Summary');
        $this->info('===============');

        $successCount = 0;
        $failedCount = 0;

        foreach ($results as $result) {
            if ($result['status'] === 'SUCCESS') {
                $successCount++;
                $this->info("✅ {$result['provider']}/{$result['model']} - {$result['duration']}ms");
            } else {
                $failedCount++;
                $this->error("❌ {$result['provider']}/{$result['model']} - {$result['error']}");
            }
        }

        $this->info('');
        $this->info("🎯 Results: {$successCount} successful, {$failedCount} failed");

        if ($successCount > 0) {
            $this->info('💡 Recommendation: Use one of the successful models for production.');
        } else {
            $this->warn('⚠️  No models worked. Check your API keys and configuration.');
        }

        return 0;
    }
}
