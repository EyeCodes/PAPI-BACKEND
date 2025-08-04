<?php

namespace App\Services;

use App\Models\Purchase;
use App\Models\User;
use App\Models\UserMemory;
use App\Models\FinancialCategory;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Prism\Prism\Prism;
use Prism\Prism\Enums\Provider;
use Prism\Prism\Facades\Tool;
use Prism\Prism\Schema\ObjectSchema;
use Prism\Prism\Schema\StringSchema;
use Prism\Prism\Schema\ArraySchema;
use Prism\Prism\Schema\NumberSchema;
use Carbon\Carbon;

class FinancialAdvisorService
{
    protected User $user;

    public function __construct(User $user)
    {
        $this->user = $user;
    }

    /**
     * Process natural language message and handle everything automatically
     */
    public function processMessage(string $message): array
    {
        try {
            // Get configuration
            $config = config('financial-advisor');
            $providerConfig = $config['providers'][$config['provider']];

            // Create tools for the AI to use
            $tools = $this->createTools();

            // Build context
            $context = $this->buildContext($message);

            $savedPurchases = [];
            $toolResults = '';

            // First, let the AI use tools to perform actions (if enabled)
            if ($config['enable_function_calling']) {
                try {
                    $toolResponse = Prism::text()
                        ->using($providerConfig['provider'], $config['model'])
                        ->withMaxSteps($config['max_steps'])
                        ->usingTemperature($config['temperature'])
                        ->withMaxTokens($config['max_tokens'])
                        ->withSystemPrompt($this->getToolSystemPrompt())
                        ->withPrompt($context)
                        ->withTools($tools)
                        ->asText();

                    $toolResults = $toolResponse->text;
                } catch (\Exception $toolError) {
                    Log::warning('Tool execution failed, falling back to direct processing', [
                        'provider' => $config['provider'],
                        'model' => $config['model'],
                        'error' => $toolError->getMessage()
                    ]);

                    // Fallback: Process without tools and save purchases
                    $savedPurchases = $this->extractAndSavePurchases($message);
                    $toolResults = $this->fallbackProcessing($message);
                }
            } else {
                // Function calling disabled, extract and save purchases directly
                $savedPurchases = $this->extractAndSavePurchases($message);
                $toolResults = $this->fallbackProcessing($message);
            }

            // Then, get a structured response with insights and advice
            try {
                // Check if tool results already contain merchant information
                if (
                    strpos($toolResults, 'Available Merchants') !== false ||
                    strpos($toolResults, '🏪') !== false ||
                    strpos($toolResults, '• SM Supermarket') !== false
                ) {
                    // Use the tool results directly for merchant listings
                    $insights = [
                        'message' => $toolResults,
                        'advice' => '',
                        'insights' => [],
                        'recommendations' => [],
                        'purchases_added' => $savedPurchases
                    ];
                } elseif (
                    strpos($toolResults, 'Points Status') !== false ||
                    strpos($toolResults, 'Current Points') !== false ||
                    strpos($toolResults, '❌ Jollibee') !== false ||
                    strpos($toolResults, '✅ Jollibee') !== false
                ) {
                    // Use the tool results directly for points information
                    $insights = [
                        'message' => $toolResults,
                        'advice' => '',
                        'insights' => [],
                        'recommendations' => [],
                        'purchases_added' => $savedPurchases
                    ];
                } else {
                    // Generate structured insights
                    $insights = $this->generateStructuredInsights($message, $toolResults, $providerConfig, $config);
                }
            } catch (\Exception $insightsError) {
                Log::warning('Insights generation failed, using fallback', [
                    'error' => $insightsError->getMessage()
                ]);

                $insights = $this->generateFallbackResponse($message);
            }

            // Store conversation memory
            $this->storeConversationMemory($message, $insights);

            return $insights;
        } catch (\Exception $e) {
            Log::error('Financial advisor processing failed', [
                'user_id' => $this->user->id,
                'message' => $message,
                'error' => $e->getMessage()
            ]);

            return [
                'message' => 'I apologize, but I encountered an error processing your request. Please try again.',
                'advice' => '',
                'insights' => [],
                'recommendations' => [],
                'purchases_added' => []
            ];
        }
    }

