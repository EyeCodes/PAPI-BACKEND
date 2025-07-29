<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class FinancialAdvisorConfigService
{
    /**
     * Get current provider configuration
     */
    public function getCurrentProvider(): array
    {
        $config = config('financial-advisor');
        $providerKey = $config['provider'];

        if (!isset($config['providers'][$providerKey])) {
            throw new \Exception("Provider '{$providerKey}' not found in configuration");
        }

        return $config['providers'][$providerKey];
    }

    /**
     * Get current model name
     */
    public function getCurrentModel(): string
    {
        return config('financial-advisor.model');
    }

    /**
     * Get available providers
     */
    public function getAvailableProviders(): array
    {
        $config = config('financial-advisor');
        $providers = [];

        foreach ($config['providers'] as $key => $provider) {
            $providers[$key] = [
                'name' => $provider['name'],
                'models' => $provider['models'],
                'default_model' => $provider['default_model'],
                'is_current' => $key === $config['provider']
            ];
        }

        return $providers;
    }

    /**
     * Change provider and model
     */
    public function changeProvider(string $provider, string $model = null): bool
    {
        try {
            $config = config('financial-advisor');

            if (!isset($config['providers'][$provider])) {
                throw new \Exception("Provider '{$provider}' not found");
            }

            $providerConfig = $config['providers'][$provider];

            // Validate model
            if ($model && !isset($providerConfig['models'][$model])) {
                throw new \Exception("Model '{$model}' not available for provider '{$provider}'");
            }

            // Use default model if not specified
            if (!$model) {
                $model = $providerConfig['default_model'];
            }

            // Update environment variables (you might want to persist this to database)
            putenv("FINANCIAL_ADVISOR_PROVIDER={$provider}");
            putenv("FINANCIAL_ADVISOR_MODEL={$model}");

            // Clear config cache
            Cache::forget('financial-advisor');

            Log::info('Financial advisor provider changed', [
                'provider' => $provider,
                'model' => $model
            ]);

            return true;
        } catch (\Exception $e) {
            Log::error('Failed to change financial advisor provider', [
                'provider' => $provider,
                'model' => $model,
                'error' => $e->getMessage()
            ]);

            return false;
        }
    }

    /**
     * Get provider status and health
     */
    public function getProviderStatus(): array
    {
        $config = config('financial-advisor');
        $currentProvider = $this->getCurrentProvider();

        return [
            'current_provider' => $config['provider'],
            'current_model' => $config['model'],
            'provider_name' => $currentProvider['name'],
            'available_models' => $currentProvider['models'],
            'configuration' => [
                'max_steps' => $config['max_steps'],
                'temperature' => $config['temperature'],
                'max_tokens' => $config['max_tokens'],
            ],
            'features' => [
                'memory_enabled' => true,
                'categorization_enabled' => true,
                'insights_enabled' => $config['response']['include_insights'],
                'recommendations_enabled' => $config['response']['include_recommendations'],
            ]
        ];
    }

    /**
     * Test provider connection
     */
    public function testProvider(): array
    {
        try {
            $config = config('financial-advisor');
            $providerConfig = $this->getCurrentProvider();

            // Simple test prompt
            $testResponse = \Prism\Prism\Prism::text()
                ->using($providerConfig['provider'], $config['model'])
                ->withMaxTokens(50)
                ->withPrompt('Say "Hello" if you can read this.')
                ->asText();

            return [
                'success' => true,
                'provider' => $config['provider'],
                'model' => $config['model'],
                'response' => $testResponse->text,
                'timestamp' => now()->toISOString()
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'provider' => $config['provider'] ?? 'unknown',
                'model' => $config['model'] ?? 'unknown',
                'error' => $e->getMessage(),
                'timestamp' => now()->toISOString()
            ];
        }
    }

    /**
     * Get configuration summary
     */
    public function getConfigSummary(): array
    {
        $config = config('financial-advisor');
        $providerConfig = $this->getCurrentProvider();

        return [
            'ai' => [
                'provider' => $config['provider'],
                'model' => $config['model'],
                'provider_name' => $providerConfig['name'],
                'temperature' => $config['temperature'],
                'max_tokens' => $config['max_tokens'],
                'max_steps' => $config['max_steps'],
            ],
            'memory' => [
                'max_memories' => $config['memory']['max_memories'],
                'retention_days' => $config['memory']['memory_retention_days'],
                'importance_threshold' => $config['memory']['importance_threshold'],
            ],
            'categorization' => [
                'confidence_threshold' => $config['categorization']['confidence_threshold'],
                'fallback_category' => $config['categorization']['fallback_category'],
            ],
            'response' => [
                'include_summary' => $config['response']['include_summary'],
                'include_insights' => $config['response']['include_insights'],
                'include_recommendations' => $config['response']['include_recommendations'],
                'max_recommendations' => $config['response']['max_recommendations'],
            ]
        ];
    }
}
