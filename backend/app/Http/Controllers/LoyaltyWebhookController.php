<?php

namespace App\Http\Controllers;

use App\Models\CustomerPoint;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class LoyaltyWebhookController extends Controller
{
    //
    public function confirmEarning(string $transactionId){
        Log::info("Confirming earning for transaction ID: $transactionId");

        $customerPoint = CustomerPoint::where('transaction_id', $transactionId)
        ->where('transaction_type', 'earning')
        ->first();

        if (!$customerPoint) {
            Log::warning(("Earning transaction not found for ID: $transactionId"));
            return response()->json(['message' => 'Earning transaction not found'], 404);
        }

        if ($customerPoint->status === 'credited'){
            Log::info("Earning already credited for transaction ID: $transactionId");
            return response()->json(['message' => 'Earning already credited'], 200);
        }

        if ($customerPoint->creditPoints()){
            Log::info("Earning credited successfully for transaction ID: $transactionId");

            if ($customerPoint->qr_code_path && Storage::disk('public')->exists($customerPoint->qr_code_path)) {
                Storage::disk('public')->delete($customerPoint->qr_code_path);
                $customerPoint->update(['qr_code_path' => null]);
                Log::info("QR code deleted for transaction ID: $transactionId");
            }
            return response()->json(['message' => 'Earning credited successfully', 'transaction' => $customerPoint], 200);
        }
        Log::error("Failed to credit earning for transaction ID: $transactionId");
        return response()->json(['message' => 'Failed to credit earning'], 500);  
    }

    public function confirmRedemption(string $transactionId){
        Log::info("Confirming redemption for transaction ID: $transactionId");

        $customerPoint = CustomerPoint::where('transaction_id', $transactionId)
        ->where('transaction_type', 'redemption')
        ->first();
        
        if (!$customerPoint) {
            Log::warning("Redemption transaction not found for ID: $transactionId");
            return response()->json(['message' => 'Redemption transaction not found'], 404);
        }

        if ($customerPoint->status === 'redemed'){
            Log::info("Redemption already processed for transaction ID: $transactionId");
            return response()->json(['message' => 'Redemption already processed'], 200);
        }

        if ($customerPoint->redeemPoints()){
            Log::info("Redemption processed successfully for transaction ID: $transactionId");
            if ($customerPoint->qr_code_path && Storage::disk('public')->exists($customerPoint->qr_code_path)) {
                Storage::disk('public')->delete($customerPoint->qr_code_path);
                $customerPoint->update(['qr_code_path' => null]);
                Log::info("QR code deleted for transaction ID: $transactionId");
            }
            return response()->json(['message' => 'Redemption processed successfully', 'transaction' => $customerPoint], 200);
        }
        Log::error("Failed to process redemption for transaction ID: $transactionId");
        return response()->json(['message' => 'Failed to process redemption'], 500);

  }
}