    /**
     * Create AI tools for function calling
     */
    protected function createTools(): array
    {
        return [
            Tool::function('save_purchase', 'Save a purchase to the user\'s transaction history')
                ->parameter('merchant', StringSchema::make()->description('Name of the merchant/store'))
                ->parameter('amount', NumberSchema::make()->description('Purchase amount'))
                ->parameter('items', ArraySchema::make()->description('List of items purchased'))
                ->parameter('date', StringSchema::make()->description('Purchase date (YYYY-MM-DD)'))
                ->handler(function ($merchant, $amount, $items, $date) {
                    return $this->savePurchase($merchant, $amount, $items, $date);
                }),

            Tool::function('get_user_points', 'Get user\'s current points status')
                ->parameter('merchant', StringSchema::make()->description('Merchant name (optional)'))
                ->handler(function ($merchant = null) {
                    return $this->getUserPoints($merchant);
                }),

            Tool::function('get_available_merchants', 'Get list of available merchants and their point systems')
                ->handler(function () {
                    return $this->getAvailableMerchants();
                }),

            Tool::function('get_purchase_history', 'Get user\'s recent purchase history')
                ->parameter('days', NumberSchema::make()->description('Number of days to look back'))
                ->handler(function ($days = 30) {
                    return $this->getPurchaseHistory($days);
                }),
        ];
    }

