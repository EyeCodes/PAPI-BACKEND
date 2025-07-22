<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\LoyaltyProgramRule;
use App\Models\LoyaltyProgram;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;

class LoyaltyRuleController extends Controller
{
    public function index(Request $request)
    {
        $user = Auth::user();

        $rules = LoyaltyProgramRule::with('loyaltyProgram')
            ->where('loyaltyProgram', function($query) use ($user) {
                $query->where('company_id', $user->company_id);
            })
            ->when($request->program_id, function($query, $programId) {
                $query->where('loyalty_program_id', $programId);
            })
            ->when($request->rule_type, function($query, $ruleType) {
                $query->where('rule_type', $ruleType);
            })
            ->when($request->is_active !== null, function($query) use ($request) {
                $query->where('is_active', $request->boolean('is_active'));
            })
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function($rule) {
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

    public function store(Request $request)
    {
        $request->validate([
            'loyalty_program_id' => 'required|exists:loyaltyPrograms,id',
            'rule_name' => 'required|string|max:255',
            'rule_type' => 'required|string|in:purchase_based,birthday_bonus,referral_bonus,',
            'points_earned' => 'nullable|numeric|min:0',
            'amount_per_point' => 'nullable|numeric|min:0.01',
            'min_purchase_amount' => 'nullable|numeric|min:0',
            'product_category_id' => 'nullable|integer',
            'product_item_id' => 'nullable|integer',
            'active_from_date' => 'nullable|date',
            'active_to_date' => 'nullable|date|after_or_equal:active_from_date'
        ]);

        if ($request->rule_type === 'purchase_based' || $request->rule_type === 'birthday_bonus' || $request->rule_type === 'referral_bonus' ) {
            if (!$request->amount_per_point) {
                return response()->json(['error' => 'amount_per_point is required for purchase-based, category bonus, or item bonus rules'], 400);
            }
        } else {
            if (!$request->points_earned) {
                return response()->json(['error' => 'points_earned is required for bonus-type rules'], 400);
            }
        }
        //check if loyalty program exists for user
        $loyaltyProgram = LoyaltyProgram::findOrFail($request->loyalty_program_id);
        $user = Auth::user();
        if ($loyaltyProgram->company_id !== $user->company_id) {
            return response()->json(['error' => 'Loyalty program is not available'], 403);
        }
        $rule = LoyaltyProgramRule::create([
            'loyalty_program_id' => $request->loyalty_program_id,
            'rule_name' => $request->rule_name,
            'rule_type' => $request->rule_type,
            'points_earned' => $request->points_earned,
            'amount_per_point' => $request->amount_per_point,
            'min_purchase_amount' => $request->min_purchase_amount,
            'product_category_id' => $request->product_category_id,
            'product_item_id' => $request->product_item_id,
            'is_active' => true,
            'active_from_date' => $request->active_from_date,
            'active_to_date' => $request->active_to_date
        ]);

        return response()->json([
            'message' => 'Loyalty rule created successfully',
            'rule' => $rule->load('loyaltyProgram')
        ], 201);
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

    public function update(Request $request, $id)
    {
       $rule = LoyaltyProgramRule::with('loyaltyProgram')->findOrFail($id);
       $user = Auth::user();

        if ($rule->loyaltyProgram->company_id !== $user->company_id) {
            return response()->json(['error' => 'Loyalty rule not found'], 404);
        }

        $request->validate([
            'rule_name' => 'required|string|max:255',
            'rule_type' => 'required|string|in:purchase_based,birthday_bonus,referral_bonus',
            'points_earned' => 'nullable|numeric|min:0',
            'amount_per_point' => 'nullable|numeric|min:0.01',
            'min_purchase_amount' => 'nullable|numeric|min:0',
            'product_category_id' => 'nullable|integer',
            'product_item_id' => 'nullable|integer',
            'active_from_date' => 'nullable|date',
            'active_to_date' => 'nullable|date|after_or_equal:active_from_date'
        ]);

        $rule->update($request->only(
            'rule_name',
            'rule_type',
            'points_earned',
            'amount_per_point',
            'min_purchase_amount',
            'product_category_id',
            'product_item_id',
            'active_from_date',
            'active_to_date'
        ));

        return response()->json([
            'message' => 'Loyalty rule updated successfully',
            'rule' => $rule->fresh()->load('loyaltyProgram')
        ]);
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
    public function calculatePointsForPurchase(Request $request)
    {
        $request->validate([
            'user_id' => 'required|exists:users,id',
            'purchase_amount' => 'required|numeric|min:0',
            'product_category_id' => 'nullable|exists:product_categories,id',
            'product_item_id' => 'nullable|exists:product_items,id',
            'loyalty_program_id' => 'required|exists:loyalty_programs,id',
        ]);

        $user = User::findOrFail($request->user_id);
        $purchaseAmount = $request->purchase_amount;
        $categoryId = $request->product_category_id;
        $itemId = $request->product_item_id;

        $loyaltyProgramQuery = LoyaltyProgramRule::where('loyalty_program_id', $request->loyalty_program_id)
            ->where('is_active', true);
            
            if ($request->loyalty_program_id) {
                $loyaltyProgramQuery->where('loyalty_program_id', $request->loyalty_program_id);
            }
        
        $loyaltyPrograms = $loyaltyProgramQuery->get();

        $totalPoints = 0;
        $appliedRules = [];

        foreach ($loyaltyPrograms as $program) {
            $rules = LoyaltyProgramRule::where('loyalty_program_id', $program->id)
                ->where('is_active', true)
                ->get();

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
        }
            return response()->json([
                'user_id' => $user->id,
                'purchase_amount' => $purchaseAmount,
                'total_points_earned' => round ($totalPoints,2),
                'applied_rules' => $appliedRules,
                'rules_count' => count($appliedRules)
            ]);
    }

    public function awardPoints(Request $request)
    {
        $request->validate([
            'user_id' => 'required|exists:users,id',
            'purchase_amount' => 'required|numeric|min:0',
            'product_category_id' => 'nullable|exists:product_categories,id',
            'product_item_id' => 'nullable|exists:product_items,id',
            'loyalty_program_id' => 'required|exists:loyalty_programs'
        ]);

        $user = User::findOrFail($request->user_id);

        $calculationRequest = new Request([
            'user_id' => $user->id,
            'purchase_amount' => $request->purchase_amount,
            'product_category_id' => $request->product_category_id,
            'product_item_id' => $request->product_item_id,
            'loyalty_program_id' => $request->loyalty_program_id
        ]);

        $calculation = $this->calculatePointsForPurchase($calculationRequest);
        $calculationData = $calculation->getData();

        if ($calculationData->total_points_earned > 0) {
            DB::transaction (function() use ($user, $calculationData, $request){
                $user->addPoints($calculationData->total_points_earned);
            });
        }
        
        return response()->json([
            'message' => 'Points added successfully',
            'user_id' => $user->id,
            'points_awarded' => $calculationData->total_points_earned,
            'new_points_balance' => $user->fresh()->points_balance,
            'applied_rules' => $calculationData->applied_rules
        ]);
    }

    public function getRuleTypes()
    {
        $ruleTypes = [
        'purchase_based' =>[
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
            ->where ('company_id', $user->company_id)
            ->firstOrFail();

        $rules = LoyaltyProgramRule::where('loyalty_program_id', $programId)
        ->where('is_active', true)
        ->get()
        ->map(function($rule){
            $rule->is_currently_active = $rule->description;
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
