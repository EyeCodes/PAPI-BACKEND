<?php

namespace App\Http\Controllers;

use App\Models\LoyaltyTransaction;
use Illuminate\Http\Request;

class LoyaltyTransactionController extends Controller
{
    public function index()
    {
        return LoyaltyTransaction::with(['user', 'program', 'item'])->get();
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'user_id' => 'required|exists:users,id',
            'program_id' => 'required|exists:loyalty_programs,id',
            'item_id' => 'required|exists:product_items,id',
            'points' => 'required|integer',
            'transaction_type' => 'required|string',
            'source' => 'required|string',
            'notes' => 'nullable|string',
        ]);

        $transaction = LoyaltyTransaction::create($validated);

        return response()->json($transaction, 201);
    }

    public function show($id)
    {
        $transaction = LoyaltyTransaction::with(['user', 'program', 'item'])->findOrFail($id);
        return response()->json($transaction);
    }

    public function update(Request $request, $id)
    {
        $transaction = LoyaltyTransaction::findOrFail($id);

        $validated = $request->validate([
            'points' => 'sometimes|integer',
            'transaction_type' => 'sometimes|string',
            'source' => 'sometimes|string',
            'notes' => 'nullable|string',
        ]);

        $transaction->update($validated);

        return response()->json($transaction);
    }

    public function destroy($id)
    {
        $transaction = LoyaltyTransaction::findOrFail($id);
        $transaction->delete();

        return response()->json(null, 204);
    }
}
