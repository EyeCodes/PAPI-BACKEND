<?php

namespace App\Http\Controllers;

use App\Models\LoyaltyProgram;
use App\Models\User;
use Illuminate\Support\Facades\Validator;
use Illuminate\Http\Request;
use App\Models\PointTransaction;
use App\Models\LoyaltyPoint;


class LoyaltyPointController extends Controller
{

    public function index(){
        return LoyaltyPoint::all();
    }

    public function getUserPoints($userId)
    {
        $user = User::with('loyaltyPrograms')->findOrFail($userId);
        
        return response()->json([
            'total_points' => $user->loyaltyPrograms->sum('pivot.points_balance'),
            'programs' => $user->loyaltyPrograms->map(function ($program) {
                return [
                    'program_id' => $program->id,
                    'program_name' => $program->program_name,
                    'points_balance' => $program->pivot->points_balance
                ];
            })
        ]);
    }

    public function getProgramPoints($programId)
    {
        $program = LoyaltyProgram::with('members')->findOrFail($programId);
        
        return response()->json([
            'program_name' => $program->program_name,
            'total_points_issued' => $program->members->sum('pivot.points_balance'),
            'members' => $program->members->map(function ($member) {
                return [
                    'user_id' => $member->id,
                    'user_name' => $member->name,
                    'points_balance' => $member->pivot->points_balance
                ];
            })->sortByDesc('points_balance')->values()
        ]);
    }

    public function adjustPoints(Request $request, $userId, $programId)
    {
        $validator = Validator::make($request->all(), [
            'points' => 'required|integer',
            'reason' => 'required|string|max:255',
            'notes' => 'nullable|string'
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        $user = User::findOrFail($userId);
        $program = $user->loyaltyPrograms()->findOrFail($programId);
        
        $currentBalance = $program->pivot->points_balance;
        $newBalance = $currentBalance + $request->points;
        
        if ($newBalance < 0) {
            return response()->json([
                'message' => 'Cannot adjust points below zero'
            ], 400);
        }

        // Update balance
        $user->loyaltyPrograms()->updateExistingPivot($programId, [
            'points_balance' => $newBalance
        ]);

        // Record transaction
        PointTransaction::create([
            'user_id' => $userId,
            'program_id' => $programId,
            'points' => $request->points,
            'transaction_type' => $request->points > 0 ? 'adjust_add' : 'adjust_remove',
            'source' => 'manual_adjustment',
            'notes' => $request->notes ?? 'Manual adjustment: ' . $request->reason
        ]);

        return response()->json([
            'message' => 'Points adjusted successfully',
            'new_balance' => $newBalance
        ]);
    }
}