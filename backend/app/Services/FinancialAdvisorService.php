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
                    // Use the tool results directly for merchant points queries
                    $insights = [
                        'message' => $toolResults,
                        'advice' => '',
                        'insights' => [],
                        'recommendations' => [],
                        'purchases_added' => $savedPurchases
                    ];
                } else {
                    // Generate structured AI insights for other queries
                    $insights = $this->generateStructuredInsights($message, $toolResults, $providerConfig, $config);
                }
            } catch (\Exception $insightsError) {
                Log::warning('Insights generation failed, using fallback response', [
                    'provider' => $config['provider'],
                    'model' => $config['model'],
                    'error' => $insightsError->getMessage()
                ]);

                // Fallback: Generate basic response with saved purchases
                $insights = $this->generateFallbackResponse($message);
                if (!empty($savedPurchases)) {
                    $insights['purchases_added'] = $savedPurchases;
                }
            }

            $response = [
                'success' => true,
                'message' => $insights['message'] ?? 'Message processed successfully',
                'purchases_added' => $this->formatPurchasesAdded($insights['purchases_added'] ?? []),
                'advice' => $insights['advice'] ?? '',
                'insights' => $insights['insights'] ?? [],
                'recommendations' => $insights['recommendations'] ?? [],
            ];

            // Add summary if enabled - calculate after purchases are saved
            if ($config['response']['include_summary']) {
                $response['summary'] = $this->getCurrentSummary();
            }

            // Automatically store important memories after successful AI response
            $this->storeConversationMemory($message, $response);

            return $response;
        } catch (\Exception $e) {
            Log::error('Financial advisor message processing failed', [
                'user_id' => $this->user->id,
                'message' => $message,
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'error' => 'Unable to process message',
                'message' => $e->getMessage()
            ];
        }
    }

    /**
     * Create tools for the AI to use
     */
    protected function createTools(): array
    {
        return [
            // Tool to add a purchase
            Tool::as('add_purchase')
                ->for('Add a new purchase to the user\'s financial records')
                ->withObjectParameter(
                    'purchase',
                    'The purchase details',
                    [
                        new \Prism\Prism\Schema\StringSchema('title', 'Purchase title/description'),
                        new \Prism\Prism\Schema\NumberSchema('amount', 'Purchase amount'),
                        new \Prism\Prism\Schema\StringSchema('merchant_name', 'Merchant name (optional)'),
                        new \Prism\Prism\Schema\StringSchema('category', 'Purchase category (optional)'),
                        new \Prism\Prism\Schema\StringSchema('date', 'Purchase date (YYYY-MM-DD)'),
                        new \Prism\Prism\Schema\StringSchema('description', 'Additional description (optional)')
                    ],
                    requiredFields: ['title', 'amount', 'date']
                )
                ->using(function (array $purchase): string {
                    try {
                        $newPurchase = Purchase::create([
                            'user_id' => $this->user->id,
                            'title' => $purchase['title'],
                            'description' => $purchase['description'] ?? null,
                            'amount' => $purchase['amount'],
                            'currency' => 'PHP',
                            'merchant_name' => $purchase['merchant_name'] ?? null,
                            'purchase_date' => $purchase['date'],
                            'metadata' => []
                        ]);

                        // Auto-categorize the purchase
                        $category = $this->autoCategorizePurchase($newPurchase);
                        $newPurchase->update(['ai_categorized_category' => $category]);

                        return "Purchase added successfully: {$purchase['title']} for ₱{$purchase['amount']} - Categorized as: {$category}";
                    } catch (\Exception $e) {
                        return "Failed to add purchase: {$e->getMessage()}";
                    }
                }),

            // Tool to get merchant products
            Tool::as('get_merchant_products')
                ->for('Get products available at a specific merchant with prices and points earning potential')
                ->withStringParameter('merchant_name', 'Name of the merchant to check products for')
                ->using(function (string $merchantName): string {
                    try {
                        $merchant = \App\Models\Merchant::where('name', 'like', "%{$merchantName}%")->first();

                        if (!$merchant) {
                            return "❌ Merchant '{$merchantName}' not found in the system.";
                        }

                        $products = $merchant->products()->with('pointsRules')->get();

                        if ($products->isEmpty()) {
                            return "📦 No products found for {$merchant->name}.";
                        }

                        $productList = $products->map(function ($product) {
                            $price = number_format($product->price, 2);
                            $pointsRules = $product->pointsRules;
                            $pointsInfo = $pointsRules->isNotEmpty()
                                ? " 🎯 " . $this->getProductPointsInfo($product)
                                : " ❌ No points";

                            return "• {$product->name} - ₱{$price}{$pointsInfo}";
                        })->join("\n");

                        $totalProducts = $products->count();
                        $totalValue = $products->sum('price');
                        $productsWithPoints = $products->filter(fn($p) => $p->pointsRules->isNotEmpty())->count();

                        return "🛍️ Products at {$merchant->name}:\n\n{$productList}\n\n📊 Summary: {$totalProducts} products\n💰 Total Value: ₱" . number_format($totalValue, 2) . "\n🎯 Products with Points: {$productsWithPoints}";
                    } catch (\Exception $e) {
                        return "❌ Failed to get merchant products: {$e->getMessage()}";
                    }
                }),

            // Tool to calculate purchase points
            Tool::as('calculate_purchase_points')
                ->for('Calculate total cost and points that can be earned for a specific purchase')
                ->withObjectParameter(
                    'purchase_calculation',
                    'Purchase calculation details',
                    [
                        new \Prism\Prism\Schema\StringSchema('merchant_name', 'Name of the merchant'),
                        new \Prism\Prism\Schema\ArraySchema('items', 'List of items to purchase', new \Prism\Prism\Schema\ObjectSchema('item', 'Item details', [
                            new \Prism\Prism\Schema\StringSchema('product_name', 'Name of the product'),
                            new \Prism\Prism\Schema\NumberSchema('quantity', 'Quantity to purchase'),
                        ]))
                    ],
                    requiredFields: ['merchant_name', 'items']
                )
                ->using(function (array $calculation): string {
                    try {
                        $merchant = \App\Models\Merchant::where('name', 'like', "%{$calculation['merchant_name']}%")->first();

                        if (!$merchant) {
                            return "Merchant '{$calculation['merchant_name']}' not found in the system.";
                        }

                        $totalCost = 0;
                        $totalPoints = 0;
                        $itemDetails = [];

                        foreach ($calculation['items'] as $item) {
                            $product = $merchant->products()
                                ->where('name', 'like', "%{$item['product_name']}%")
                                ->with('pointsRules')
                                ->first();

                            if ($product) {
                                $itemCost = $product->price * $item['quantity'];
                                $totalCost += $itemCost;

                                // Calculate points for this item
                                $itemPoints = $this->calculateProductPoints($product, $itemCost, $item['quantity']);
                                $totalPoints += $itemPoints;

                                $itemDetails[] = "• {$product->name} x{$item['quantity']} = ₱" . number_format($itemCost, 2) . " (Earns {$itemPoints} points)";
                            } else {
                                $itemDetails[] = "• {$item['product_name']} x{$item['quantity']} = Product not found";
                            }
                        }

                        $summary = "Purchase Summary for {$merchant->name}:\n" . implode("\n", $itemDetails) . "\n\nTotal Cost: ₱" . number_format($totalCost, 2) . "\nTotal Points to Earn: {$totalPoints}";

                        return $summary;
                    } catch (\Exception $e) {
                        return "Failed to calculate purchase points: {$e->getMessage()}";
                    }
                }),

            // Tool to confirm purchase details
            Tool::as('confirm_purchase_details')
                ->for('Confirm purchase details before recording, including merchant identification')
                ->withObjectParameter(
                    'purchase_confirmation',
                    'Purchase confirmation details',
                    [
                        new \Prism\Prism\Schema\StringSchema('title', 'Purchase title/description'),
                        new \Prism\Prism\Schema\NumberSchema('amount', 'Purchase amount'),
                        new \Prism\Prism\Schema\StringSchema('merchant_name', 'Merchant name (if known)'),
                        new \Prism\Prism\Schema\StringSchema('date', 'Purchase date (YYYY-MM-DD)'),
                        new \Prism\Prism\Schema\StringSchema('description', 'Additional details (optional)'),
                        new \Prism\Prism\Schema\BooleanSchema('is_system_merchant', 'Whether this is a merchant in our system')
                    ],
                    requiredFields: ['title', 'amount', 'date']
                )
                ->using(function (array $confirmation): string {
                    try {
                        $merchantName = $confirmation['merchant_name'] ?? 'Unknown Merchant';
                        $isSystemMerchant = $confirmation['is_system_merchant'] ?? false;

                        $confirmationMessage = "Purchase Confirmation:\n";
                        $confirmationMessage .= "• Item: {$confirmation['title']}\n";
                        $confirmationMessage .= "• Amount: ₱{$confirmation['amount']}\n";
                        $confirmationMessage .= "• Merchant: {$merchantName}" . ($isSystemMerchant ? " (System Merchant)" : " (External Merchant)") . "\n";
                        $confirmationMessage .= "• Date: {$confirmation['date']}\n";

                        if (!empty($confirmation['description'])) {
                            $confirmationMessage .= "• Details: {$confirmation['description']}\n";
                        }

                        // If it's a system merchant, check points earning potential
                        if ($isSystemMerchant) {
                            $merchant = \App\Models\Merchant::where('name', 'like', "%{$merchantName}%")->first();
                            if ($merchant) {
                                $pointsService = new \App\Services\PointsService();
                                $canEarnPoints = $pointsService->canEarnPointsAtMerchant($this->user->id, $merchant->id);

                                if ($canEarnPoints) {
                                    $confirmationMessage .= "• Points: You can earn points at this merchant!\n";
                                }
                            }
                        }

                        $confirmationMessage .= "\nPurchase details confirmed and ready to record.";

                        return $confirmationMessage;
                    } catch (\Exception $e) {
                        return "Failed to confirm purchase details: {$e->getMessage()}";
                    }
                }),

            // Tool to get spending summary
            Tool::as('get_spending_summary')
                ->for('Get a summary of user\'s spending for a specific period')
                ->withStringParameter('period', 'Time period: week, month, quarter, year, or custom dates (YYYY-MM-DD to YYYY-MM-DD)')
                ->using(function (string $period): string {
                    try {
                        $dates = $this->parseDateRange($period);
                        $purchases = $this->user->purchases()
                            ->whereBetween('purchase_date', $dates)
                            ->get();

                        $total = $purchases->sum('amount');
                        $count = $purchases->count();
                        $avg = $count > 0 ? $total / $count : 0;

                        $topCategory = $purchases->groupBy('ai_categorized_category')
                            ->map->sum('amount')
                            ->sortDesc()
                            ->keys()
                            ->first() ?? 'Uncategorized';

                        return "Spending Summary ({$period}): Total: ₱{$total}, Count: {$count}, Average: ₱{$avg}, Top Category: {$topCategory}";
                    } catch (\Exception $e) {
                        return "Failed to get spending summary: {$e->getMessage()}";
                    }
                }),

            // Tool to check points for a specific merchant
            Tool::as('check_merchant_points')
                ->for('Check if user can earn points at a specific merchant and get current points balance')
                ->withStringParameter('merchant_name', 'Name of the merchant to check (e.g., SM Supermarket, Puregold)')
                ->using(function (string $merchantName): string {
                    try {
                        $merchant = \App\Models\Merchant::where('name', 'like', "%{$merchantName}%")->first();

                        if (!$merchant) {
                            return "Merchant '{$merchantName}' not found in the system.";
                        }

                        $pointsService = new \App\Services\PointsService();
                        $status = $pointsService->getUserMerchantPointsStatus($this->user->id, $merchant->id);

                        return $status['message'];
                    } catch (\Exception $e) {
                        return "Failed to check merchant points: {$e->getMessage()}";
                    }
                }),

            // Tool to get all user points
            Tool::as('get_user_points')
                ->for('Get user\'s points balance for all merchants')
                ->using(function (): string {
                    try {
                        $pointsService = new \App\Services\PointsService();
                        $pointsSummary = $pointsService->getUserPointsSummary($this->user->id);

                        if (empty($pointsSummary)) {
                            return "🎯 Your Points Summary:\n\nYou don't have any points yet. Start shopping at participating merchants to earn points!";
                        }

                        $pointsList = collect($pointsSummary)
                            ->map(function ($merchant) {
                                return "• {$merchant['merchant_name']}: {$merchant['points']} points";
                            })
                            ->join("\n");

                        $totalPoints = collect($pointsSummary)->sum('points');

                        return "🎯 Your Points Summary:\n\n{$pointsList}\n\n💰 Total Points: {$totalPoints}";
                    } catch (\Exception $e) {
                        return "❌ Failed to get user points: {$e->getMessage()}";
                    }
                }),

            // Tool to get merchant-specific points and purchases
            Tool::as('get_merchant_details')
                ->for('Get detailed information about user\'s points and purchases at a specific merchant')
                ->withStringParameter('merchant_name', 'Name of the merchant to get details for')
                ->using(function (string $merchantName): string {
                    try {
                        $merchant = \App\Models\Merchant::where('name', 'like', "%{$merchantName}%")->first();

                        if (!$merchant) {
                            return "❌ Merchant '{$merchantName}' not found in the system.";
                        }

                        // Get points information
                        $pointsService = new \App\Services\PointsService();
                        $pointsStatus = $pointsService->getUserMerchantPointsStatus($this->user->id, $merchant->id);

                        // Get purchase history for this merchant
                        $purchases = $this->user->purchases()
                            ->where('merchant_name', 'like', "%{$merchant->name}%")
                            ->orderBy('purchase_date', 'desc')
                            ->limit(10)
                            ->get();

                        $icon = $pointsStatus['can_earn_points'] ? '✅' : '❌';

                        $response = "{$icon} {$merchant->name} Details:\n\nPoints Status: {$pointsStatus['message']}\n";

                        if ($purchases->isNotEmpty()) {
                            $response .= "\n📋 Recent Purchases:\n";

                            $totalSpent = 0;
                            foreach ($purchases as $purchase) {
                                $response .= "• {$purchase->title}: ₱{$purchase->amount} on {$purchase->purchase_date->format('M d, Y')}\n";
                                $totalSpent += $purchase->amount;
                            }

                            $response .= "\nTotal spent at {$merchant->name}: ₱" . number_format($totalSpent, 2) . "\n";
                            $response .= "Purchase count: {$purchases->count()}";
                        } else {
                            $response .= "\nNo purchase history found for this merchant.";
                        }

                        return $response;
                    } catch (\Exception $e) {
                        return "❌ Failed to get merchant details: {$e->getMessage()}";
                    }
                }),

            // Tool to get overall purchase history
            Tool::as('get_purchase_history')
                ->for('Get user\'s overall purchase history and summary')
                ->withStringParameter('period', 'Time period: all, month, quarter, year (default: all)')
                ->using(function (string $period = 'all'): string {
                    try {
                        $query = $this->user->purchases()->orderBy('purchase_date', 'desc');

                        // Apply time filter
                        if ($period !== 'all') {
                            $dates = $this->parseDateRange($period);
                            $query->whereBetween('purchase_date', $dates);
                        }

                        $purchases = $query->limit(20)->get();

                        if ($purchases->isEmpty()) {
                            return "<div class='info'>
                                <h3>📋 Purchase History</h3>
                                <p>No purchase history found" . ($period !== 'all' ? " for the last {$period}" : "") . ".</p>
                            </div>";
                        }

                        $response = "<div class='purchase-history'>
                            <h3>📋 Purchase History" . ($period !== 'all' ? " (Last {$period})" : "") . "</h3>";

                        $totalSpent = 0;
                        $merchantTotals = [];

                        foreach ($purchases as $purchase) {
                            $response .= "<div class='purchase-item'>
                                <div class='purchase-title'>{$purchase->title}</div>
                                <div class='purchase-details'>
                                    <span class='amount'>₱{$purchase->amount}</span>
                                    <span class='merchant'>at {$purchase->merchant_name}</span>
                                    <span class='date'>{$purchase->purchase_date->format('M d, Y')}</span>
                                </div>
                            </div>";
                            $totalSpent += $purchase->amount;

                            // Track merchant totals
                            $merchantName = $purchase->merchant_name ?? 'Unknown';
                            if (!isset($merchantTotals[$merchantName])) {
                                $merchantTotals[$merchantName] = 0;
                            }
                            $merchantTotals[$merchantName] += $purchase->amount;
                        }

                        $response .= "<div class='summary'>
                            <h4>📊 Summary</h4>
                            <p><strong>Total spent:</strong> ₱" . number_format($totalSpent, 2) . "</p>
                            <p><strong>Purchase count:</strong> {$purchases->count()}</p>
                            <p><strong>Average purchase:</strong> ₱" . number_format($totalSpent / $purchases->count(), 2) . "</p>
                        </div>";

                        $response .= "<div class='by-merchant'>
                            <h4>🏪 By Merchant</h4>";
                        arsort($merchantTotals);
                        foreach ($merchantTotals as $merchant => $amount) {
                            $response .= "<div class='merchant-total'>
                                <span class='merchant-name'>{$merchant}</span>
                                <span class='amount'>₱" . number_format($amount, 2) . "</span>
                            </div>";
                        }
                        $response .= "</div></div>";

                        return $response;
                    } catch (\Exception $e) {
                        return "<div class='error'>❌ Failed to get purchase history: {$e->getMessage()}</div>";
                    }
                }),



            // Tool to list all available merchants
            Tool::as('list_merchants')
                ->for('List all available merchants in the system with their descriptions')
                ->using(function (): string {
                    try {
                        $merchants = $this->getAvailableMerchants();

                        if (empty($merchants)) {
                            return "📋 No merchants found in the system.";
                        }

                        $merchantList = collect($merchants)->map(function ($merchant) {
                            $description = $merchant['description'] ? " - {$merchant['description']}" : '';
                            return "• {$merchant['name']}{$description}";
                        })->join("\n");

                        $totalMerchants = count($merchants);

                        return "🏪 Available Merchants ({$totalMerchants}):\n\n{$merchantList}\n\n💡 Tip: Ask about products at any merchant to see what's available!";
                    } catch (\Exception $e) {
                        return "❌ Failed to list merchants: {$e->getMessage()}";
                    }
                }),

            // Tool to get user's financial profile
            Tool::as('get_financial_profile')
                ->for('Get user\'s complete financial profile including salary, assets, liabilities, and net worth')
                ->using(function (): string {
                    try {
                        $user = $this->user;
                        $assets = $user->assets()->get();
                        $liabilities = $user->liabilities()->get();
                        $purchases = $user->purchases()->get();

                        // Calculate totals
                        $totalAssets = $assets->sum('value');
                        $totalLiabilities = $liabilities->where('status', 'active')->sum('amount');
                        $totalExpenses = $purchases->sum('amount');
                        $netWorth = $totalAssets - $totalLiabilities;
                        $monthlyLiabilities = $liabilities->where('status', 'active')->sum('monthly_payment');
                        $monthlyIncome = $user->salary ?? 0;
                        $netProfit = $monthlyIncome - $totalExpenses - $monthlyLiabilities;

                        $profile = "💰 Financial Profile Summary:\n\n";
                        $profile .= "📊 Income & Expenses:\n";
                        $profile .= "• Monthly Salary: ₱" . number_format($monthlyIncome, 2) . "\n";
                        $profile .= "• Total Expenses: ₱" . number_format($totalExpenses, 2) . "\n";
                        $profile .= "• Net Profit: ₱" . number_format($netProfit, 2) . "\n\n";

                        $profile .= "🏦 Assets & Liabilities:\n";
                        $profile .= "• Total Assets: ₱" . number_format($totalAssets, 2) . "\n";
                        $profile .= "• Total Liabilities: ₱" . number_format($totalLiabilities, 2) . "\n";
                        $profile .= "• Monthly Liability Payments: ₱" . number_format($monthlyLiabilities, 2) . "\n";
                        $profile .= "• Net Worth: ₱" . number_format($netWorth, 2) . "\n\n";

                        if ($assets->isNotEmpty()) {
                            $profile .= "📈 Assets ({$assets->count()}):\n";
                            foreach ($assets as $asset) {
                                $profile .= "• {$asset->name}: ₱" . number_format($asset->value, 2) . " ({$asset->type})\n";
                            }
                            $profile .= "\n";
                        }

                        if ($liabilities->isNotEmpty()) {
                            $profile .= "💳 Liabilities ({$liabilities->count()}):\n";
                            foreach ($liabilities as $liability) {
                                $status = $liability->status === 'active' ? '🟢' : '🔴';
                                $profile .= "• {$status} {$liability->name}: ₱" . number_format($liability->amount, 2) . " ({$liability->type})";
                                if ($liability->monthly_payment) {
                                    $profile .= " - Monthly: ₱" . number_format($liability->monthly_payment, 2);
                                }
                                $profile .= "\n";
                            }
                        }

                        return $profile;
                    } catch (\Exception $e) {
                        return "❌ Failed to get financial profile: {$e->getMessage()}";
                    }
                }),

            // Tool to add an asset
            Tool::as('add_asset')
                ->for('Add a new asset to the user\'s financial records')
                ->withObjectParameter(
                    'asset',
                    'The asset details',
                    [
                        new \Prism\Prism\Schema\StringSchema('name', 'Asset name/description'),
                        new \Prism\Prism\Schema\NumberSchema('value', 'Asset value'),
                        new \Prism\Prism\Schema\StringSchema('type', 'Asset type (cash, investment, property, vehicle, etc.)'),
                        new \Prism\Prism\Schema\StringSchema('description', 'Additional description (optional)'),
                        new \Prism\Prism\Schema\StringSchema('acquisition_date', 'Date acquired (YYYY-MM-DD, optional)')
                    ],
                    requiredFields: ['name', 'value', 'type']
                )
                ->using(function (array $asset): string {
                    try {
                        $newAsset = \App\Models\Asset::create([
                            'user_id' => $this->user->id,
                            'name' => $asset['name'],
                            'description' => $asset['description'] ?? null,
                            'value' => $asset['value'],
                            'type' => $asset['type'],
                            'currency' => 'PHP',
                            'acquisition_date' => $asset['acquisition_date'] ?? null,
                            'metadata' => []
                        ]);

                        return "✅ Asset added successfully: {$asset['name']} worth ₱" . number_format($asset['value'], 2) . " ({$asset['type']})";
                    } catch (\Exception $e) {
                        return "❌ Failed to add asset: {$e->getMessage()}";
                    }
                }),

            // Tool to add a liability
            Tool::as('add_liability')
                ->for('Add a new liability/debt to the user\'s financial records')
                ->withObjectParameter(
                    'liability',
                    'The liability details',
                    [
                        new \Prism\Prism\Schema\StringSchema('name', 'Liability name/description'),
                        new \Prism\Prism\Schema\NumberSchema('amount', 'Liability amount'),
                        new \Prism\Prism\Schema\StringSchema('type', 'Liability type (credit_card, loan, mortgage, etc.)'),
                        new \Prism\Prism\Schema\NumberSchema('monthly_payment', 'Monthly payment amount (optional)'),
                        new \Prism\Prism\Schema\StringSchema('description', 'Additional description (optional)'),
                        new \Prism\Prism\Schema\StringSchema('due_date', 'Due date (YYYY-MM-DD, optional)'),
                        new \Prism\Prism\Schema\NumberSchema('interest_rate', 'Annual interest rate (optional)')
                    ],
                    requiredFields: ['name', 'amount', 'type']
                )
                ->using(function (array $liability): string {
                    try {
                        $newLiability = \App\Models\Liability::create([
                            'user_id' => $this->user->id,
                            'name' => $liability['name'],
                            'description' => $liability['description'] ?? null,
                            'amount' => $liability['amount'],
                            'monthly_payment' => $liability['monthly_payment'] ?? null,
                            'type' => $liability['type'],
                            'currency' => 'PHP',
                            'due_date' => $liability['due_date'] ?? null,
                            'interest_rate' => $liability['interest_rate'] ?? null,
                            'status' => 'active',
                            'metadata' => []
                        ]);

                        $response = "✅ Liability added successfully: {$liability['name']} - ₱" . number_format($liability['amount'], 2) . " ({$liability['type']})";

                        if ($liability['monthly_payment']) {
                            $response .= "\n💳 Monthly payment: ₱" . number_format($liability['monthly_payment'], 2);
                        }

                        return $response;
                    } catch (\Exception $e) {
                        return "❌ Failed to add liability: {$e->getMessage()}";
                    }
                }),

            // Tool to intelligently record financial items (assets, liabilities, or expenses)
            Tool::as('record_financial_item')
                ->for('Intelligently detect and record financial items as assets, liabilities, or expenses based on context')
                ->withObjectParameter(
                    'financial_item',
                    'The financial item details with automatic categorization',
                    [
                        new \Prism\Prism\Schema\StringSchema('name', 'Item name/description'),
                        new \Prism\Prism\Schema\NumberSchema('amount', 'Item amount/value'),
                        new \Prism\Prism\Schema\StringSchema('category', 'Automatically detected category: asset, liability, or expense'),
                        new \Prism\Prism\Schema\StringSchema('subcategory', 'Specific type (e.g., cash, investment, credit_card, loan, food, transport)'),
                        new \Prism\Prism\Schema\StringSchema('description', 'Additional description (optional)'),
                        new \Prism\Prism\Schema\StringSchema('date', 'Date (YYYY-MM-DD, optional)'),
                        new \Prism\Prism\Schema\StringSchema('merchant_name', 'Merchant name (for expenses, optional)'),
                        new \Prism\Prism\Schema\NumberSchema('monthly_payment', 'Monthly payment (for liabilities, optional)'),
                        new \Prism\Prism\Schema\NumberSchema('interest_rate', 'Interest rate (for liabilities, optional)'),
                        new \Prism\Prism\Schema\StringSchema('due_date', 'Due date (for liabilities, optional)'),
                        new \Prism\Prism\Schema\StringSchema('acquisition_date', 'Acquisition date (for assets, optional)')
                    ],
                    requiredFields: ['name', 'amount', 'category', 'subcategory']
                )
                ->using(function (array $item): string {
                    try {
                        $category = strtolower($item['category']);
                        $subcategory = strtolower($item['subcategory']);

                        switch ($category) {
                            case 'asset':
                                // Record as asset in purchases table
                                $purchase = \App\Models\Purchase::create([
                                    'user_id' => $this->user->id,
                                    'title' => $item['name'],
                                    'description' => $item['description'] ?? null,
                                    'amount' => $item['amount'],
                                    'currency' => 'PHP',
                                    'merchant_name' => $item['merchant_name'] ?? null,
                                    'purchase_date' => $item['date'] ?? now()->format('Y-m-d'),
                                    'asset_type' => 'asset',
                                    'asset_value' => $item['amount'],
                                    'ai_categorized_category' => $subcategory,
                                    'metadata' => ['auto_categorized' => true]
                                ]);

                                return "✅ Asset recorded: {$item['name']} worth ₱" . number_format($item['amount'], 2) . " ({$subcategory}) | ASSET:{$item['name']} ₱" . number_format($item['amount'], 2);

                            case 'liability':
                                // Record as liability in purchases table
                                $purchase = \App\Models\Purchase::create([
                                    'user_id' => $this->user->id,
                                    'title' => $item['name'],
                                    'description' => $item['description'] ?? null,
                                    'amount' => $item['amount'],
                                    'currency' => 'PHP',
                                    'merchant_name' => $item['merchant_name'] ?? null,
                                    'purchase_date' => $item['date'] ?? now()->format('Y-m-d'),
                                    'asset_type' => 'liability',
                                    'liability_amount' => $item['amount'],
                                    'monthly_payment' => $item['monthly_payment'] ?? null,
                                    'liability_type' => $subcategory,
                                    'interest_rate' => $item['interest_rate'] ?? null,
                                    'due_date' => $item['due_date'] ?? null,
                                    'ai_categorized_category' => $subcategory,
                                    'metadata' => ['auto_categorized' => true]
                                ]);

                                $response = "✅ Liability recorded: {$item['name']} - ₱" . number_format($item['amount'], 2) . " ({$subcategory})";
                                if ($item['monthly_payment']) {
                                    $response .= "\n💳 Monthly payment: ₱" . number_format($item['monthly_payment'], 2);
                                }
                                $response .= " | LIABILITY:{$item['name']} ₱" . number_format($item['amount'], 2);
                                return $response;

                            case 'expense':
                                // Record as purchase/expense
                                $purchase = \App\Models\Purchase::create([
                                    'user_id' => $this->user->id,
                                    'title' => $item['name'],
                                    'description' => $item['description'] ?? null,
                                    'amount' => $item['amount'],
                                    'currency' => 'PHP',
                                    'merchant_name' => $item['merchant_name'] ?? null,
                                    'purchase_date' => $item['date'] ?? now()->format('Y-m-d'),
                                    'ai_categorized_category' => $subcategory,
                                    'metadata' => ['auto_categorized' => true]
                                ]);

                                return "✅ Expense recorded: {$item['name']} for ₱" . number_format($item['amount'], 2) . " ({$subcategory}) | EXPENSE:{$item['name']} ₱" . number_format($item['amount'], 2);

                            default:
                                return "❌ Unknown category: {$category}. Please specify asset, liability, or expense.";
                        }
                    } catch (\Exception $e) {
                        return "❌ Failed to record financial item: {$e->getMessage()}";
                    }
                })
        ];
    }

    /**
     * Extract purchases from message and save them to database
     */
    protected function extractAndSavePurchases(string $message): array
    {
        $purchases = $this->extractPurchasesFromMessage($message);
        $savedPurchases = [];

        if (!empty($purchases)) {
            foreach ($purchases as $purchase) {
                try {
                    // Normalize the purchase data
                    $title = $purchase['title'] ?? '';
                    $amount = (float) ($purchase['amount'] ?? 0);
                    $merchantName = $purchase['merchant_name'] ?? null;
                    $quantity = $purchase['quantity'] ?? '1';
                    $date = $purchase['date'] ?? now()->format('Y-m-d');
                    $description = $purchase['description'] ?? null;

                    // Skip if no valid title or amount
                    if (empty($title) || $amount <= 0) {
                        continue;
                    }

                    // Check if this purchase already exists (prevent duplicates)
                    $existingPurchase = Purchase::where('user_id', $this->user->id)
                        ->where('title', $title)
                        ->where('amount', $amount)
                        ->where('purchase_date', $date)
                        ->where('created_at', '>=', now()->subMinutes(5)) // Check for duplicates in last 5 minutes
                        ->first();

                    if ($existingPurchase) {
                        // Purchase already exists, skip
                        Log::info('Purchase already exists, skipping', [
                            'user_id' => $this->user->id,
                            'title' => $title,
                            'amount' => $amount,
                            'existing_id' => $existingPurchase->id
                        ]);
                        continue;
                    }

                    $savedPurchase = Purchase::create([
                        'user_id' => $this->user->id,
                        'title' => $title,
                        'description' => $description,
                        'amount' => $amount,
                        'currency' => 'PHP',
                        'merchant_name' => $merchantName,
                        'purchase_date' => $date,
                        'metadata' => ['quantity' => $quantity]
                    ]);

                    // Auto-categorize the purchase
                    $category = $this->autoCategorizePurchase($savedPurchase);
                    $savedPurchase->update(['ai_categorized_category' => $category]);

                    $savedPurchases[] = [
                        'title' => $savedPurchase->title,
                        'amount' => (string) $savedPurchase->amount,
                        'category' => $savedPurchase->ai_categorized_category,
                        'date' => $savedPurchase->purchase_date->format('Y-m-d')
                    ];

                    // Award points if it's a system merchant
                    if ($merchantName) {
                        $merchant = \App\Models\Merchant::where('name', 'like', "%{$merchantName}%")->first();
                        if ($merchant) {
                            $pointsService = new \App\Services\PointsService();
                            // Create a temporary transaction for points calculation
                            $transaction = new \App\Models\Transaction([
                                'user_id' => $this->user->id,
                                'merchant_id' => $merchant->id,
                                'amount' => $amount,
                                'status' => 'completed'
                            ]);
                            $pointsService->awardPoints($transaction);
                        }
                    }
                } catch (\Exception $e) {
                    Log::warning('Failed to save purchase', [
                        'user_id' => $this->user->id,
                        'purchase' => $purchase,
                        'error' => $e->getMessage()
                    ]);
                }
            }
        }

        return $savedPurchases;
    }

    /**
     * Build context for tool processing
     */
    protected function buildContext(string $message): string
    {
        $userContext = $this->buildUserContext();
        $recentPurchases = $this->getRecentPurchaseSummary();
        $memories = $this->getRelevantMemories($message);
        $availableMerchants = $this->getAvailableMerchants();

        $context = "User Context:\n";
        $context .= "- Total spent: ₱{$userContext['total_spent']}\n";
        $context .= "- Purchase count: {$userContext['purchase_count']}\n";
        $context .= "- Favorite category: {$userContext['favorite_category']}\n";
        $context .= "- Recent purchases: " . count($recentPurchases['recent_purchases']) . " in last 10\n";

        if (!empty($memories)) {
            $context .= "- Recent memories: " . count($memories) . " relevant items\n";
        }

        $context .= "- Available merchants: " . count($availableMerchants) . " merchants\n\n";

        $context .= "Available Tools:\n";
        $context .= "1. If they ask about products at a merchant, use get_merchant_products tool\n";
        $context .= "2. If they want to calculate purchase cost and points, use calculate_purchase_points tool\n";
        $context .= "3. If they want to record a purchase, use confirm_purchase_details tool first, then add_purchase tool\n";
        $context .= "4. Check points for merchants mentioned using check_merchant_points tool\n";
        $context .= "5. Get user's points summary if they ask about points using get_user_points tool\n";
        $context .= "6. Get merchant-specific details if they ask about points/purchases at a specific merchant using get_merchant_details tool\n";
        $context .= "7. Get purchase history if they ask about overall spending or transaction history using get_purchase_history tool\n";
        $context .= "8. Store important information using store_memory tool\n";
        $context .= "9. Get spending summary if relevant using get_spending_summary tool\n";
        $context .= "\nIMPORTANT: For product inquiries like 'What products does [merchant] have?', ALWAYS use get_merchant_products tool.\n";
        $context .= "IMPORTANT: For specific product price queries like 'How much is [product] at [merchant]?', use get_merchant_products tool.\n";
        $context .= "IMPORTANT: Remember conversation context and previous interactions.\n";

        // Add relevant memories based on the current message
        $relevantMemories = $this->getRelevantMemories($message);

        // Log memory retrieval for debugging
        Log::info('Memory retrieval', [
            'user_id' => $this->user->id,
            'message' => $message,
            'relevant_memories_count' => count($relevantMemories),
            'total_user_memories' => $this->user->memories()->count()
        ]);

        if (!empty($relevantMemories)) {
            $context .= "\n📝 RELEVANT CONVERSATION MEMORY:\n";
            $context .= "Based on your current message, here's what I remember from our previous conversations:\n";

            foreach ($relevantMemories as $memory) {
                $context .= "• [{$memory['type']}] {$memory['content']}\n";
            }

            $context .= "\nUse this context to provide more personalized and relevant responses.\n";
        } else {
            // Add recent memories even if not directly relevant
            $recentMemories = $this->user->memories()
                ->orderBy('created_at', 'desc')
                ->limit(3)
                ->get();

            if ($recentMemories->isNotEmpty()) {
                $context .= "\n📝 RECENT CONVERSATION MEMORY:\n";
                $context .= "Here are some recent conversations we've had:\n";

                foreach ($recentMemories as $memory) {
                    $context .= "• [{$memory->type}] {$memory->content}\n";
                }

                $context .= "\nUse this context to provide continuity in our conversation.\n";
            }
        }

        // Add conversation context
        $context .= $this->getConversationContext($message);

        // Add enhanced context for important questions
        if ($this->isImportantQuestion($message)) {
            Log::info('Important question detected', [
                'user_id' => $this->user->id,
                'message' => $message,
                'question_type' => 'financial_advice'
            ]);

            $context .= "\n🎯 IMPORTANT QUESTION DETECTED: This appears to be a significant financial question.\n";
            $context .= "Please provide detailed, educational explanations that help the user understand the concepts, reasoning, and practical implications.\n";
            $context .= "Explain WHY and HOW things work, and what the user can do to improve their financial situation.\n\n";
        }

        return $context;
    }

    /**
     * Build context for insights generation
     */
    protected function buildInsightsContext(string $message, string $toolResults): string
    {
        $userContext = $this->buildUserContext();
        $purchaseHistory = $this->getRecentPurchaseSummary();
        $memories = $this->getRelevantMemories($message);
        $currentSummary = $this->getCurrentSummary();

        $context = "User Message: {$message}\n\n";
        $context .= "Tool Results: {$toolResults}\n\n";
        $context .= "User Context: " . json_encode($userContext) . "\n";
        $context .= "Recent Purchase History: " . json_encode($purchaseHistory) . "\n";
        $context .= "Current Financial Summary: " . json_encode($currentSummary) . "\n";
        $context .= "Relevant Memories: " . json_encode($memories) . "\n\n";

        // Add memory context instructions
        if (!empty($memories)) {
            $context .= "MEMORY CONTEXT: The user has previous conversations and preferences stored. Use this information to:\n";
            $context .= "1. Reference previous discussions and build on them\n";
            $context .= "2. Acknowledge their preferences and goals\n";
            $context .= "3. Provide continuity in the conversation\n";
            $context .= "4. Show that you remember their financial situation and concerns\n\n";
        }

        // Add conversation context
        $context .= $this->getConversationContext($message);

        // Add enhanced context for important questions
        if ($this->isImportantQuestion($message)) {
            Log::info('Important question detected in insights', [
                'user_id' => $this->user->id,
                'message' => $message,
                'question_type' => 'financial_insights'
            ]);

            $context .= $this->getEnhancedContextForImportantQuestions($message);
        }

        $context .= "Instructions: Based on the user's message and tool results, provide:\n";
        $context .= "1. A natural, conversational response that sounds like talking to a friend\n";
        $context .= "2. Comprehensive financial advice with explanations of WHY and HOW\n";
        $context .= "3. Detailed insights about their spending patterns with explanations of implications\n";
        $context .= "4. Specific, actionable recommendations with clear implementation steps\n";
        $context .= "5. List of purchases added (if any)\n\n";
        $context .= "CONVERSATION STYLE: Be natural and conversational. Don't be overly formal or repetitive. Avoid saying 'Hello [Name]! I'm Papi, your financial advisor' unless it's the very first message. Talk like you're having a casual conversation with a friend.\n\n";
        $context .= "IMPORTANT: If the user asks important questions about finances, goals, or financial health, provide detailed explanations that educate and empower them. Explain the reasoning behind your advice and recommendations.\n\n";
        $context .= "The response will be automatically structured according to the schema provided.\n";

        return $context;
    }

    /**
     * Get system prompt for tool processing
     */
    protected function getToolSystemPrompt(): string
    {
        return "You are a financial assistant with loyalty points expertise. Your job is to:

                1. **Merchant Listings**: When users ask about merchants, 'what are the merchants', 'show me merchants', 'list merchants', or any variation asking about available merchants, ALWAYS use list_merchants tool to show all available merchants in the system.

                2. **Product Inquiries**: When users ask about products at a merchant, use get_merchant_products tool to show available products with prices and points earning potential.

                3. **Specific Product Queries**: When users ask about specific product prices (e.g., 'How much is Yumburger at Jollibee?'), use get_merchant_products tool to find and show the specific product price.

                4. **Purchase Calculations**: When users want to calculate total cost and points for specific items, use calculate_purchase_points tool to provide detailed breakdown.

                5. **Purchase Confirmation**: When users want to record a purchase, use confirm_purchase_details tool to verify details and identify if it's a system merchant or external merchant.

                6. **Extract Purchases**: When users confirm purchases, use add_purchase tool to record them in their financial records.

                7. **Check Points**: When users mention specific merchants, use check_merchant_points tool to tell them about earning points at that merchant.

                8. **Points Summary**: IMPORTANT - When users ask about their points, 'how many points I have', 'my points', 'points balance', 'points summary', or any variation asking about their points across merchants, ALWAYS use get_user_points tool first to show their points balance across all merchants.

                9. **Merchant Details**: When users ask about their points and purchases at a specific merchant, use get_merchant_details tool to show detailed information about that merchant.

                10. **Purchase History**: When users ask about their purchase history, overall spending, or transaction history, use get_purchase_history tool to show their complete purchase history.

                11. **Store Information**: Use the store_memory tool to save important information about the user's preferences, goals, and financial situation.

                12. **Get Data**: Use the get_spending_summary tool when you need to analyze spending patterns.

                                13. **Financial Profile Management**: When users ask about their financial situation, salary, assets, liabilities, or net worth, use get_financial_profile tool to provide a comprehensive overview.

                14. **Intelligent Financial Recording**: CRITICAL - When users mention any financial items (money, purchases, debts, assets, etc.), use the record_financial_item tool to automatically detect and categorize them. This tool can intelligently determine if something is an asset, liability, or expense based on context.

                **Categorization Rules:**

                **ASSETS** (Items that retain or increase in value):
                - Real estate: houses, land, commercial properties, rental properties
                - Vehicles: cars, motorcycles, boats, aircraft, trucks
                - Investments: stocks, bonds, mutual funds, crypto, ETFs, retirement accounts
                - Jewelry: gold, diamonds, watches, precious metals, luxury items
                - Business assets: equipment, machinery, intellectual property, patents
                - Collectibles: art, antiques, wine, rare items, memorabilia
                - Cash and bank accounts: savings, checking, money market
                - Digital assets: domain names, digital art, NFTs

                **LIABILITIES** (Debts and financial obligations):
                - Loans: car loans, personal loans, business loans, student loans, payday loans
                - Mortgages: home loans, property financing, construction loans
                - Credit cards: outstanding balances, credit card debt, store cards
                - Insurance premiums: health, life, car, property insurance (monthly/annual payments)
                - Taxes owed: income tax, property tax, business tax, VAT
                - Utility bills: electricity, water, internet, phone (if outstanding/overdue)
                - Subscriptions: streaming services, gym memberships, software licenses, magazines
                - Medical bills: outstanding healthcare expenses, dental work, prescriptions
                - Legal obligations: court judgments, alimony, child support, fines
                - Business debts: vendor payments, equipment financing, lines of credit

                **EXPENSES** (Regular spending on consumables):
                - Food & Dining: groceries, restaurants, takeout, coffee, snacks
                - Transportation: gas, public transport, parking, tolls, ride-sharing
                - Utilities: electricity, water, internet, phone bills (when paid regularly)
                - Entertainment: movies, games, hobbies, events, concerts, sports
                - Clothing: clothes, shoes, accessories, uniforms
                - Healthcare: doctor visits, medicine, dental care, therapy
                - Education: books, courses, training, workshops, seminars
                - Personal care: haircuts, beauty products, gym fees, spa treatments
                - Home maintenance: repairs, cleaning supplies, furniture, appliances
                - Miscellaneous: gifts, donations, small purchases, fees

                **IMPORTANT**: All financial items are now recorded in the purchases table with an asset_type field:
                - asset_type = 'asset' for assets
                - asset_type = 'liability' for liabilities
                - asset_type = null for expenses

                **EXAMPLES OF INTELLIGENT CATEGORIZATION:**

                **ASSETS:**
                - 'I bought a house for ₱10M' → ASSET:House ₱10,000,000.00
                - 'I purchased a Ferrari for ₱8M' → ASSET:Ferrari ₱8,000,000.00
                - 'I invested ₱2M in stocks' → ASSET:Stock Investment ₱2,000,000.00
                - 'I bought gold jewelry worth ₱500K' → ASSET:Gold Jewelry ₱500,000.00
                - 'I bought land for ₱5M' → ASSET:Land ₱5,000,000.00
                - 'I purchased a Rolex watch for ₱1M' → ASSET:Rolex Watch ₱1,000,000.00

                **LIABILITIES:**
                - 'I have a mortgage of ₱8M' → LIABILITY:Mortgage ₱8,000,000.00
                - 'I owe ₱2M on my car loan' → LIABILITY:Car Loan ₱2,000,000.00
                - 'I pay ₱80K monthly for health insurance' → LIABILITY:Health Insurance ₱80,000.00
                - 'I have ₱500K credit card debt' → LIABILITY:Credit Card Debt ₱500,000.00
                - 'I owe ₱100K in taxes' → LIABILITY:Tax Debt ₱100,000.00
                - 'I have a student loan of ₱1.5M' → LIABILITY:Student Loan ₱1,500,000.00
                - 'I pay ₱15K monthly for Netflix and Spotify' → LIABILITY:Streaming Subscriptions ₱15,000.00

                **EXPENSES:**
                - 'I spent ₱2K on groceries' → EXPENSE:Groceries ₱2,000.00
                - 'I paid ₱1.5K for gas' → EXPENSE:Gas ₱1,500.00
                - 'I spent ₱500 on dinner' → EXPENSE:Dinner ₱500.00
                - 'I paid ₱3K for electricity bill' → EXPENSE:Electricity ₱3,000.00
                - 'I bought ₱200 worth of socks' → EXPENSE:Socks ₱200.00
                - 'I spent ₱1K on movie tickets' → EXPENSE:Movie Tickets ₱1,000.00

                **SPECIAL CASES:**
                - Insurance: If it's a premium payment (monthly/yearly) → LIABILITY, if it's a one-time payment for coverage → EXPENSE
                - Utilities: If it's an outstanding bill → LIABILITY, if it's a regular payment → EXPENSE
                - Subscriptions: Usually LIABILITIES as they represent ongoing obligations
                - Large purchases: Consider if it's an investment (ASSET) vs. consumption (EXPENSE)
                - Business expenses: Usually EXPENSES unless they're capital investments (ASSETS)

                15. **Legacy Tools**: Only use add_asset and add_liability tools for specific, explicit requests where the user clearly states they want to add an asset or liability manually.

                18. **Memory and Context Awareness**: CRITICAL - You have access to the user's conversation memory and previous interactions. ALWAYS use this information to:
                    - Reference previous conversations and build continuity
                    - Acknowledge their preferences, goals, and financial concerns
                    - Show that you remember their financial situation
                    - Provide personalized responses based on their history
                    - If they ask about previous conversations, reference the stored memories
                    - If they mention something from a previous conversation, acknowledge it
                    - Build on previous advice and recommendations

                    Examples:
                    - If they previously mentioned saving for vacation, reference that goal in future responses
                    - If they asked about budgeting before, acknowledge that context
                    - If they mentioned specific preferences, incorporate them into recommendations

                19. **Be Efficient**: Only use tools when necessary. Don't use tools if the user is just asking for advice without mentioning purchases, products, points, assets, or liabilities.

                CRITICAL: If the user asks about merchants in any way (e.g., 'What are the merchants?', 'Show me merchants', 'List merchants', 'What merchants do you have?'), you MUST use the list_merchants tool to show all available merchants in the system.

                CRITICAL: If the user asks about their points in any way, you MUST use the get_user_points tool to provide accurate information about their points balance across all merchants. Do not provide generic advice about points without checking their actual balance.

                CRITICAL: If the user asks about products at a merchant (e.g., 'What products does Jollibee have?', 'Show me products at SM', 'What's available at Puregold?'), you MUST use the get_merchant_products tool to show the actual products with prices and points information. Do not provide generic advice about products without checking what's actually available.

                CRITICAL: If the user asks about specific product prices (e.g., 'How much is Yumburger?', 'What's the price of Chickenjoy?'), you MUST use the get_merchant_products tool to find and show the specific product price.

                CRITICAL: If the user asks about their financial situation, salary, assets, liabilities, net worth, or financial profile in any way, you MUST use the get_financial_profile tool to provide accurate information about their complete financial status. Do not provide generic advice without checking their actual financial data.

                CRITICAL: If the user mentions any financial items (money, purchases, debts, assets, etc.), you MUST use the record_financial_item tool to automatically detect and categorize them properly. This tool will intelligently determine if something is an asset, liability, or expense.

                CRITICAL: Only use add_asset and add_liability tools for specific, explicit requests where the user clearly states they want to manually add an asset or liability.

                Always use the available tools to perform actions. Be proactive in helping users manage their finances and loyalty points. Maintain conversation context and remember previous interactions.

                CONVERSATION STYLE: Be natural and conversational, like talking to a friend. Don't be overly formal or repetitive. Avoid formal introductions unless it's the very first message. Use casual language, contractions, and reference previous conversations naturally. Ask follow-up questions and show genuine interest.

                IMPORTANT: When users ask important or insightful questions about their finances, provide detailed, educational explanations that help them understand the concepts, reasoning, and practical implications. Don't just give surface-level advice - explain WHY and HOW things work, and what the user can do to improve their financial situation.";
    }

    /**
     * Get system prompt for insights generation
     */
    protected function getInsightsSystemPrompt(): string
    {
        return "You are Papi, a friendly and knowledgeable financial advisor. You speak naturally like a real person having a casual conversation with a friend. Your responses should be conversational, empathetic, and genuinely helpful.

            CORE PRINCIPLES:
            1. **Be Natural**: Talk like you're chatting with a friend, not giving a formal presentation
            2. **Show Understanding**: Acknowledge what they've shared and build on it naturally
            3. **Provide Context**: When giving advice, explain why it matters and how it helps them
            4. **Be Educational**: Help them understand financial concepts in simple terms
            5. **Stay Focused**: Don't repeat yourself or ask redundant questions

            CONVERSATION GUIDELINES:
            - Use natural language: 'Hey!', 'That's interesting', 'I see what you mean'
            - Reference previous conversations naturally: 'As we discussed earlier...' or 'Building on what you mentioned...'
            - Ask genuine follow-up questions: 'How do you feel about that?' or 'What's your take on this?'
            - Show empathy: 'I understand that can be tough' or 'That makes sense'
            - Use contractions: you're, I'm, that's, it's, we're
            - Keep responses conversational, not robotic

            WHEN TO PROVIDE INSIGHTS:
            - When they share significant financial information (income, expenses, debts)
            - When they ask for advice or seem uncertain
            - When patterns emerge in their financial behavior
            - When opportunities for improvement are identified

            WHEN TO PROVIDE RECOMMENDATIONS:
            - When they're making financial decisions
            - When there are clear areas for improvement
            - When they ask for specific guidance
            - When you can offer actionable steps

            AVOID:
            - Repetitive questions they've already answered
            - Formal greetings in ongoing conversations
            - Generic responses that don't address their specific situation
            - Over-explaining simple concepts
            - Asking obvious questions

            EXAMPLE NATURAL RESPONSES:
            - '₱10K monthly for car insurance? That's a significant regular expense. How does that fit into your overall budget?'
            - 'I see you're tracking your car insurance payments. That's smart - regular expenses like this can really add up over time.'
            - 'So you're spending ₱10K monthly on car insurance. That's ₱120K annually. Have you shopped around for better rates recently?'

            Remember: You're having a natural conversation, not conducting an interview. Build on what they share, show understanding, and provide helpful insights when relevant.";
    }



    /**
     * Get user's assets
     */
    protected function getUserAssets(): array
    {
        try {
            $assets = $this->user->purchases()
                ->where('asset_type', 'asset')
                ->orderBy('created_at', 'desc')
                ->get();

            return $assets->map(function ($purchase) {
                return [
                    'id' => $purchase->id,
                    'name' => $purchase->title,
                    'description' => $purchase->description,
                    'value' => $purchase->asset_value,
                    'type' => $purchase->ai_categorized_category,
                    'currency' => $purchase->currency,
                    'acquisition_date' => $purchase->purchase_date?->format('Y-m-d'),
                    'created_at' => $purchase->created_at->format('Y-m-d H:i:s'),
                    'updated_at' => $purchase->updated_at->format('Y-m-d H:i:s'),
                ];
            })->toArray();
        } catch (\Exception $e) {
            Log::error('Failed to get user assets', [
                'user_id' => $this->user->id,
                'error' => $e->getMessage()
            ]);
            return [];
        }
    }

    /**
     * Get user's liabilities
     */
    protected function getUserLiabilities(): array
    {
        try {
            $liabilities = $this->user->purchases()
                ->where('asset_type', 'liability')
                ->orderBy('created_at', 'desc')
                ->get();

            return $liabilities->map(function ($purchase) {
                return [
                    'id' => $purchase->id,
                    'name' => $purchase->title,
                    'description' => $purchase->description,
                    'amount' => $purchase->liability_amount,
                    'monthly_payment' => $purchase->monthly_payment,
                    'type' => $purchase->liability_type,
                    'currency' => $purchase->currency,
                    'due_date' => $purchase->due_date?->format('Y-m-d'),
                    'interest_rate' => $purchase->interest_rate,
                    'status' => 'active',
                    'created_at' => $purchase->created_at->format('Y-m-d H:i:s'),
                    'updated_at' => $purchase->updated_at->format('Y-m-d H:i:s'),
                ];
            })->toArray();
        } catch (\Exception $e) {
            Log::error('Failed to get user liabilities', [
                'user_id' => $this->user->id,
                'error' => $e->getMessage()
            ]);
            return [];
        }
    }

    /**
     * Format purchases added to show type (asset/liability/expense)
     */
    protected function formatPurchasesAdded(array $purchases): array
    {
        $formatted = [];

        foreach ($purchases as $purchase) {
            // Check if the purchase string contains type information (ASSET:, LIABILITY:, EXPENSE:)
            if (preg_match('/(ASSET|LIABILITY|EXPENSE):(.*?)\s+₱([\d,]+\.?\d*)/', $purchase, $matches)) {
                $type = strtolower($matches[1]);
                $itemName = trim($matches[2]);
                $amount = str_replace(',', '', $matches[3]);

                $formatted[] = [
                    'item' => $itemName,
                    'amount' => (float) $amount,
                    'type' => $type,
                    'currency' => 'PHP'
                ];
            } else {
                // Handle complex item names with amounts embedded
                if (preg_match('/^(.*?)\s*\(₱([\d,]+\.?\d*).*?\)$/', $purchase, $matches)) {
                    $itemName = trim($matches[1]);
                    $amount = str_replace(',', '', $matches[2]);

                    // Determine the type based on the item name and context
                    $type = $this->determineItemType($itemName, $amount);

                    $formatted[] = [
                        'item' => $itemName,
                        'amount' => (float) $amount,
                        'type' => $type,
                        'currency' => 'PHP'
                    ];
                } else {
                    // Fallback: Extract the item name and amount from the purchase string
                    if (preg_match('/^(.*?)\s+₱([\d,]+\.?\d*)$/', $purchase, $matches)) {
                        $itemName = trim($matches[1]);
                        $amount = str_replace(',', '', $matches[2]);

                        // Determine the type based on the item name and context
                        $type = $this->determineItemType($itemName, $amount);

                        $formatted[] = [
                            'item' => $itemName,
                            'amount' => (float) $amount,
                            'type' => $type,
                            'currency' => 'PHP'
                        ];
                    } else {
                        // Fallback for items that don't match any pattern
                        $formatted[] = [
                            'item' => $purchase,
                            'amount' => 0,
                            'type' => 'expense',
                            'currency' => 'PHP'
                        ];
                    }
                }
            }
        }

        return $formatted;
    }

    /**
     * Determine if an item is an asset, liability, or expense
     */
    protected function determineItemType(string $itemName, string $amount): string
    {
        $itemName = strtolower($itemName);

        // Asset keywords
        $assetKeywords = [
            'car',
            'vehicle',
            'house',
            'home',
            'property',
            'land',
            'real estate',
            'investment',
            'stock',
            'bond',
            'crypto',
            'bitcoin',
            'ethereum',
            'jewelry',
            'gold',
            'silver',
            'diamond',
            'watch',
            'rolex',
            'art',
            'painting',
            'sculpture',
            'antique',
            'collectible',
            'business',
            'equipment',
            'machinery',
            'patent',
            'trademark',
            'savings',
            'bank',
            'account',
            'cash',
            'money',
            'furniture',
            'electronics',
            'computer',
            'phone',
            'laptop',
            'boat',
            'aircraft',
            'motorcycle',
            'truck',
            'van',
            'commercial',
            'rental',
            'office',
            'warehouse',
            'factory',
            'domain',
            'website',
            'nft',
            'digital',
            'asset',
            'ferrari',
            'lamborghini',
            'porsche',
            'bmw',
            'mercedes',
            'apartment',
            'condo',
            'villa',
            'mansion',
            'farm',
            'portfolio',
            'fund',
            'etf',
            'mutual fund',
            'retirement',
            'precious metal',
            'platinum',
            'palladium',
            'gemstone',
            'luxury',
            'designer',
            'brand',
            'limited edition'
        ];

        // Liability keywords
        $liabilityKeywords = [
            'loan',
            'debt',
            'credit',
            'mortgage',
            'car loan',
            'student loan',
            'personal loan',
            'business loan',
            'credit card',
            'overdraft',
            'payday loan',
            'home equity',
            'line of credit',
            'borrowed',
            'owe',
            'financing',
            'lease',
            'rental',
            'subscription',
            'bill',
            'outstanding',
            'balance',
            'arrears',
            'default',
            'collection',
            'insurance',
            'health insurance',
            'life insurance',
            'car insurance',
            'property insurance',
            'premium',
            'policy',
            'coverage',
            'claim',
            'agent',
            'broker',
            'protection',
            'security',
            'tax',
            'taxes',
            'income tax',
            'property tax',
            'business tax',
            'utility',
            'electricity',
            'water',
            'internet',
            'phone bill',
            'medical',
            'dental',
            'healthcare',
            'prescription',
            'treatment',
            'legal',
            'court',
            'judgment',
            'alimony',
            'child support',
            'fine',
            'penalty',
            'fee',
            'charge',
            'overdue',
            'streaming',
            'netflix',
            'spotify',
            'gym',
            'membership',
            'subscription',
            'monthly',
            'annual',
            'recurring',
            'obligation'
        ];

        // Check for liability keywords first (higher priority)
        foreach ($liabilityKeywords as $keyword) {
            if (strpos($itemName, $keyword) !== false) {
                // Skip if it's a common expense word that might contain liability keywords
                if (in_array($itemName, ['coffee', 'dinner', 'lunch', 'breakfast', 'snack', 'food', 'meal'])) {
                    continue;
                }
                return 'liability';
            }
        }

        // Check for asset keywords
        foreach ($assetKeywords as $keyword) {
            if (strpos($itemName, $keyword) !== false) {
                return 'asset';
            }
        }

        // Default to expense for items that don't match asset or liability patterns
        return 'expense';
    }

    /**
     * Get current financial summary
     */
    protected function getCurrentSummary(): array
    {
        $totalSpent = $this->user->purchases()->sum('amount');
        $avgPurchase = $this->user->purchases()->avg('amount');
        $purchaseCount = $this->user->purchases()->count();
        $favoriteCategory = $this->getFavoriteCategory();

        return [
            'total_spent' => (float) $totalSpent,
            'purchase_count' => (int) $purchaseCount,
            'average_purchase' => (float) $avgPurchase,
            'top_category' => $favoriteCategory
        ];
    }

    /**
     * Get user's points summary
     */
    protected function getUserPointsSummary(): array
    {
        $pointsService = new \App\Services\PointsService();
        return $pointsService->getUserPointsSummary($this->user->id);
    }

    /**
     * Get product points information
     */
    protected function getProductPointsInfo($product): string
    {
        $pointsRules = $product->pointsRules;
        if ($pointsRules->isEmpty()) {
            return "No points";
        }

        $pointsInfo = [];
        foreach ($pointsRules as $rule) {
            switch ($rule->type->value) {
                case 'fixed':
                    $points = $rule->parameters['points'] ?? 0;
                    $pointsInfo[] = "{$points} points";
                    break;
                case 'dynamic':
                    $div = $rule->parameters['divisor'] ?? 1;
                    $mul = $rule->parameters['multiplier'] ?? 1;
                    $rate = $mul / $div;
                    $pointsInfo[] = "{$rate} points per peso";
                    break;
                default:
                    $pointsInfo[] = "Special points rule";
            }
        }

        return implode(', ', $pointsInfo);
    }

    /**
     * Calculate points for a product purchase
     */
    protected function calculateProductPoints($product, float $amount, int $quantity): int
    {
        $pointsRules = $product->pointsRules;
        if ($pointsRules->isEmpty()) {
            return 0;
        }

        $totalPoints = 0;
        foreach ($pointsRules as $rule) {
            switch ($rule->type->value) {
                case 'fixed':
                    $points = $rule->parameters['points'] ?? 0;
                    $totalPoints += $points;
                    break;
                case 'dynamic':
                    $div = $rule->parameters['divisor'] ?? 1;
                    $mul = $rule->parameters['multiplier'] ?? 1;
                    $totalPoints += (int) floor($amount / $div) * $mul;
                    break;
                case 'combo':
                    $div = $rule->parameters['divisor'] ?? 1;
                    $amtMul = $rule->parameters['amount_multiplier'] ?? 1;
                    $qtyMul = $rule->parameters['quantity_multiplier'] ?? 1;
                    $amountPoints = floor($amount / $div) * $amtMul;
                    $quantityPoints = $quantity * $qtyMul;
                    $totalPoints += (int) ($amountPoints + $quantityPoints);
                    break;
            }
        }

        return $totalPoints;
    }

    /**
     * Get available merchants in the system
     */
    protected function getAvailableMerchants(): array
    {
        return \App\Models\Merchant::select('id', 'name', 'description')
            ->get()
            ->map(function ($merchant) {
                return [
                    'id' => $merchant->id,
                    'name' => $merchant->name,
                    'description' => $merchant->description
                ];
            })
            ->toArray();
    }

    /**
     * Helper methods
     */
    protected function buildUserContext(): array
    {
        $totalSpent = $this->user->purchases()->sum('amount');
        $avgPurchase = $this->user->purchases()->avg('amount');
        $purchaseCount = $this->user->purchases()->count();
        $favoriteCategory = $this->getFavoriteCategory();

        return [
            'total_spent' => $totalSpent,
            'average_purchase' => $avgPurchase,
            'purchase_count' => $purchaseCount,
            'favorite_category' => $favoriteCategory,
            'member_since' => $this->user->created_at->format('Y-m-d')
        ];
    }

    protected function getRecentPurchaseSummary(): array
    {
        $recentPurchases = $this->user->purchases()
            ->orderBy('purchase_date', 'desc')
            ->take(10)
            ->get();

        return [
            'recent_purchases' => $recentPurchases->map(function ($purchase) {
                return [
                    'title' => $purchase->title,
                    'amount' => $purchase->amount,
                    'category' => $purchase->ai_categorized_category,
                    'date' => $purchase->purchase_date->format('Y-m-d')
                ];
            })->toArray(),
            'total_recent' => $recentPurchases->sum('amount'),
            'count_recent' => $recentPurchases->count()
        ];
    }

    protected function getRelevantMemories(string $query): array
    {
        // Get recent memories first (last 7 days)
        $recentMemories = $this->user->memories()
            ->where('created_at', '>=', now()->subDays(7))
            ->orderBy('importance', 'desc')
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get();

        // Get important memories (importance >= 7)
        $importantMemories = $this->user->memories()
            ->where('importance', '>=', 7)
            ->orderBy('importance', 'desc')
            ->orderBy('last_accessed_at', 'desc')
            ->limit(5)
            ->get();

        // Combine and deduplicate
        $allMemories = $recentMemories->merge($importantMemories)->unique('id');

        // Filter by relevance to current query
        $relevantMemories = $allMemories->filter(function ($memory) use ($query) {
            $queryLower = strtolower($query);
            $contentLower = strtolower($memory->content);

            // Special handling for questions about previous conversations
            if (
                str_contains($queryLower, 'what did i say') ||
                str_contains($queryLower, 'what did we talk') ||
                str_contains($queryLower, 'remember') ||
                str_contains($queryLower, 'before') ||
                str_contains($queryLower, 'previous')
            ) {
                return true; // Return all memories for these types of questions
            }

            // Check if query keywords appear in memory content
            $keywords = explode(' ', $queryLower);
            foreach ($keywords as $keyword) {
                if (strlen($keyword) > 2 && str_contains($contentLower, $keyword)) {
                    return true;
                }
            }

            // Check for specific memory types that are always relevant
            if (in_array($memory->type, ['preference', 'goal', 'insight'])) {
                return true;
            }

            return false;
        });

        // If no relevant memories found, return recent important ones
        if ($relevantMemories->isEmpty()) {
            $relevantMemories = $allMemories->take(5);
        }

        return $relevantMemories->map(function ($memory) {
            return [
                'type' => $memory->type,
                'key' => $memory->key,
                'content' => $memory->content,
                'importance' => $memory->importance,
                'created_at' => $memory->created_at->format('Y-m-d H:i:s')
            ];
        })->toArray();
    }

    protected function getFavoriteCategory(): ?string
    {
        return $this->user->purchases()
            ->selectRaw('ai_categorized_category, COUNT(*) as count')
            ->groupBy('ai_categorized_category')
            ->orderBy('count', 'desc')
            ->value('ai_categorized_category');
    }

    protected function parseDateRange(string $period): array
    {
        if (str_contains($period, ' to ')) {
            $dates = explode(' to ', $period);
            return [Carbon::parse($dates[0]), Carbon::parse($dates[1])];
        }

        return match ($period) {
            'week' => [now()->startOfWeek(), now()->endOfWeek()],
            'month' => [now()->startOfMonth(), now()->endOfMonth()],
            'quarter' => [now()->startOfQuarter(), now()->endOfQuarter()],
            'year' => [now()->startOfYear(), now()->endOfYear()],
            default => [now()->startOfMonth(), now()->endOfMonth()]
        };
    }

    public function autoCategorizePurchase(Purchase $purchase): string
    {
        $text = strtolower($purchase->title . ' ' . ($purchase->description ?? ''));

        $categories = FinancialCategory::active()->get();

        foreach ($categories as $category) {
            if ($category->matchesKeywords($text)) {
                return $category->name;
            }
        }

        // Handle null amount case
        $amount = $purchase->amount;
        if ($amount === null) {
            $amount = 0.0; // Default to 0 if amount is null
        }

        return $this->aiCategorize($text, $amount);
    }

    protected function aiCategorize(string $text, ?float $amount): string
    {
        // Handle null amount
        if ($amount === null) {
            $amount = 0.0;
        }

        // Simple AI categorization logic
        $keywords = [
            'food' => ['food', 'restaurant', 'meal', 'dinner', 'lunch', 'breakfast', 'snack', 'coffee', 'pizza', 'burger'],
            'transport' => ['transport', 'uber', 'grab', 'taxi', 'bus', 'train', 'gas', 'fuel', 'parking', 'car', 'bmw', 'vehicle'],
            'shopping' => ['shopping', 'mall', 'store', 'clothes', 'shoes', 'bag', 'accessories'],
            'entertainment' => ['movie', 'cinema', 'game', 'concert', 'show', 'ticket', 'netflix', 'spotify'],
            'utilities' => ['electricity', 'water', 'internet', 'phone', 'bill', 'utility'],
            'health' => ['medicine', 'doctor', 'hospital', 'pharmacy', 'medical', 'health'],
            'education' => ['book', 'course', 'school', 'education', 'training', 'workshop']
        ];

        foreach ($keywords as $category => $words) {
            foreach ($words as $word) {
                if (str_contains($text, $word)) {
                    return ucfirst($category);
                }
            }
        }

        return 'Uncategorized';
    }

    /**
     * Fallback processing when tools are not supported
     */
    protected function fallbackProcessing(string $message): string
    {
        // Check for merchant listing queries
        if (preg_match('/(?:what|show|list|tell).*(?:merchants?|stores?|shops?)/i', $message)) {
            return $this->handleMerchantListing();
        }

        // Check for specific product price queries
        if (preg_match('/(?:how much|what.*price|cost).*?(?:is|are|of|for)\s+([a-zA-Z\s]+).*?(?:at|in|from)\s+([a-zA-Z\s]+)/i', $message, $matches)) {
            $productName = trim($matches[1]);
            $merchantName = trim($matches[2]);
            return $this->handleSpecificProductQuery($productName, $merchantName);
        }

        // Check for product inquiries using a simpler approach
        $productKeywords = ['products', 'product', 'items', 'item', 'menu'];
        $hasProductKeyword = false;
        foreach ($productKeywords as $keyword) {
            if (stripos($message, $keyword) !== false) {
                $hasProductKeyword = true;
                break;
            }
        }

        if ($hasProductKeyword) {
            // Try to find a merchant name in the message
            $merchants = \App\Models\Merchant::all();
            foreach ($merchants as $merchant) {
                if (stripos($message, $merchant->name) !== false) {
                    return $this->handleProductInquiry($merchant->name);
                }
            }

            // If no exact match, try partial matches
            foreach ($merchants as $merchant) {
                $words = explode(' ', strtolower($merchant->name));
                foreach ($words as $word) {
                    if (strlen($word) > 3 && stripos($message, $word) !== false) {
                        return $this->handleProductInquiry($merchant->name);
                    }
                }
            }
        }

        // Check for specific product mentions (like "yumburger", "chickenjoy", etc.)
        $specificProducts = $this->findSpecificProductMention($message);
        if ($specificProducts) {
            return $this->handleSpecificProductQuery($specificProducts['product'], $specificProducts['merchant']);
        }

        // Check for points inquiries
        if (preg_match('/(?:how many|what|show|get).*(?:points?|balance).*(?:at|in|from|of)\s+([a-zA-Z\s]+)/i', $message, $matches)) {
            $merchantName = trim($matches[1]);
            return $this->handleMerchantPointsInquiry($merchantName);
        }

        // Check for general points inquiries
        if (preg_match('/(?:how many|what|show|get|my).*(?:points?|balance)/i', $message)) {
            return $this->handleGeneralPointsInquiry();
        }

        // Check for purchase history inquiries
        if (preg_match('/(?:purchase|buying|transaction|spending).*(?:history|record|summary)/i', $message)) {
            return $this->handlePurchaseHistoryInquiry();
        }

        // Extract purchases from message
        $purchases = $this->extractPurchasesFromMessage($message);

        if (!empty($purchases)) {
            // Actually save the purchases to the database
            $savedPurchases = [];
            foreach ($purchases as $purchase) {
                try {
                    $savedPurchase = \App\Models\Purchase::create([
                        'user_id' => $this->user->id,
                        'title' => $purchase['title'],
                        'description' => $purchase['description'] ?? null,
                        'amount' => $purchase['amount'],
                        'currency' => 'PHP',
                        'merchant_name' => $purchase['merchant_name'] ?? null,
                        'purchase_date' => $purchase['date'],
                        'ai_categorized_category' => $this->autoCategorizePurchase(new \App\Models\Purchase($purchase)),
                        'metadata' => []
                    ]);

                    $savedPurchases[] = $savedPurchase;

                    // Award points if it's a system merchant
                    if ($purchase['merchant_name']) {
                        $merchant = \App\Models\Merchant::where('name', 'like', "%{$purchase['merchant_name']}%")->first();
                        if ($merchant) {
                            $pointsService = new \App\Services\PointsService();
                            // Create a temporary transaction for points calculation
                            $transaction = new \App\Models\Transaction([
                                'user_id' => $this->user->id,
                                'merchant_id' => $merchant->id,
                                'amount' => $purchase['amount'],
                                'status' => 'completed'
                            ]);
                            $pointsService->awardPoints($transaction);
                        }
                    }
                } catch (\Exception $e) {
                    // Log error but continue with other purchases
                    Log::warning('Failed to save purchase', [
                        'user_id' => $this->user->id,
                        'purchase' => $purchase,
                        'error' => $e->getMessage()
                    ]);
                }
            }

            $purchaseList = collect($savedPurchases)->map(function ($purchase) {
                return "<div class='saved-purchase'>
                    <div class='purchase-title'>{$purchase->title}</div>
                    <div class='purchase-details'>
                        <span class='amount'>₱{$purchase->amount}</span>
                        <span class='merchant'>at {$purchase->merchant_name}</span>
                        <span class='date'>on {$purchase->purchase_date->format('M d, Y')}</span>
                    </div>
                </div>";
            })->join('');

            $totalAmount = collect($savedPurchases)->sum('amount');

            return "<div class='purchases-saved'>
                <h3>✅ Purchases Saved Successfully!</h3>
                <div class='saved-purchases-list'>{$purchaseList}</div>
                <div class='total-amount'>
                    <h4>💰 Total Amount: <span class='total'>₱" . number_format($totalAmount, 2) . "</span></h4>
                </div>
                <p class='confirmation'>Your purchases have been recorded and points have been awarded if applicable.</p>
            </div>";
        }

        // Store important information
        $this->storeImportantMemory($message);

        return "<div class='help-info'>
            <h3>🤖 Financial Assistant</h3>
            <p>I understand your message. I can help you with:</p>
            <div class='help-list'>
                <div class='help-item'>🛍️ <strong>Product inquiries:</strong> 'What products does SM have?'</div>
                <div class='help-item'>💰 <strong>Product prices:</strong> 'How much is Yumburger at Jollibee?'</div>
                <div class='help-item'>🎯 <strong>Points checking:</strong> 'How many points do I have?'</div>
                <div class='help-item'>📝 <strong>Purchase recording:</strong> 'I bought rice for ₱50 at SM today'</div>
                <div class='help-item'>📋 <strong>Purchase history:</strong> 'Show my purchase history'</div>
                <div class='help-item'>💡 <strong>Financial advice and insights</strong></div>
            </div>
        </div>";
    }

    protected function handleSpecificProductQuery(string $productName, string $merchantName): string
    {
        try {
            $merchant = \App\Models\Merchant::where('name', 'like', "%{$merchantName}%")->first();

            if (!$merchant) {
                return "<div class='error'>❌ Merchant '{$merchantName}' not found in the system.</div>";
            }

            $product = $merchant->products()
                ->whereRaw('LOWER(name) LIKE ?', ['%' . strtolower($productName) . '%'])
                ->with('pointsRules')
                ->first();

            if (!$product) {
                // Try to find similar products
                $similarProducts = $merchant->products()
                    ->whereRaw('LOWER(name) LIKE ?', ['%' . strtolower($productName) . '%'])
                    ->orWhereRaw('LOWER(name) LIKE ?', ['%' . substr(strtolower($productName), 0, 3) . '%'])
                    ->limit(3)
                    ->get();

                if ($similarProducts->isNotEmpty()) {
                    $similarList = $similarProducts->map(function ($p) {
                        $price = number_format($p->price, 2);
                        $pointsInfo = $p->pointsRules->isNotEmpty() ? " <span class='points-badge'>🎯 Earns points</span>" : "";
                        return "<div class='product-item'>• <strong>{$p->name}</strong> - <span class='price'>₱{$price}</span>{$pointsInfo}</div>";
                    })->join('');

                    return "<div class='product-not-found'>
                        <h3>🔍 Product Not Found</h3>
                        <p>I couldn't find '{$productName}' at {$merchant->name}, but here are similar products:</p>
                        <div class='similar-products'>{$similarList}</div>
                    </div>";
                }

                return "<div class='error'>❌ Product '{$productName}' not found at {$merchant->name}.</div>";
            }

            $price = number_format($product->price, 2);
            $pointsRules = $product->pointsRules;
            $pointsInfo = $pointsRules->isNotEmpty()
                ? " <span class='points-badge'>🎯 " . $this->getProductPointsInfo($product) . "</span>"
                : " <span class='no-points'>❌ No points program</span>";

            return "<div class='product-details'>
                <h3>💰 Product Price</h3>
                <div class='product-info'>
                    <div class='product-name'><strong>{$product->name}</strong></div>
                    <div class='product-price'>Price: <span class='price'>₱{$price}</span></div>
                    <div class='product-merchant'>Available at: <span class='merchant'>{$merchant->name}</span></div>
                    <div class='product-points'>{$pointsInfo}</div>
                </div>
            </div>";
        } catch (\Exception $e) {
            return "<div class='error'>❌ Failed to get product information: {$e->getMessage()}</div>";
        }
    }

    protected function findSpecificProductMention(string $message): ?array
    {
        // Common product names to look for (case insensitive)
        $productNames = [
            'yumburger',
            'chickenjoy',
            'spaghetti',
            'champ',
            'hotdog',
            'fries',
            'pie',
            'rice',
            'sandwich',
            'burger',
            'meal',
            'bucket',
            'pack'
        ];

        $merchants = \App\Models\Merchant::all();

        foreach ($productNames as $productName) {
            if (stripos($message, $productName) !== false) {
                // Try to find which merchant this product belongs to
                foreach ($merchants as $merchant) {
                    $product = $merchant->products()
                        ->whereRaw('LOWER(name) LIKE ?', ['%' . strtolower($productName) . '%'])
                        ->first();

                    if ($product) {
                        return [
                            'product' => $productName,
                            'merchant' => $merchant->name
                        ];
                    }
                }
            }
        }

        return null;
    }

    protected function handleProductInquiry(string $merchantName): string
    {
        try {
            $merchant = \App\Models\Merchant::where('name', 'like', "%{$merchantName}%")->first();

            if (!$merchant) {
                return "<div class='error'>❌ Merchant '{$merchantName}' not found in the system.</div>";
            }

            $products = $merchant->products()->with('pointsRules')->get();

            if ($products->isEmpty()) {
                return "<div class='info'>📦 No products found for {$merchant->name}.</div>";
            }

            $productList = $products->map(function ($product) {
                $price = number_format($product->price, 2);
                $pointsRules = $product->pointsRules;
                $pointsInfo = $pointsRules->isNotEmpty()
                    ? " <span class='points-badge'>🎯 " . $this->getProductPointsInfo($product) . "</span>"
                    : " <span class='no-points'>❌ No points</span>";

                return "<div class='product-item'>• <strong>{$product->name}</strong> - <span class='price'>₱{$price}</span>{$pointsInfo}</div>";
            })->join('');

            $totalProducts = $products->count();
            $totalValue = $products->sum('price');
            $productsWithPoints = $products->filter(fn($p) => $p->pointsRules->isNotEmpty())->count();

            return "<div class='merchant-products'>
                <h3>🛍️ Products at {$merchant->name}</h3>
                <div class='products-list'>{$productList}</div>
                <div class='summary'>
                    <p><strong>📊 Summary:</strong> {$totalProducts} products</p>
                    <p><strong>💰 Total Value:</strong> ₱" . number_format($totalValue, 2) . "</p>
                    <p><strong>🎯 Products with Points:</strong> {$productsWithPoints}</p>
                </div>
            </div>";
        } catch (\Exception $e) {
            return "<div class='error'>❌ Failed to get merchant products: {$e->getMessage()}</div>";
        }
    }

    protected function handleMerchantPointsInquiry(string $merchantName): string
    {
        try {
            $merchant = \App\Models\Merchant::where('name', 'like', "%{$merchantName}%")->first();

            if (!$merchant) {
                return "❌ Merchant '{$merchantName}' not found in the system.";
            }

            $pointsService = new \App\Services\PointsService();
            $pointsStatus = $pointsService->getUserMerchantPointsStatus($this->user->id, $merchant->id);

            $icon = $pointsStatus['can_earn_points'] ? '✅' : '❌';

            return "{$icon} {$merchant->name} Points Status:\n\nCurrent Points: {$pointsStatus['current_points']}\n\n{$pointsStatus['message']}";
        } catch (\Exception $e) {
            return "❌ Failed to get merchant points: {$e->getMessage()}";
        }
    }

    protected function handleGeneralPointsInquiry(): string
    {
        try {
            $pointsService = new \App\Services\PointsService();
            $pointsSummary = $pointsService->getUserPointsSummary($this->user->id);

            if (empty($pointsSummary)) {
                return "🎯 Your Points Summary:\n\nYou don't have any points yet. Start shopping at participating merchants to earn points!";
            }

            $pointsList = collect($pointsSummary)
                ->map(function ($merchant) {
                    return "• {$merchant['merchant_name']}: {$merchant['points']} points";
                })
                ->join("\n");

            $totalPoints = collect($pointsSummary)->sum('points');

            return "🎯 Your Points Summary:\n\n{$pointsList}\n\n💰 Total Points: {$totalPoints}";
        } catch (\Exception $e) {
            return "❌ Failed to get user points: {$e->getMessage()}";
        }
    }

    protected function handlePurchaseHistoryInquiry(): string
    {
        try {
            $purchases = $this->user->purchases()
                ->orderBy('purchase_date', 'desc')
                ->limit(10)
                ->get();

            if ($purchases->isEmpty()) {
                return "📋 Purchase History:\n\nNo purchase history found.";
            }

            $purchaseList = $purchases->map(function ($purchase) {
                return "• {$purchase->title}: ₱{$purchase->amount} at {$purchase->merchant_name} on {$purchase->purchase_date->format('M d, Y')}";
            })->join("\n");

            $totalSpent = $purchases->sum('amount');

            return "📋 Recent Purchase History:\n\n{$purchaseList}\n\n💰 Total Spent: ₱" . number_format($totalSpent, 2);
        } catch (\Exception $e) {
            return "❌ Failed to get purchase history: {$e->getMessage()}";
        }
    }

    /**
     * Extract purchases from message using AI tools instead of regex
     */
    protected function extractPurchasesFromMessage(string $message): array
    {
        try {
            // Get configuration
            $config = config('financial-advisor');
            $providerConfig = $config['providers'][$config['provider']];

            $prompt = "Extract purchase information from this message and return ONLY a valid JSON array. Convert all amounts to numbers.

IMPORTANT: Convert text amounts to numbers:
- '1 million pesos' = 1000000
- '5 thousand pesos' = 5000
- '2 hundred pesos' = 200
- '₱1,500' = 1500
- '500 pesos' = 500

Examples:
Input: 'I bought 5 Yumburgers for ₱225 at Jollibee today'
Output: [{\"title\":\"5 Yumburgers\",\"amount\":225,\"merchant_name\":\"Jollibee\",\"quantity\":\"5\",\"date\":\"2025-07-27\"}]

Input: 'I spent 1 million pesos on an Audi R8'
Output: [{\"title\":\"Audi R8\",\"amount\":1000000,\"merchant_name\":null,\"quantity\":\"1\",\"date\":\"2025-07-27\"}]

Input: 'I got 3 shirts for ₱1,500'
Output: [{\"title\":\"3 shirts\",\"amount\":1500,\"merchant_name\":null,\"quantity\":\"3\",\"date\":\"2025-07-27\"}]

Input: '{$message}'
Output:";

            $response = Prism::text()
                ->using($providerConfig['provider'], $config['model'])
                ->usingTemperature(0.1)
                ->withMaxTokens(500)
                ->withSystemPrompt("You are a JSON generator. Return ONLY valid JSON arrays with purchase information. ALWAYS convert text amounts to numbers. Do not include any explanations or text outside the JSON.")
                ->withPrompt($prompt)
                ->asText();

            Log::info('AI extraction response', ['response' => $response->text]);

            // Try to extract JSON from the response
            $jsonData = json_decode($response->text, true);
            if (is_array($jsonData)) {
                return $jsonData;
            }

            // Fallback: try to find JSON in the response
            if (preg_match('/\[.*\]/s', $response->text, $matches)) {
                $jsonData = json_decode($matches[0], true);
                if (is_array($jsonData)) {
                    return $jsonData;
                }
            }

            return [];
        } catch (\Exception $e) {
            Log::warning('AI purchase extraction failed, falling back to basic parsing', [
                'message' => $message,
                'error' => $e->getMessage()
            ]);

            // Fallback to basic extraction for simple cases
            return $this->basicExtractPurchases($message);
        }
    }

    /**
     * Simple AI extraction without tools
     */
    protected function simpleAIExtraction(string $message): array
    {
        try {
            $config = config('financial-advisor');
            $providerConfig = $config['providers'][$config['provider']];

            $prompt = "Extract purchase information from this message and return ONLY a JSON array. Convert all amounts to numbers.

                Examples:
                Input: 'I bought 5 Yumburgers for ₱225 at Jollibee today'
                Output: [{\"title\":\"5 Yumburgers\",\"amount\":225,\"merchant_name\":\"Jollibee\",\"quantity\":\"5\",\"date\":\"2025-07-27\"}]

                Input: 'I spent 1 million pesos on an Audi R8'
                Output: [{\"title\":\"Audi R8\",\"amount\":1000000,\"merchant_name\":null,\"quantity\":\"1\",\"date\":\"2025-07-27\"}]

                Input: '{$message}'
                Output:";

            $response = Prism::text()
                ->using($providerConfig['provider'], $config['model'])
                ->usingTemperature(0.1)
                ->withMaxTokens(500)
                ->withSystemPrompt("You are a JSON generator. Return ONLY valid JSON arrays with purchase information. Convert text amounts to numbers.")
                ->withPrompt($prompt)
                ->asText();

            $jsonData = json_decode($response->text, true);
            if (is_array($jsonData)) {
                return $jsonData;
            }

            return [];
        } catch (\Exception $e) {
            Log::warning('Simple AI extraction failed', ['error' => $e->getMessage()]);
            return [];
        }
    }

    /**
     * Basic fallback extraction for simple cases
     */
    protected function basicExtractPurchases(string $message): array
    {
        $purchases = [];

        // Very simple patterns as fallback
        if (preg_match('/I\s+(?:bought|purchased|got)\s+(.+?)\s+(?:for|at)\s+(?:₱|PHP|pesos?)?\s*([\d,]+(?:\.\d{2})?)/i', $message, $matches)) {
            $purchases[] = [
                'title' => trim($matches[1]),
                'amount' => (float) str_replace(',', '', $matches[2]),
                'merchant_name' => null,
                'quantity' => '1',
                'date' => now()->format('Y-m-d'),
                'description' => null
            ];
        }

        return $purchases;
    }

    /**
     * Store important information in memory
     */
    protected function storeImportantMemory(string $message): void
    {
        // Look for important keywords
        $importantKeywords = [
            'goal',
            'save',
            'budget',
            'investment',
            'debt',
            'loan',
            'emergency',
            'retirement',
            'vacation',
            'house',
            'car'
        ];

        foreach ($importantKeywords as $keyword) {
            if (stripos($message, $keyword) !== false) {
                try {
                    UserMemory::updateOrCreate(
                        [
                            'user_id' => $this->user->id,
                            'type' => 'conversation',
                            'key' => $keyword
                        ],
                        [
                            'content' => "User mentioned {$keyword} in conversation: {$message}",
                            'importance' => 7,
                            'last_accessed_at' => now()
                        ]
                    );
                } catch (\Exception $e) {
                    Log::warning('Failed to store memory', ['error' => $e->getMessage()]);
                }
                break;
            }
        }
    }

    /**
     * Store conversation memory automatically after successful AI response
     */
    protected function storeConversationMemory(string $userMessage, array $aiResponse): void
    {
        try {
            // Extract key information from the conversation
            $conversationSummary = $this->extractConversationSummary($userMessage, $aiResponse);

            if (!empty($conversationSummary)) {
                // Store the conversation memory
                UserMemory::updateOrCreate(
                    [
                        'user_id' => $this->user->id,
                        'type' => 'conversation',
                        'key' => 'conversation_' . now()->format('Y-m-d_H-i-s')
                    ],
                    [
                        'content' => $conversationSummary,
                        'importance' => $this->calculateConversationImportance($userMessage, $aiResponse),
                        'metadata' => [
                            'user_message' => $userMessage,
                            'ai_response' => $aiResponse['message'] ?? '',
                            'purchases_added' => count($aiResponse['purchases_added'] ?? []),
                            'has_advice' => !empty($aiResponse['advice']),
                            'has_insights' => !empty($aiResponse['insights']),
                            'has_recommendations' => !empty($aiResponse['recommendations'])
                        ],
                        'last_accessed_at' => now()
                    ]
                );
            }

            // Store specific insights if any
            if (!empty($aiResponse['insights'])) {
                foreach ($aiResponse['insights'] as $index => $insight) {
                    UserMemory::updateOrCreate(
                        [
                            'user_id' => $this->user->id,
                            'type' => 'insight',
                            'key' => 'insight_' . now()->format('Y-m-d_H-i-s') . '_' . $index
                        ],
                        [
                            'content' => $insight,
                            'importance' => 6,
                            'metadata' => [
                                'source_message' => $userMessage,
                                'context' => 'AI-generated insight'
                            ],
                            'last_accessed_at' => now()
                        ]
                    );
                }
            }

            // Store recommendations if any
            if (!empty($aiResponse['recommendations'])) {
                foreach ($aiResponse['recommendations'] as $index => $recommendation) {
                    UserMemory::updateOrCreate(
                        [
                            'user_id' => $this->user->id,
                            'type' => 'recommendation',
                            'key' => 'recommendation_' . now()->format('Y-m-d_H-i-s') . '_' . $index
                        ],
                        [
                            'content' => $recommendation,
                            'importance' => 7,
                            'metadata' => [
                                'source_message' => $userMessage,
                                'context' => 'AI-generated recommendation'
                            ],
                            'last_accessed_at' => now()
                        ]
                    );
                }
            }

            // Store user preferences and goals mentioned
            $this->storeUserPreferences($userMessage);
        } catch (\Exception $e) {
            Log::warning('Failed to store conversation memory', [
                'user_id' => $this->user->id,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Extract a summary of the conversation for memory storage
     */
    protected function extractConversationSummary(string $userMessage, array $aiResponse): string
    {
        $summary = [];

        // Add user's message context
        $summary[] = "User asked: " . substr($userMessage, 0, 200) . (strlen($userMessage) > 200 ? '...' : '');

        // Add AI response context
        if (!empty($aiResponse['message'])) {
            $summary[] = "AI responded: " . substr($aiResponse['message'], 0, 200) . (strlen($aiResponse['message']) > 200 ? '...' : '');
        }

        // Add purchases context
        if (!empty($aiResponse['purchases_added'])) {
            $summary[] = "Purchases recorded: " . count($aiResponse['purchases_added']) . " items";
        }

        // Add advice context
        if (!empty($aiResponse['advice'])) {
            $summary[] = "Financial advice provided";
        }

        return implode(' | ', $summary);
    }

    /**
     * Calculate importance level for conversation memory
     */
    protected function calculateConversationImportance(string $userMessage, array $aiResponse): int
    {
        $importance = 3; // Base importance

        // Increase importance based on content
        if (!empty($aiResponse['purchases_added'])) {
            $importance += 2; // Purchases are important
        }

        if (!empty($aiResponse['advice'])) {
            $importance += 2; // Financial advice is important
        }

        if (!empty($aiResponse['insights'])) {
            $importance += 1; // Insights add value
        }

        if (!empty($aiResponse['recommendations'])) {
            $importance += 2; // Recommendations are important
        }

        // Check for important keywords in user message
        $importantKeywords = ['goal', 'save', 'budget', 'investment', 'debt', 'loan', 'emergency', 'retirement'];
        foreach ($importantKeywords as $keyword) {
            if (stripos($userMessage, $keyword) !== false) {
                $importance += 3;
                break;
            }
        }

        return min($importance, 10); // Cap at 10
    }

    /**
     * Store user preferences and goals mentioned in conversation
     */
    protected function storeUserPreferences(string $userMessage): void
    {
        $preferences = [];

        // Look for preference indicators
        if (stripos($userMessage, 'prefer') !== false || stripos($userMessage, 'like') !== false || stripos($userMessage, 'favorite') !== false) {
            $preferences[] = 'preferences_mentioned';
        }

        if (stripos($userMessage, 'goal') !== false || stripos($userMessage, 'target') !== false || stripos($userMessage, 'aim') !== false) {
            $preferences[] = 'goals_mentioned';
        }

        if (stripos($userMessage, 'budget') !== false || stripos($userMessage, 'spending') !== false || stripos($userMessage, 'save') !== false) {
            $preferences[] = 'budget_concerns';
        }

        foreach ($preferences as $preference) {
            UserMemory::updateOrCreate(
                [
                    'user_id' => $this->user->id,
                    'type' => 'preference',
                    'key' => $preference
                ],
                [
                    'content' => "User mentioned {$preference} in conversation: {$userMessage}",
                    'importance' => 6,
                    'metadata' => [
                        'source_message' => $userMessage,
                        'detected_at' => now()->toISOString()
                    ],
                    'last_accessed_at' => now()
                ]
            );
        }
    }

    /**
     * Detect if the user's message is asking an important or insightful question
     */
    protected function isImportantQuestion(string $message): bool
    {
        $importantKeywords = [
            'how',
            'why',
            'what',
            'explain',
            'help',
            'advice',
            'recommend',
            'budget',
            'save',
            'invest',
            'debt',
            'loan',
            'emergency',
            'retirement',
            'goal',
            'target',
            'plan',
            'strategy',
            'improve',
            'better',
            'optimize',
            'financial health',
            'financial situation',
            'spending habits',
            'money management',
            'income',
            'expenses',
            'cash flow',
            'wealth',
            'financial freedom'
        ];

        $messageLower = strtolower($message);

        foreach ($importantKeywords as $keyword) {
            if (str_contains($messageLower, $keyword)) {
                return true;
            }
        }

        // Check for question patterns
        if (preg_match('/\b(how|why|what|when|where|which|who)\b.*\?/i', $message)) {
            return true;
        }

        // Check for requests for help or advice
        if (preg_match('/\b(help|advice|recommend|suggest|guide)\b/i', $message)) {
            return true;
        }

        return false;
    }

    /**
     * Get enhanced context for important questions
     */
    protected function getEnhancedContextForImportantQuestions(string $message): string
    {
        $context = "\n🎯 IMPORTANT QUESTION DETECTED: This appears to be a significant financial question that requires detailed explanation.\n\n";

        $context .= "Please provide a comprehensive, educational response that includes:\n";
        $context .= "1. Clear explanation of the financial concepts involved\n";
        $context .= "2. Detailed reasoning behind your advice and recommendations\n";
        $context .= "3. Step-by-step guidance on how to implement suggestions\n";
        $context .= "4. Educational content that helps the user understand the 'why' and 'how'\n";
        $context .= "5. Practical examples and analogies where helpful\n";
        $context .= "6. Long-term implications and benefits of following the advice\n\n";

        $context .= "Make this response educational and empowering, not just informational.\n";

        return $context;
    }

    /**
     * Check if this is likely the first message in a conversation
     */
    protected function isFirstMessage(string $message): bool
    {
        $firstMessageIndicators = [
            'hello',
            'hi',
            'hey',
            'good morning',
            'good afternoon',
            'good evening',
            'start',
            'begin',
            'new',
            'first time',
            'help me',
            'can you help',
            'what can you do',
            'how does this work',
            'introduce'
        ];

        $messageLower = strtolower($message);

        foreach ($firstMessageIndicators as $indicator) {
            if (str_contains($messageLower, $indicator)) {
                return true;
            }
        }

        // Check if user has very few or no previous memories
        $memoryCount = $this->user->memories()->count();
        if ($memoryCount <= 2) {
            return true;
        }

        return false;
    }

    /**
     * Get conversation context based on whether it's first message or not
     */
    protected function getConversationContext(string $message): string
    {
        if ($this->isFirstMessage($message)) {
            return "\n💬 FIRST MESSAGE: This appears to be the start of a conversation. You can introduce yourself briefly and warmly welcome the user.\n";
        } else {
            return "\n💬 CONTINUING CONVERSATION: This is an ongoing conversation. Be natural and conversational, reference previous discussions, and avoid formal introductions.\n";
        }
    }

    /**
     * Generate structured insights using Prism's structured output
     */
    protected function generateStructuredInsights(string $message, string $toolResults, array $providerConfig, array $config): array
    {
        try {
            // Create the structured schema for financial advisor response
            $schema = new ObjectSchema(
                name: 'financial_advisor_response',
                description: 'A structured financial advisor response with insights and recommendations',
                properties: [
                    new StringSchema('message', 'Natural, conversational main response'),
                    new StringSchema('advice', 'Comprehensive financial advice with explanations'),
                    new ArraySchema('insights', 'Array of detailed insights with explanations', new StringSchema('insight', 'Individual insight')),
                    new ArraySchema('recommendations', 'Array of specific, actionable recommendations', new StringSchema('recommendation', 'Individual recommendation')),
                    new ArraySchema('purchases_added', 'Array of purchases that were added', new StringSchema('purchase', 'Individual purchase'))
                ],
                requiredFields: ['message', 'advice', 'insights', 'recommendations', 'purchases_added']
            );

            // Build the context for structured generation
            $context = $this->buildInsightsContext($message, $toolResults);

            // Generate structured response
            $response = Prism::structured()
                ->using($providerConfig['provider'], $config['model'])
                ->usingTemperature($config['temperature'])
                ->withMaxTokens($config['max_tokens'])
                ->withSystemPrompt($this->getInsightsSystemPrompt())
                ->withPrompt($context)
                ->withSchema($schema)
                ->asStructured();

            // Log successful structured generation
            Log::info('Structured insights generated successfully', [
                'user_id' => $this->user->id,
                'finish_reason' => $response->finishReason->name ?? 'unknown',
                'token_usage' => $response->usage ?? null
            ]);

            // Return the structured data
            return $response->structured ?? [
                'message' => 'I understand your question. Let me help you with that.',
                'advice' => '',
                'insights' => [],
                'recommendations' => [],
                'purchases_added' => []
            ];
        } catch (\Exception $e) {
            Log::warning('Structured insights generation failed, using fallback', [
                'user_id' => $this->user->id,
                'error' => $e->getMessage()
            ]);

            // Fallback to basic response
            return [
                'message' => 'I understand your question. Let me help you with that.',
                'advice' => '',
                'insights' => [],
                'recommendations' => [],
                'purchases_added' => []
            ];
        }
    }

    /**
     * Generate fallback response when AI fails
     */
    protected function generateFallbackResponse(string $message): array
    {
        // Extract and save purchases from message
        $purchases = $this->extractPurchasesFromMessage($message);
        $savedPurchases = [];

        if (!empty($purchases)) {
            foreach ($purchases as $purchase) {
                try {
                    $savedPurchase = \App\Models\Purchase::create([
                        'user_id' => $this->user->id,
                        'title' => $purchase['title'],
                        'description' => $purchase['description'] ?? null,
                        'amount' => $purchase['amount'],
                        'currency' => 'PHP',
                        'merchant_name' => $purchase['merchant_name'] ?? null,
                        'purchase_date' => $purchase['date'],
                        'ai_categorized_category' => $this->autoCategorizePurchase(new \App\Models\Purchase($purchase)),
                        'metadata' => []
                    ]);

                    $savedPurchases[] = $savedPurchase;

                    // Award points if it's a system merchant
                    if ($purchase['merchant_name']) {
                        $merchant = \App\Models\Merchant::where('name', 'like', "%{$purchase['merchant_name']}%")->first();
                        if ($merchant) {
                            $pointsService = new \App\Services\PointsService();
                            // Create a temporary transaction for points calculation
                            $transaction = new \App\Models\Transaction([
                                'user_id' => $this->user->id,
                                'merchant_id' => $merchant->id,
                                'amount' => $purchase['amount'],
                                'status' => 'completed'
                            ]);
                            $pointsService->awardPoints($transaction);
                        }
                    }
                } catch (\Exception $e) {
                    Log::warning('Failed to save purchase in fallback response', [
                        'user_id' => $this->user->id,
                        'purchase' => $purchase,
                        'error' => $e->getMessage()
                    ]);
                }
            }
        }

        // Store important information
        $this->storeImportantMemory($message);

        // Generate response based on saved purchases
        if (!empty($savedPurchases)) {
            $totalAmount = collect($savedPurchases)->sum('amount');
            $purchaseCount = count($savedPurchases);

            $message = "Great! I've recorded " . $purchaseCount . " purchase(s) for you today, totaling ₱" . number_format($totalAmount, 2) . ". ";

            if ($totalAmount > 10000) {
                $message .= "That's quite a significant amount - how are you feeling about your spending today?";
            } else {
                $message .= "How's your day going?";
            }

            $purchasesAdded = collect($savedPurchases)->map(function ($purchase) {
                return [
                    'title' => $purchase->title,
                    'amount' => number_format($purchase->amount, 2),
                    'category' => $purchase->ai_categorized_category,
                    'date' => $purchase->purchase_date->format('Y-m-d')
                ];
            })->toArray();

            return [
                'message' => $message,
                'purchases_added' => $purchasesAdded,
                'advice' => $this->generateBasicAdvice($savedPurchases),
                'insights' => $this->generateBasicInsights($savedPurchases),
                'recommendations' => $this->generateBasicRecommendations($savedPurchases),
                'summary' => $this->getCurrentSummary()
            ];
        }

        // No purchases found, return generic response
        return [
            'message' => "Thank you for your message. I'm here to help with your financial questions and track your spending.",
            'purchases_added' => [],
            'advice' => "Consider tracking your daily expenses to better understand your spending patterns.",
            'insights' => ['You can ask me about products, prices, points, and purchase history.'],
            'recommendations' => [
                'Set up a monthly budget to track your spending',
                'Consider creating an emergency fund',
                'Review your spending patterns regularly'
            ],
            'summary' => $this->getCurrentSummary()
        ];
    }

    /**
     * Generate basic message
     */
    protected function generateBasicMessage(string $message, array $purchases): string
    {
        if (!empty($purchases)) {
            $total = array_sum(array_column($purchases, 'amount'));
            $count = count($purchases);

            if ($count === 1) {
                $purchase = $purchases[0];
                return "Got it! I've recorded your {$purchase['title']} for ₱{$total}. That's now part of your financial tracking.";
            } else {
                return "Perfect! I've recorded your {$count} purchases totaling ₱{$total}. These are now tracked in your financial profile.";
            }
        }

        // For general conversation without purchases
        if (stripos($message, 'hello') !== false || stripos($message, 'hi') !== false) {
            return "Hey! How's your day going? I'm here to help with your finances whenever you need me.";
        }

        if (stripos($message, 'thank') !== false) {
            return "You're welcome! Happy to help with your financial tracking.";
        }

        return "I'm here to help with your financial questions and track your spending. What's on your mind?";
    }

    /**
     * Generate basic advice
     */
    protected function generateBasicAdvice(array $purchases): string
    {
        if (empty($purchases)) {
            return "Tracking your expenses is a great habit! It helps you see where your money goes and identify areas to save.";
        }

        $total = array_sum(array_column($purchases, 'amount'));
        $count = count($purchases);

        if ($total > 50000) {
            return "That's a substantial amount! For large purchases like this, it's good to have savings set aside. How does this fit into your overall financial plan?";
        } elseif ($total > 10000) {
            return "This is a significant purchase. It's smart to track these - they can really impact your monthly budget. Are you comfortable with this level of spending?";
        } elseif ($total > 1000) {
            return "This is a moderate purchase. It's good you're tracking it - these add up quickly! How does this compare to your usual spending?";
        } else {
            return "Even small purchases matter when you're tracking finances. These daily expenses can really add up over time. Great job staying on top of it!";
        }
    }

    /**
     * Generate basic insights
     */
    protected function generateBasicInsights(array $purchases): array
    {
        $insights = [];

        if (!empty($purchases)) {
            $total = array_sum(array_column($purchases, 'amount'));
            $count = count($purchases);

            if ($count === 1) {
                $purchase = $purchases[0];
                $insights[] = "You spent ₱{$total} on {$purchase['title']}.";
            } else {
                $insights[] = "You made {$count} purchases totaling ₱{$total}.";
            }

            // Add contextual insights based on amount
            if ($total > 50000) {
                $insights[] = "This represents a major financial decision.";
            } elseif ($total > 10000) {
                $insights[] = "This is a significant portion of most monthly budgets.";
            }
        }

        $userStats = $this->getCurrentSummary();
        if ($userStats['total_spent'] > 0) {
            $average = $userStats['purchase_count'] > 0 ? $userStats['total_spent'] / $userStats['purchase_count'] : 0;
            $insights[] = "Your average purchase is ₱" . number_format($average, 2) . " across {$userStats['purchase_count']} transactions.";
        }

        return $insights;
    }

    /**
     * Generate basic recommendations
     */
    protected function generateBasicRecommendations(array $purchases): array
    {
        $recommendations = [];

        if (!empty($purchases)) {
            $total = array_sum(array_column($purchases, 'amount'));

            if ($total > 50000) {
                $recommendations[] = "Consider setting aside a portion of your income for major purchases";
                $recommendations[] = "Review your emergency fund to ensure it covers 3-6 months of expenses";
            } elseif ($total > 10000) {
                $recommendations[] = "Track these larger purchases to ensure they fit your monthly budget";
                $recommendations[] = "Consider if this purchase aligns with your financial goals";
            } else {
                $recommendations[] = "Keep tracking daily expenses - they add up quickly";
                $recommendations[] = "Consider setting spending limits for different categories";
            }
        } else {
            $recommendations[] = "Set up a monthly budget to track your spending";
            $recommendations[] = "Consider creating an emergency fund";
            $recommendations[] = "Review your spending patterns regularly";
        }

        return $recommendations;
    }

    /**
     * Handle merchant listing queries
     */
    protected function handleMerchantListing(): string
    {
        try {
            $merchants = $this->getAvailableMerchants();

            if (empty($merchants)) {
                return "📋 No merchants found in the system.";
            }

            $merchantList = collect($merchants)->map(function ($merchant) {
                $description = $merchant['description'] ? " - {$merchant['description']}" : '';
                return "• {$merchant['name']}{$description}";
            })->join("\n");

            $totalMerchants = count($merchants);

            return "🏪 Available Merchants ({$totalMerchants}):\n\n{$merchantList}\n\n💡 Tip: Ask about products at any merchant to see what's available!";
        } catch (\Exception $e) {
            return "Failed to list merchants: {$e->getMessage()}";
        }
    }
}
