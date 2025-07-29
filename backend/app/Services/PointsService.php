<?php

namespace App\Services;

use App\Models\Transaction;
use App\Models\PointsRule;
use App\Models\UserMerchantPoints;
use App\Enums\PointsRuleType;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Collection;
use Carbon\Carbon;

class PointsService
{
    public function calculatePoints(Transaction $transaction): int
    {
        // Ensure items relationship is loaded before processing
        if (!$transaction->relationLoaded('items')) {
            $transaction->load('items.product.pointsRules');
        }

        $rules = $this->getApplicableRules($transaction);
        $points = 0;

        foreach ($rules as $rule) {
            $points += $this->calculatePointsForRule($rule, $transaction);
        }

        return $points;
    }

    /**
     * Award points to user for a specific merchant
     */
    public function awardPoints(Transaction $transaction): void
    {
        if (!$transaction->user) {
            Log::warning('Cannot award points: transaction has no user', [
                'transaction_id' => $transaction->id
            ]);
            return;
        }

        $points = $this->calculatePoints($transaction);

        if ($points > 0) {
            $this->addPointsToUser($transaction->user_id, $transaction->merchant_id, $points);

            // Update transaction with awarded points
            $transaction->update(['awarded_points' => $points]);

            Log::info('Points awarded successfully', [
                'user_id' => $transaction->user_id,
                'merchant_id' => $transaction->merchant_id,
                'transaction_id' => $transaction->id,
                'points_awarded' => $points,
                'transaction_amount' => $transaction->amount
            ]);
        }
    }

    /**
     * Add points to user for a specific merchant
     */
    public function addPointsToUser(int $userId, int $merchantId, int $points): void
    {
        $userMerchantPoints = UserMerchantPoints::firstOrCreate(
            [
                'user_id' => $userId,
                'merchant_id' => $merchantId
            ],
            [
                'points' => 0,
                'total_earned' => 0,
                'total_spent' => 0
            ]
        );

        $userMerchantPoints->addPoints($points);

        Log::info('Points added to user merchant points', [
            'user_id' => $userId,
            'merchant_id' => $merchantId,
            'points_added' => $points,
            'new_balance' => $userMerchantPoints->points
        ]);
    }

    /**
     * Spend points from user for a specific merchant
     */
    public function spendPointsFromUser(int $userId, int $merchantId, int $points): bool
    {
        $userMerchantPoints = UserMerchantPoints::where('user_id', $userId)
            ->where('merchant_id', $merchantId)
            ->first();

        if (!$userMerchantPoints) {
            Log::warning('Cannot spend points: no points record found', [
                'user_id' => $userId,
                'merchant_id' => $merchantId,
                'points_requested' => $points
            ]);
            return false;
        }

        $success = $userMerchantPoints->spendPoints($points);

        if ($success) {
            Log::info('Points spent from user merchant points', [
                'user_id' => $userId,
                'merchant_id' => $merchantId,
                'points_spent' => $points,
                'new_balance' => $userMerchantPoints->points
            ]);
        } else {
            Log::warning('Failed to spend points: insufficient balance', [
                'user_id' => $userId,
                'merchant_id' => $merchantId,
                'points_requested' => $points,
                'available_balance' => $userMerchantPoints->points
            ]);
        }

        return $success;
    }

    /**
     * Get user's points for a specific merchant
     */
    public function getUserMerchantPoints(int $userId, int $merchantId): int
    {
        $userMerchantPoints = UserMerchantPoints::where('user_id', $userId)
            ->where('merchant_id', $merchantId)
            ->first();

        return $userMerchantPoints ? $userMerchantPoints->points : 0;
    }

    /**
     * Get user's points summary for all merchants
     */
    public function getUserPointsSummary(int $userId): array
    {
        return UserMerchantPoints::where('user_id', $userId)
            ->with('merchant')
            ->get()
            ->map(function ($userMerchantPoints) {
                return [
                    'merchant_name' => $userMerchantPoints->merchant->name,
                    'points' => $userMerchantPoints->points ?? 0,
                    'total_earned' => $userMerchantPoints->total_earned ?? $userMerchantPoints->points ?? 0,
                    'total_spent' => $userMerchantPoints->total_spent ?? 0,
                    'last_earned' => $userMerchantPoints->last_earned_at,
                    'last_spent' => $userMerchantPoints->last_spent_at,
                ];
            })
            ->toArray();
    }

