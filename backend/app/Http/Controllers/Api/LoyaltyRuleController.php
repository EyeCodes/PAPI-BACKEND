<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use App\Models\LoyaltyProgramRule;
use App\Models\LoyaltyProgram;
use App\Models\User;
use App\Http\Controllers\Controller;
use App\Http\Requests\LoyaltyRuleRequest;
use App\Http\Requests\CalculateAndAwardRequest;

class LoyaltyRuleController extends Controller
{
    public function index(Request $request)
    {
        $user = Auth::user();

        // if (!$user) {
        //     return response()->json(['error' => 'Unauthorized'], 401);
        // }

        $rules = LoyaltyProgramRule::with('loyaltyProgram')
            ->whereHas('loyaltyProgram', function ($query) use ($user) {
                $query->where('company_id', $user->company_id);
            })
            ->when($request->program_id, function ($query, $programId) {
                $query->where('loyalty_program_id', $programId);
            })
            ->when($request->rule_type, function ($query, $ruleType) {
                $query->where('rule_type', $ruleType);
            })
            ->when($request->is_active !== null, function ($query) use ($request) {
                $query->where('is_active', $request->boolean('is_active'));
            })
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function ($rule) {
                $rule->is_currently_active = $rule->is_active;
                // $rule->description = $rule->description;
                return $rule;
            });

        return response()->json([
            'rules' => $rules,
            'total_rules' => $rules->count(),
            'active_rules' => $rules->where('is_currently_active', true)->count()
        ]);
    }

    public function store(LoyaltyRuleRequest $request)
    {
        $user = Auth::user();
        $program = LoyaltyProgram::findOrFail($request->loyalty_program_id);

        if ($program->company_id !== $user->company_id) {
            return response()->json(['error' => 'Loyalty program is not available'], 403);
        }

        $rule = LoyaltyProgramRule::create($request->validated() + ['is_active' => true]);
        return response()->json(['rule' => $rule->load('loyaltyProgram')], 201);
    }

    public function show($id)
    {
        $rule = LoyaltyProgramRule::with('loyaltyProgram')->findOrFail($id);
        $user = Auth::user();

        if ($rule->loyaltyProgram->company_id !== $user->company_id) {
            return response()->json(['error' => 'Loyalty rule not found'], 404);
        }

        return response()->json([
            'rule' => $rule,
            'is_currently_active' => $rule->is_active,
            'description' => $rule->description
        ]);
    }

    public function update(LoyaltyRuleRequest $request, $id)
    {
        $rule = LoyaltyProgramRule::with('loyaltyProgram')->findOrFail($id);
        $user = Auth::user();

        if ($rule->loyaltyProgram->company_id !== $user->company_id) {
            return response()->json(['error' => 'Loyalty rule not found'], 404);
        }

        $rule->update($request->validated());
        return response()->json(['rule' => $rule->fresh()->load('loyaltyProgram')]);
    }

    public function destroy($id)
    {
        $rule = LoyaltyProgramRule::with('loyaltyProgram')->findOrFail($id);
        $user = Auth::user();

        if ($rule->loyaltyProgram->company_id !== $user->company_id) {
            return response()->json(['error' => 'Loyalty rule not found'], 404);
        }

        $rule->delete();

        return response()->json([
            'message' => 'Loyalty rule deleted successfully'
        ]);
    }

    public function calculatePointsForPurchase(CalculateAndAwardRequest $request)
    {
        $user = User::findOrFail($request->user_id);
        return response()->json($this->calculateEarnedPoints(...));
    }

    public function awardPoints(CalculateAndAwardRequest $request)
    {
        $user = User::findOrFail($request->user_id);
        $result = $this->calculateEarnedPoints($user, $request->purchase_amount, $request->product_category_id, $request->product_item_id, $request->loyalty_program_id);

        if ($result['total_points_earned'] > 0) {
            DB::transaction(fn() => $user->addPoints($result['total_points_earned']));
        }

        return response()->json([
            'message' => 'Points awarded successfully',
            'user_id' => $user->id,
            'points_awarded' => $result['total_points_earned'],
            'new_balance' => $user->fresh()->points_balance,
            'applied_rules' => $result['applied_rules']
        ]);
    }

    private function calculateEarnedPoints(User $user, float $purchaseAmount, ?int $categoryId, ?int $itemId, int $programId)
    {
        $rules = LoyaltyProgramRule::where('loyalty_program_id', $programId)
            ->where('is_active', true)
            ->get();

        $totalPoints = 0;
        $appliedRules = [];

        foreach ($rules as $rule) {
            $points = $rule->calculatePoints($purchaseAmount, $categoryId, $itemId);

            if ($points > 0) {
                $totalPoints += $points;
                $appliedRules[] = [
                    'rule_name' => $rule->rule_name,
                    'points_earned' => $points,
                    'rule_type' => $rule->rule_type,
                ];
            }
        }

        return [
            'user_id' => $user->id,
            'purchase_amount' => $purchaseAmount,
            'total_points_earned' => round($totalPoints, 2),
            'applied_rules' => $appliedRules,
            'rules_count' => count($appliedRules)
        ];
    }

    public function getRuleTypes()
    {
        $ruleTypes = [
            'purchase_based' => [
                'name' => 'Purchase Based',
                'description' => 'Earn points based on purchase amount',
                'requires' => 'amount_per_point',
                'example' => 'Spend 50 PHP, earn 1 point'
            ],
            'birthday_bonus' => [
                'name' => 'Birthday Bonus',
                'description' => 'Earn points because it is your birthday',
                'requires' => 'points_earned',
                'example' => 'Free 50 points on your birthday'
            ],
            'referral_bonus' => [
                'name' => 'Referral Bonus',
                'description' => 'Fixed points for referring new users',
                'requires' => 'points_earned',
                'example' => '25 points per referral'
            ]
        ];

        return response()->json($ruleTypes);
    }

    public function getProgramRules($programId)
    {
        $user = Auth::user();

        $program = LoyaltyProgram::where('id', $programId)
            ->where('company_id', $user->company_id)
            ->firstOrFail();

        $rules = LoyaltyProgramRule::where('loyalty_program_id', $programId)
            ->where('is_active', true)
            ->get()
            ->map(function ($rule) {
                $rule->is_currently_active = $rule->is_active;
                $rule->description = $rule->description;
                return $rule;
            });

        return response()->json([
            'program' => $program,
            'rules' => $rules,
            'active_rules_count' => $rules->where('is_currently_active', true)->count()
        ]);
    }
}
