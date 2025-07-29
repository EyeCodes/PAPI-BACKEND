<?php

namespace App\Http\Controllers;

use App\Models\RewardRedemption;
use App\Models\LoyaltyReward;
use App\Models\User;
use App\Services\RewardService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class RewardRedemptionController extends Controller
{
    protected $rewardService;

    // public function __construct(RewardService $rewardService)
    // {
    //     $this->rewardService = $rewardService;
    // }

    public function index(){
        return RewardRedemption::all();
    }

    public function redeem(Request $request, $rewardId)
    {
        $validator = Validator::make($request->all(), [
            'user_id' => 'required|exists:users,id'
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        try {
            $this->rewardService->redeemReward($request->user_id, $rewardId);
            
            return response()->json([
                'message' => 'Reward redeemed successfully',
                'reward' => LoyaltyReward::find($rewardId)
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'message' => $e->getMessage()
            ], 400);
        }
    }

    public function getUserRedemptions($userId)
    {
        return RewardRedemption::with('reward')
            ->where('user_id', $userId)
            ->orderBy('created_at', 'desc')
            ->paginate(20);
    }

    public function updateRedemptionStatus(Request $request, $redemptionId)
    {
        $validator = Validator::make($request->all(), [
            'status' => 'required|in:pending,fulfilled,cancelled'
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        $redemption = RewardRedemption::findOrFail($redemptionId);
        $redemption->update(['status' => $request->status]);

        return response()->json($redemption);
    }
}