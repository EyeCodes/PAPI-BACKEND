<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\RewardsResource;
use App\Models\LoyaltyReward as Rewards;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use App\Models\LoyaltyReward;
use Illuminate\Support\Facades\Validator;

use App\Http\Requests\LoyaltyRewardsRequest;

class LoyaltyRewardsController extends Controller
{
    /**
     * Display a listing of the rewards.
     */
    public function index()
    {

        $reward = LoyaltyReward::get();

        if ($reward->count() > 0){
            return RewardsResource::collection($reward);
        }
        else{
            return response()->json(['message' => 'Empty'], status:200);
        }
        
    }

    /**
     * Store a newly created reward in storage.
     */
    public function store(LoyaltyRewardsRequest $request)
    {
        $reward = Rewards::create($request->validated());

        $reward = Rewards::create([ 
            'loyalty_program_id' => $request->loyalty_program_id,
            $validator = Validator::make($request->all(), [
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
        ])]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        $reward =LoyaltyReward::create([ 
            'reward_name' => $request->reward_name,
            'reward_type' => $request->reward_type,
            'point_cost' => $request->point_cost,
            'discount_value' => $request->discount_value,
            'discount_percentage' => $request->discount_percentage,
            'item_id' => $request->item_id,
            'voucher_code' => $request->voucher_code,
            'is_active' => $request->is_active,
        ]);

        return new RewardsResource($reward);
    }

    // Show a single voucher
    public function show(LoyaltyReward $reward)
    {
            return new RewardsResource($reward);
    }

    // Update a voucher
    public function update(LoyaltyRewardsRequest $request, Rewards $reward)
    {
        $reward = Rewards::create($request->validated());

        $reward->update([
            'reward_name' => $request->reward_name,
            'reward_type' => $request->reward_type,
            'point_cost' => $request->point_cost,
            'item_id' => $request->item_id,
        ]);
        return new RewardsResource($reward);
    }

    // Delete a voucher
    public function destroy(LoyaltyReward $reward)
    {
        $reward->delete();
        return response()->json(['message' => $reward]);

    }
}