    /**
     * Save a purchase to the database
     */
    protected function savePurchase(string $merchant, float $amount, array $items, string $date): array
    {
        try {
            $purchase = Purchase::create([
                'user_id' => $this->user->id,
                'merchant' => $merchant,
                'amount' => $amount,
                'purchase_date' => $date,
                'category' => $this->autoCategorizePurchase($merchant, $amount, $items),
            ]);

            // Store important memory about this purchase
            $this->storeImportantMemory("Purchase at {$merchant} for {$amount}");

            return [
                'success' => true,
                'message' => "Purchase saved: {$merchant} - {$amount}",
                'purchase_id' => $purchase->id
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Failed to save purchase: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Get user's points status
     */
    protected function getUserPoints(?string $merchant = null): array
    {
        // This would integrate with your existing points system
        // For now, return a placeholder
        return [
            'total_points' => 0,
            'merchant_points' => [],
            'message' => 'Points system integration pending'
        ];
    }

    /**
     * Get available merchants
     */
    protected function getAvailableMerchants(): array
    {
        return [
            'merchants' => [
                'SM Supermarket' => ['points_per_peso' => 1],
                'Jollibee' => ['points_per_peso' => 2],
                'McDonald\'s' => ['points_per_peso' => 1.5],
            ],
            'message' => 'Available merchants and their point systems'
        ];
    }

    /**
     * Get purchase history
     */
    protected function getPurchaseHistory(int $days): array
    {
        $purchases = Purchase::where('user_id', $this->user->id)
            ->where('purchase_date', '>=', now()->subDays($days))
            ->orderBy('purchase_date', 'desc')
            ->get();

        return [
            'purchases' => $purchases->toArray(),
            'total_amount' => $purchases->sum('amount'),
            'count' => $purchases->count()
        ];
    }

    /**
     * Extract and save purchases from message
     */
    protected function extractAndSavePurchases(string $message): array
    {
        $purchases = $this->extractPurchasesFromMessage($message);
        $savedPurchases = [];

        foreach ($purchases as $purchase) {
            try {
                $savedPurchase = Purchase::create([
                    'user_id' => $this->user->id,
                    'merchant' => $purchase['merchant'],
                    'amount' => $purchase['amount'],
                    'purchase_date' => $purchase['date'] ?? now()->toDateString(),
                    'category' => $purchase['category'] ?? 'Uncategorized',
                ]);

                $savedPurchases[] = $savedPurchase;
            } catch (\Exception $e) {
                Log::error('Failed to save purchase', [
                    'purchase' => $purchase,
                    'error' => $e->getMessage()
                ]);
            }
        }

        return $savedPurchases;
    }

    /**
     * Build context for AI processing
     */
    protected function buildContext(string $message): string
    {
        $userContext = $this->buildUserContext();
        $recentPurchases = $this->getRecentPurchaseSummary();
        $relevantMemories = $this->getRelevantMemories($message);

        return "User Context:\n" . json_encode($userContext, JSON_PRETTY_PRINT) . "\n\n" .
            "Recent Purchases:\n" . json_encode($recentPurchases, JSON_PRETTY_PRINT) . "\n\n" .
            "Relevant Memories:\n" . json_encode($relevantMemories, JSON_PRETTY_PRINT) . "\n\n" .
            "User Message: {$message}";
    }

    /**
     * Build user context
     */
    protected function buildUserContext(): array
    {
        return [
            'user_id' => $this->user->id,
            'name' => $this->user->name,
            'email' => $this->user->email,
            'favorite_category' => $this->getFavoriteCategory(),
            'total_purchases' => Purchase::where('user_id', $this->user->id)->count(),
            'total_spent' => Purchase::where('user_id', $this->user->id)->sum('amount'),
        ];
    }

    /**
     * Get recent purchase summary
     */
    protected function getRecentPurchaseSummary(): array
    {
        $recentPurchases = Purchase::where('user_id', $this->user->id)
            ->where('purchase_date', '>=', now()->subDays(30))
            ->orderBy('purchase_date', 'desc')
            ->limit(10)
            ->get();

        return [
            'count' => $recentPurchases->count(),
            'total_amount' => $recentPurchases->sum('amount'),
            'purchases' => $recentPurchases->map(function ($purchase) {
                return [
                    'merchant' => $purchase->merchant,
                    'amount' => $purchase->amount,
                    'date' => $purchase->purchase_date,
                    'category' => $purchase->category,
                ];
            })->toArray()
        ];
    }

    /**
     * Get relevant memories for the query
     */
    protected function getRelevantMemories(string $query): array
    {
        $memories = UserMemory::where('user_id', $this->user->id)
            ->where('importance', '>=', config('financial-advisor.memory.importance_threshold'))
            ->where('last_accessed_at', '>=', now()->subDays(config('financial-advisor.memory.memory_retention_days')))
            ->orderBy('importance', 'desc')
            ->orderBy('last_accessed_at', 'desc')
            ->limit(5)
            ->get();

        return $memories->map(function ($memory) {
            return [
                'type' => $memory->type,
                'content' => $memory->content,
                'importance' => $memory->importance,
                'last_accessed' => $memory->last_accessed_at,
            ];
        })->toArray();
    }

    /**
     * Get favorite category
     */
    protected function getFavoriteCategory(): ?string
    {
        $favoriteCategory = Purchase::where('user_id', $this->user->id)
            ->selectRaw('category, COUNT(*) as count')
            ->groupBy('category')
            ->orderBy('count', 'desc')
            ->first();

        return $favoriteCategory ? $favoriteCategory->category : null;
    }

    /**
     * Auto-categorize purchase
     */
    protected function autoCategorizePurchase(string $merchant, float $amount, array $items = []): string
    {
        $text = $merchant . ' ' . implode(' ', $items);
        return $this->aiCategorize($text, $amount);
    }

    /**
     * AI categorization
     */
    protected function aiCategorize(string $text, ?float $amount): string
    {
        try {
            $config = config('financial-advisor');
            $providerConfig = $config['providers'][$config['provider']];

            $response = Prism::text()
                ->using($providerConfig['provider'], $config['model'])
                ->withMaxTokens(50)
                ->withTemperature(0.3)
                ->withPrompt("Categorize this purchase into one of these categories: Food & Dining, Shopping, Transportation, Entertainment, Healthcare, Utilities, Education, Travel, Other. Purchase: {$text} Amount: {$amount}. Return only the category name.")
                ->asText();

            $category = trim($response->text);

            // Validate category
            $validCategories = ['Food & Dining', 'Shopping', 'Transportation', 'Entertainment', 'Healthcare', 'Utilities', 'Education', 'Travel', 'Other'];

            if (in_array($category, $validCategories)) {
                return $category;
            }

            return config('financial-advisor.categorization.fallback_category');
        } catch (\Exception $e) {
            Log::warning('AI categorization failed', [
                'text' => $text,
                'error' => $e->getMessage()
            ]);

            return config('financial-advisor.categorization.fallback_category');
        }
    }

    /**
     * Fallback processing when function calling fails
     */
    protected function fallbackProcessing(string $message): string
    {
        // Simple keyword-based processing
        if (stripos($message, 'merchant') !== false || stripos($message, 'store') !== false) {
            return $this->handleMerchantListing();
        }

        if (stripos($message, 'point') !== false) {
            return $this->handleGeneralPointsInquiry();
        }

        return "I understand you said: {$message}. I can help you track purchases, check points, and provide financial insights.";
    }

    /**
     * Extract purchases from message using AI
     */
    protected function extractPurchasesFromMessage(string $message): array
    {
        try {
            $config = config('financial-advisor');
            $providerConfig = $config['providers'][$config['provider']];

            $response = Prism::text()
                ->using($providerConfig['provider'], $config['model'])
                ->withMaxTokens(200)
                ->withTemperature(0.3)
                ->withPrompt("Extract purchase information from this message. Return as JSON array with objects containing: merchant, amount, date (YYYY-MM-DD), category. Message: {$message}")
                ->asText();

            $purchases = json_decode($response->text, true);

            if (is_array($purchases)) {
                return $purchases;
            }

            return $this->simpleAIExtraction($message);
        } catch (\Exception $e) {
            Log::warning('AI purchase extraction failed', [
                'message' => $message,
                'error' => $e->getMessage()
            ]);

            return $this->basicExtractPurchases($message);
        }
    }

    /**
     * Simple AI extraction fallback
     */
    protected function simpleAIExtraction(string $message): array
    {
        // Basic regex-based extraction
        $purchases = [];

        // Look for amount patterns
        if (preg_match_all('/(\d+(?:\.\d{2})?)/', $message, $matches)) {
            foreach ($matches[1] as $amount) {
                $purchases[] = [
                    'merchant' => 'Unknown Store',
                    'amount' => (float) $amount,
                    'date' => now()->toDateString(),
                    'category' => 'Uncategorized'
                ];
            }
        }

        return $purchases;
    }

    /**
     * Basic purchase extraction
     */
    protected function basicExtractPurchases(string $message): array
    {
        return [];
    }

    /**
     * Store important memory
     */
    protected function storeImportantMemory(string $message): void
    {
        try {
            UserMemory::create([
                'user_id' => $this->user->id,
                'type' => 'insight',
                'content' => $message,
                'importance' => 7,
                'last_accessed_at' => now(),
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to store memory', [
                'message' => $message,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Store conversation memory
     */
    protected function storeConversationMemory(string $userMessage, array $aiResponse): void
    {
        try {
            $summary = $this->extractConversationSummary($userMessage, $aiResponse);
            $importance = $this->calculateConversationImportance($userMessage, $aiResponse);

            if ($importance >= config('financial-advisor.memory.importance_threshold')) {
                UserMemory::create([
                    'user_id' => $this->user->id,
                    'type' => 'conversation',
                    'content' => $summary,
                    'importance' => $importance,
                    'last_accessed_at' => now(),
                    'metadata' => [
                        'user_message' => $userMessage,
                        'ai_response' => $aiResponse,
                        'timestamp' => now()->toISOString(),
                    ]
                ]);
            }
        } catch (\Exception $e) {
            Log::error('Failed to store conversation memory', [
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Extract conversation summary
     */
    protected function extractConversationSummary(string $userMessage, array $aiResponse): string
    {
        $summary = "User asked about: " . substr($userMessage, 0, 100);

        if (isset($aiResponse['purchases_added']) && !empty($aiResponse['purchases_added'])) {
            $summary .= ". Purchases were recorded.";
        }

        if (isset($aiResponse['insights']) && !empty($aiResponse['insights'])) {
            $summary .= ". Financial insights provided.";
        }

        return $summary;
    }

    /**
     * Calculate conversation importance
     */
    protected function calculateConversationImportance(string $userMessage, array $aiResponse): int
    {
        $importance = 1;

        // Check for purchase-related keywords
        $purchaseKeywords = ['bought', 'purchase', 'spent', 'paid', 'cost', 'price', 'amount'];
        foreach ($purchaseKeywords as $keyword) {
            if (stripos($userMessage, $keyword) !== false) {
                $importance += 2;
                break;
            }
        }

        // Check if purchases were added
        if (isset($aiResponse['purchases_added']) && !empty($aiResponse['purchases_added'])) {
            $importance += 3;
        }

        // Check for financial advice requests
        $adviceKeywords = ['advice', 'recommend', 'suggest', 'help', 'what should'];
        foreach ($adviceKeywords as $keyword) {
            if (stripos($userMessage, $keyword) !== false) {
                $importance += 2;
                break;
            }
        }

        return min($importance, 10);
    }

    /**
     * Generate structured insights
     */
    protected function generateStructuredInsights(string $message, string $toolResults, array $providerConfig, array $config): array
    {
        try {
            $insightsContext = $this->buildInsightsContext($message, $toolResults);

            $response = Prism::text()
                ->using($providerConfig['provider'], $config['model'])
                ->withMaxTokens($config['max_tokens'])
                ->usingTemperature($config['temperature'])
                ->withSystemPrompt($this->getInsightsSystemPrompt())
                ->withPrompt($insightsContext)
                ->asText();

            $insights = json_decode($response->text, true);

            if (is_array($insights)) {
                return $insights;
            }

            return $this->generateFallbackResponse($message);
        } catch (\Exception $e) {
            Log::warning('Structured insights generation failed', [
                'error' => $e->getMessage()
            ]);

            return $this->generateFallbackResponse($message);
        }
    }

    /**
     * Build insights context
     */
    protected function buildInsightsContext(string $message, string $toolResults): string
    {
        $userContext = $this->buildUserContext();
        $recentPurchases = $this->getRecentPurchaseSummary();

        return "User Context:\n" . json_encode($userContext, JSON_PRETTY_PRINT) . "\n\n" .
            "Recent Purchases:\n" . json_encode($recentPurchases, JSON_PRETTY_PRINT) . "\n\n" .
            "Tool Results:\n{$toolResults}\n\n" .
            "User Message: {$message}\n\n" .
            "Generate a JSON response with: message, advice, insights (array), recommendations (array), purchases_added (array)";
    }

    /**
     * Generate fallback response
     */
    protected function generateFallbackResponse(string $message): array
    {
        $purchases = $this->extractPurchasesFromMessage($message);

        return [
            'message' => $this->generateBasicMessage($message, $purchases),
            'advice' => $this->generateBasicAdvice($purchases),
            'insights' => $this->generateBasicInsights($purchases),
            'recommendations' => $this->generateBasicRecommendations($purchases),
            'purchases_added' => $purchases
        ];
    }

    /**
     * Generate basic message
     */
    protected function generateBasicMessage(string $message, array $purchases): string
    {
        if (!empty($purchases)) {
            $total = array_sum(array_column($purchases, 'amount'));
            return "I've recorded your purchase(s) totaling {$total}. Here's what I found:";
        }

        return "I understand your message: {$message}. How can I help you with your finances?";
    }

    /**
     * Generate basic advice
     */
    protected function generateBasicAdvice(array $purchases): string
    {
        if (empty($purchases)) {
            return "Consider tracking your daily expenses to better understand your spending patterns.";
        }

        $total = array_sum(array_column($purchases, 'amount'));

        if ($total > 1000) {
            return "This is a significant purchase. Consider if this aligns with your financial goals.";
        }

        return "Good job tracking your expenses! Keep monitoring your spending patterns.";
    }

    /**
     * Generate basic insights
     */
    protected function generateBasicInsights(array $purchases): array
    {
        if (empty($purchases)) {
            return [];
        }

        $total = array_sum(array_column($purchases, 'amount'));
        $categories = array_count_values(array_column($purchases, 'category'));

        return [
            "Total spending: {$total}",
            "Number of purchases: " . count($purchases),
            "Most common category: " . array_keys($categories, max($categories))[0] ?? 'Unknown'
        ];
    }

    /**
     * Generate basic recommendations
     */
    protected function generateBasicRecommendations(array $purchases): array
    {
        $recommendations = [];

        if (!empty($purchases)) {
            $total = array_sum(array_column($purchases, 'amount'));

            if ($total > 1000) {
                $recommendations[] = "Consider setting a budget for large purchases";
            }

            $recommendations[] = "Review your spending patterns monthly";
            $recommendations[] = "Set up automatic expense tracking";
        }

        return $recommendations;
    }

    /**
     * Handle merchant listing
     */
    protected function handleMerchantListing(): string
    {
        $merchants = $this->getAvailableMerchants();

        $response = "🏪 Available Merchants:\n\n";
        foreach ($merchants['merchants'] as $merchant => $info) {
            $response .= "• {$merchant} - {$info['points_per_peso']} points per peso\n";
        }

        return $response;
    }

    /**
     * Handle general points inquiry
     */
    protected function handleGeneralPointsInquiry(): string
    {
        return "Points Status:\n\n" .
            "Current Points: 0\n" .
            "Available at: SM Supermarket, Jollibee, McDonald's\n" .
            "Earn points on every purchase!";
    }

    /**
     * Get tool system prompt
     */
    protected function getToolSystemPrompt(): string
    {
        return "You are a financial advisor AI assistant. Your role is to help users track purchases, manage points, and provide financial insights.

Key Responsibilities:
1. Extract purchase information from user messages
2. Save purchases to the user's transaction history
3. Provide information about available merchants and point systems
4. Show user's current points status
5. Display purchase history when requested

Available Tools:
- save_purchase: Save a purchase with merchant, amount, items, and date
- get_user_points: Get user's current points status (optional merchant parameter)
- get_available_merchants: List all available merchants and their point systems
- get_purchase_history: Get user's recent purchase history

Guidelines:
- Always be helpful and friendly
- Extract purchase details accurately
- Provide clear, actionable information
- Use the tools when appropriate to perform actions
- If you can't extract purchase details, ask for clarification";
    }

    /**
     * Get insights system prompt
     */
    protected function getInsightsSystemPrompt(): string
    {
        return "You are a financial advisor AI that provides insights and recommendations based on user data.

Your task is to analyze the user's context, recent purchases, and current message to provide:

1. A clear, helpful message responding to the user's query
2. Personalized financial advice based on their spending patterns
3. Key insights about their financial behavior
4. Actionable recommendations for improvement

Response Format (JSON):
{
  \"message\": \"Your main response to the user\",
  \"advice\": \"Personalized financial advice\",
  \"insights\": [\"insight1\", \"insight2\", \"insight3\"],
  \"recommendations\": [\"recommendation1\", \"recommendation2\"],
  \"purchases_added\": []
}

Guidelines:
- Be encouraging and supportive
- Provide specific, actionable advice
- Focus on positive financial habits
- Keep insights relevant and helpful
- Use the user's spending data to personalize responses";
    }
}
