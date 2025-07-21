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