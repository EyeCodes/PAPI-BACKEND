<?php

namespace App\Http\Controllers;

use App\Services\FinancialAdvisorService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;

class FinancialAdvisorController extends Controller
{
    protected FinancialAdvisorService $advisorService;

    public function __construct(Request $request)
    {
        $this->advisorService = new FinancialAdvisorService($request->user());
    }

    /**
     * Process natural language message - handles everything automatically
     */
    public function processMessage(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'message' => 'required|string|max:2000',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            // Process the message through the AI service
            $result = $this->advisorService->processMessage($request->message);

            return response()->json([
                'success' => true,
                'data' => $result
            ]);
        } catch (\Exception $e) {
            Log::error('Financial advisor message processing failed', [
                'user_id' => $request->user()->id,
                'message' => $request->message,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to process message',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get available financial categories (simple endpoint)
     */
    public function getCategories(Request $request): JsonResponse
    {
        try {
            $categories = \App\Models\FinancialCategory::active()
                ->ordered()
                ->withCount('purchases')
                ->get();

            return response()->json([
                'success' => true,
                'data' => $categories
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to get categories', [
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to get categories',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get current configuration and available providers
     */
    public function getConfiguration(Request $request): JsonResponse
    {
        try {
            $configService = new \App\Services\FinancialAdvisorConfigService();

            return response()->json([
                'success' => true,
                'data' => [
                    'status' => $configService->getProviderStatus(),
                    'available_providers' => $configService->getAvailableProviders(),
                    'config_summary' => $configService->getConfigSummary()
                ]
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to get configuration', [
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to get configuration',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Change provider and model
     */
    public function changeProvider(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'provider' => 'required|string',
            'model' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $configService = new \App\Services\FinancialAdvisorConfigService();
            $success = $configService->changeProvider(
                $request->provider,
                $request->model
            );

            if ($success) {
                return response()->json([
                    'success' => true,
                    'message' => 'Provider changed successfully',
                    'data' => $configService->getProviderStatus()
                ]);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to change provider'
                ], 500);
            }
        } catch (\Exception $e) {
            Log::error('Failed to change provider', [
                'provider' => $request->provider,
                'model' => $request->model,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to change provider',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Test current provider connection
     */
    public function testProvider(Request $request): JsonResponse
    {
        try {
            $configService = new \App\Services\FinancialAdvisorConfigService();
            $result = $configService->testProvider();

            return response()->json([
                'success' => true,
                'data' => $result
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to test provider', [
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to test provider',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
