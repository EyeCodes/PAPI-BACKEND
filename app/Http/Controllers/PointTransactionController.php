<?php

namespace App\Http\Controllers;

use App\Models\PointTransaction;
use App\Models\LoyaltyProgram;
use App\Models\User;
use App\Services\PointService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class PointTransactionController extends Controller
{
    protected $pointService;

    // public function __construct(PointService $pointService)
    // {
    //     $this->pointService = $pointService;
    // }

    public function index(){
        return PointTransaction::all();
    }

    public function earnPoints(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'user_id' => 'required|exists:users,id',
            'program_id' => 'required|exists:loyalty_programs,id',
            'action_type' => 'required|in:purchase,referral,review,social_share',
            'amount' => 'required|numeric|min:0',
            'reference_id' => 'nullable|string',
            'notes' => 'nullable|string'
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        $points = $this->pointService->calculatePoints(
            $request->user_id,
            $request->program_id,
            $request->action_type,
            $request->amount
        );

        if ($points <= 0) {
            return response()->json(['message' => 'No points earned for this transaction'], 200);
        }

        $transaction = PointTransaction::create([
            'user_id' => $request->user_id,
            'program_id' => $request->program_id,
            'points' => $points,
            'transaction_type' => 'earn',
            'source' => $request->action_type,
            'reference_id' => $request->reference_id,
            'notes' => $request->notes
        ]);

        $user = User::find($request->user_id);
        $user->loyaltyPrograms()->updateExistingPivot($request->program_id, [
            'points_balance' => $user->loyaltyPrograms->find($request->program_id)->pivot->points_balance + $points
        ]);

        return response()->json([
            'message' => 'Points earned successfully',
            'points_earned' => $points,
            'transaction' => $transaction
        ], 201);
    }

    public function getUserTransactions($userId)
    {
        return PointTransaction::where('user_id', $userId)
            ->orderBy('created_at', 'desc')
            ->paginate(20);
    }

    public function getProgramTransactions($programId)
    {
        return PointTransaction::where('program_id', $programId)
            ->orderBy('created_at', 'desc')
            ->paginate(20);
    }
}