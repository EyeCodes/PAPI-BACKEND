<?php

namespace App\Http\Controllers;
use App\Models\LoyaltyReward;
use Illuminate\Support\Facades\Validator;
use Illuminate\Http\Request;

class LoyaltyRewardController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    // public function index($programId)
    // {
    //     return LoyaltyReward::where('loyalty_program_id', $programId)->get();
    // }

    public function index(){
        return LoyaltyReward::all();
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request, $programId)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'point_cost' => 'required|integer|min:1',
            'reward_type' => 'required|in:discount,free_item,service,currency',
            'discount_amount' => 'nullable|numeric|required_if:reward_type,discount',
            'discount_type' => 'nullable|in:percentage,fixed|required_if:reward_type,discount',
            'free_item_id' => 'nullable|exists:items,id|required_if:reward_type,free_item',
            'is_active' => 'boolean',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'stock' => 'nullable|integer|min:0'
        ]);

        if($validator->fails()){
            return response()->json($validator->errors(), 422);
        }

        $reward = LoyaltyReward::create(array_merge(
            $request->all(), ['loyalty_program_id' => $programId]
        ));

        return response()->json($reward, 201);
    }

    /**
     * Display the specified resource.
     */
    public function show($programId, $rewardId)
    {
        return LoyaltyReward::where('loyalty_program_id', $programId)
            ->findOrFail($rewardId);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $programId, $rewardId)
    {
        $reward = LoyaltyReward::where('loyalty_program_id', $programId)
            ->findOrFail($rewardId);

        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|required|string|max:255',
            'description' => 'nullable|string',
            'point_cost' => 'sometimes|required|integer|min:1',
            'reward_type' => 'sometimes|required|in:discount,free_item,service,currency',
            'discount_amount' => 'nullable|numeric|required_if:reward_type,discount',
            'discount_type' => 'nullable|in:percentage,fixed|required_if:reward_type,discount',
            'free_item_id' => 'nullable|exists:items,id|required_if:reward_type,free_item',
            'is_active' => 'boolean',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'stock' => 'nullable|integer|min:0'
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        $reward->update($request->all());

        return response()->json($reward);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($programId, $rewardId)
    {
        $reward = LoyaltyReward::where('loyalty_program_id', $programId)
            ->findOrFail($rewardId);
            
        $reward->delete();
        return response()->json(null, 204);
    }
}
