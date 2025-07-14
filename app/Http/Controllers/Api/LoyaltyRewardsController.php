<?php

namespace App\Http\Controllers\Api;


use App\Http\Controllers\Controller;
use App\Http\Resources\RewardsResource;
use Illuminate\Http\Request;
use App\Models\Rewards;
use Illuminate\Support\Facades\Validator;
use App\Rules\RewardTypeRule;
use App\Rules\PositiveIntegerRule;
use App\Rules\DiscountPercentageRule;
use App\Rules\NonNegativeNumberRule;


class LoyaltyRewardsController extends Controller // Now, this line will work correctly
{
    // List all rewards
    public function index()
    {
        $reward = Rewards::get();

        if ($reward->count() > 0){
            return RewardsResource::collection($reward);
        }
        else{
            return response()->json(['message' => 'Empty'], status:200);
        }
        
        
    }

    // Create a new voucher
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            //'loyalty_program_id' => 'required',
            'reward_name' => 'required|string|max:255',
            'reward_type' => ['required', new RewardTypeRule()],
            'point_cost' => ['required', new NonNegativeNumberRule()],
            'discount_value' => ['nullable', new NonNegativeNumberRule()],
           'discount_percentage' => ['nullable', new DiscountPercentageRule()],
            'item_id' => ['required', new PositiveIntegerRule()],
            'voucher_code' => 'nullable|string|unique:loyaltyRewards,voucher_code',
            'is_active' => 'boolean',
            'max_redemption_rate' => ['nullable', new NonNegativeNumberRule()],
            'expiration_days' => ['nullable', new NonNegativeNumberRule()],
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        $reward =Rewards::create([ 
            'reward_name' => $request->reward_name,
            'reward_type' => $request->reward_type,
            'point_cost' => $request->point_cost,
            'item_id' => $request->item_id,
        ]);

        return new RewardsResource($reward);
    }

    // Show a single voucher
    public function show(Rewards $reward)
    {
        
            return new RewardsResource($reward);

    }

    // Update a voucher
    public function update(Request $request, Rewards $reward)
    {
        $validate = Validator::make($request->all(), [
            //'loyalty_program_id' => 'required',
            'reward_name' => 'required|string|max:255',
            'reward_type' => 'required|string',
            'point_cost' => 'required|numeric|min:0',
            'discount_value' => 'nullable|numeric',
            'discount_percentage' => 'nullable|numeric',
            'item_id' => 'required|integer',
            'voucher_code' => 'nullable|string|unique:loyaltyRewards,voucher_code',
            'is_active' => 'boolean',
            'max_redemption_rate' => 'nullable|integer',
            'expiration_days' => 'nullable|integer'
        ]);

            //'loyalty_program_id' => 'required',
        $reward->update([
            'reward_name' => $request->reward_name,
            'reward_type' => $request->reward_type,
            'point_cost' => $request->point_cost,
            'item_id' => $request->item_id,
        ]);
        return new RewardsResource($reward);
    }

    // Delete a voucher
    public function destroy(Rewards $reward)
    {
        $reward->delete();
        return response()->json(['message' => $reward]);
    }
}