    /**
     * Check if user can earn points at a specific merchant
     */
    public function canEarnPointsAtMerchant(int $userId, int $merchantId): bool
    {
        $merchant = \App\Models\Merchant::find($merchantId);
        if (!$merchant) {
            return false;
        }

        // Check if merchant has any points rules
        $hasRules = $merchant->pointsRules()->exists();

        // Check if merchant's products have points rules
        $hasProductRules = $merchant->products()
            ->whereHas('pointsRules')
            ->exists();

        return $hasRules || $hasProductRules;
    }

    /**
     * Get merchant's points earning information
     */
    public function getMerchantPointsInfo(int $merchantId): array
    {
        $merchant = \App\Models\Merchant::find($merchantId);
        if (!$merchant) {
            return [
                'can_earn_points' => false,
                'rules' => [],
                'message' => 'Merchant not found'
            ];
        }

        $merchantRules = $merchant->pointsRules()->get();
        $productRules = $merchant->products()
            ->with('pointsRules')
            ->get()
            ->flatMap(function ($product) {
                return $product->pointsRules;
            });

        $allRules = $merchantRules->concat($productRules)->unique('id');

        return [
            'can_earn_points' => $allRules->isNotEmpty(),
            'rules' => $allRules->map(function ($rule) {
                return [
                    'type' => $rule->type->value,
                    'description' => $this->getRuleDescription($rule),
                    'parameters' => $rule->parameters
                ];
            })->toArray(),
            'message' => $allRules->isNotEmpty()
                ? "Yes! You can earn points at {$merchant->name}!"
                : "Sorry, {$merchant->name} doesn't have a points program yet."
        ];
    }

    /**
     * Get user's points status for a specific merchant
     */
    public function getUserMerchantPointsStatus(int $userId, int $merchantId): array
    {
        $merchant = \App\Models\Merchant::find($merchantId);

        if (!$merchant) {
            return [
                'can_earn_points' => false,
                'current_points' => 0,
                'message' => 'Merchant not found'
            ];
        }

        $userMerchantPoints = UserMerchantPoints::where('user_id', $userId)
            ->where('merchant_id', $merchantId)
            ->first();

        $currentPoints = $userMerchantPoints ? $userMerchantPoints->points : 0;
        $canEarnPoints = $this->canEarnPointsAtMerchant($userId, $merchantId);

        return [
            'can_earn_points' => $canEarnPoints,
            'current_points' => $currentPoints,
            'merchant_name' => $merchant->name,
            'message' => $canEarnPoints
                ? "Yes! You can earn points at {$merchant->name}! Currently you have {$currentPoints} points at this merchant."
                : "Sorry, {$merchant->name} doesn't have a points program yet."
        ];
    }

    public function getApplicableRules(Transaction $transaction): Collection
    {
        // Ensure items and their relationships are loaded
        if (!$transaction->relationLoaded('items')) {
            $transaction->load('items.product.pointsRules');
        }

        $merchantRules = $transaction->merchant->pointsRules()->get();
        $productRules = $transaction->items->flatMap(function ($item) {
            return $item->product->pointsRules ?? collect();
        });
        $globalRules = PointsRule::whereNull('associated_entity_id')->get();

        // Merge all rules and ensure uniqueness by ID
        return collect([])
            ->concat($merchantRules)
            ->concat($productRules)
            ->concat($globalRules)
            ->unique('id')
            ->filter(fn($rule) => $this->checkConditions($rule))
            ->sortBy('priority');
    }

    public function calculatePointsForRule(PointsRule $rule, Transaction $transaction): int
    {
        if (!$transaction->relationLoaded('items')) {
            $transaction->load('items');
        }

        $amount = $transaction->amount;
        $quantity = $transaction->items->sum('quantity');

        // Log for debugging
        Log::debug('Calculating points for rule', [
            'rule_type' => $rule->type->value,
            'amount' => $amount,
            'quantity' => $quantity,
            'parameters' => $rule->parameters
        ]);

        switch ($rule->type->value) {
            case PointsRuleType::Fixed->value:
                return (int) ($rule->parameters['points'] ?? 0);

            case PointsRuleType::Dynamic->value:
                $div = $rule->parameters['divisor'] ?? 1;
                $mul = $rule->parameters['multiplier'] ?? 1;
                return (int) floor($amount / $div) * $mul;

            case PointsRuleType::Combo->value:
                $div = $rule->parameters['divisor'] ?? 1;
                $amtMul = $rule->parameters['amount_multiplier'] ?? 1;
                $qtyMul = $rule->parameters['quantity_multiplier'] ?? 1;

                $amountPoints = floor($amount / $div) * $amtMul;
                $quantityPoints = $quantity * $qtyMul;
                return (int) ($amountPoints + $quantityPoints);

            case PointsRuleType::Threshold->value:
                $min = $rule->conditions['min_amount'] ?? 0;
                return $amount >= $min ? (int) ($rule->parameters['points'] ?? 0) : 0;

            case PointsRuleType::FirstPurchase->value:
                // Skip first purchase rules for temporary transactions (no user)
                if (!$transaction->user) {
                    return 0;
                }
                $count = $transaction->user->transactions()
                    ->where('merchant_id', $transaction->merchant_id)
                    ->count();
                return $count === 1 ? (int) ($rule->parameters['points'] ?? 0) : 0;

            case PointsRuleType::LimitedTime->value:
                // Already filtered by checkConditions
                return (int) ($rule->parameters['points'] ?? 0);

            case PointsRuleType::NoPoints->value:
                return 0;

            case PointsRuleType::CustomFormula->value:
                return $this->evaluateCustomFormula($rule->parameters['formula'] ?? '0', $transaction);

            default:
                return 0;
        }
    }

    private function checkConditions(PointsRule $rule): bool
    {
        if ($rule->type->value === PointsRuleType::LimitedTime->value) {
            $now = Carbon::now();
            $conds = is_array($rule->conditions)
                ? $rule->conditions
                : json_decode($rule->conditions ?? '[]', true);

            $start = isset($conds['start_date']) ? Carbon::parse($conds['start_date']) : null;
            $end = isset($conds['end_date']) ? Carbon::parse($conds['end_date']) : null;

            if ($start && $end) {
                $isValid = $now->gte($start) && $now->lte($end);
                Log::debug('Limited time rule check', [
                    'now' => $now->toDateTimeString(),
                    'start' => $start ? $start->toDateTimeString() : null,
                    'end' => $end ? $end->toDateTimeString() : null,
                    'isValid' => $isValid
                ]);
                return $isValid;
            }
            return false;
        }
        return true;
    }

    private function evaluateCustomFormula(string $formula, Transaction $transaction): int
    {
        $variables = [
            'total' => $transaction->amount,
            'quantity' => $transaction->items->sum('quantity'),
        ];

        Log::debug('Evaluating custom formula', [
            'formula' => $formula,
            'variables' => $variables
        ]);

        try {
            $parser = app(\App\Services\FormulaParser::class);
            $result = $parser->evaluate($formula, $variables);
            Log::debug('Custom formula result', ['result' => $result]);
            return is_numeric($result) ? (int) $result : 0;
        } catch (\Throwable $e) {
            Log::error('Custom formula evaluation error: ' . $e->getMessage(), [
                'formula' => $formula,
                'variables' => $variables
            ]);
            return 0;
        }
    }

    /**
     * Get a human-readable description of a points rule
     */
    private function getRuleDescription(PointsRule $rule): string
    {
        switch ($rule->type->value) {
            case PointsRuleType::Fixed->value:
                $points = $rule->parameters['points'] ?? 0;
                return "Earn {$points} points per transaction";

            case PointsRuleType::Dynamic->value:
                $div = $rule->parameters['divisor'] ?? 1;
                $mul = $rule->parameters['multiplier'] ?? 1;
                return "Earn " . ($mul / $div) . " points per peso spent";

            case PointsRuleType::Combo->value:
                $div = $rule->parameters['divisor'] ?? 1;
                $amtMul = $rule->parameters['amount_multiplier'] ?? 1;
                $qtyMul = $rule->parameters['quantity_multiplier'] ?? 1;
                return "Earn " . ($amtMul / $div) . " points per peso + {$qtyMul} points per item";

            case PointsRuleType::Threshold->value:
                $min = $rule->conditions['min_amount'] ?? 0;
                $points = $rule->parameters['points'] ?? 0;
                return "Earn {$points} points when spending ₱{$min} or more";

            case PointsRuleType::FirstPurchase->value:
                $points = $rule->parameters['points'] ?? 0;
                return "Earn {$points} bonus points on your first purchase";

            case PointsRuleType::LimitedTime->value:
                $points = $rule->parameters['points'] ?? 0;
                $startDate = $rule->conditions['start_date'] ?? 'now';
                $endDate = $rule->conditions['end_date'] ?? 'ongoing';
                return "Limited time: Earn {$points} points (valid from {$startDate} to {$endDate})";

            case PointsRuleType::CustomFormula->value:
                $formula = $rule->parameters['formula'] ?? 'amount * 0.1';
                return "Custom formula: {$formula}";

            default:
                return "Special points rule";
        }
    }
}
