<?php

namespace App\Http\Controllers;

use App\Models\Transaction;
use App\Models\User;
use App\Models\TransactionItem;
use App\Models\Product;
use App\Models\Merchant;
use App\Models\UserMerchantPoints;
use App\Services\PointsService;
use App\Services\QRService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class PointsController extends Controller
{
    protected PointsService $pointsService;
    protected QRService $qrService;

    public function __construct(PointsService $pointsService, QRService $qrService)
    {
        $this->pointsService = $pointsService;
        $this->qrService = $qrService;
    }

    /**
     * Get user's points summary across all merchants
     * GET /api/points/summary
     */
    public function getPointsSummary(Request $request)
    {
        try {
            $user = $request->user();
            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'User not authenticated',
                ], Response::HTTP_UNAUTHORIZED);
            }

            $summary = $this->pointsService->getUserPointsSummary($user->id);

            return response()->json([
                'success' => true,
                'message' => 'Points summary retrieved successfully',
                'data' => [
                    'user_id' => $user->id,
                    'points_summary' => $summary,
                    'total_points' => collect($summary)->sum('points'),
                    'merchants_count' => count($summary),
                ],
            ]);
        } catch (\Throwable $e) {
            Log::error('Error getting points summary', ['error' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve points summary',
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Get user's points for a specific merchant
     * GET /api/points/merchant/{merchant_id}
     */
    public function getMerchantPoints(Request $request, $merchantId)
    {
        try {
            $user = $request->user();
            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'User not authenticated',
                ], Response::HTTP_UNAUTHORIZED);
            }

            $validator = Validator::make(['merchant_id' => $merchantId], [
                'merchant_id' => 'required|exists:merchants,id',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid merchant ID',
                    'errors' => $validator->errors(),
                ], Response::HTTP_UNPROCESSABLE_ENTITY);
            }

            $status = $this->pointsService->getUserMerchantPointsStatus($user->id, $merchantId);
            $points = $this->pointsService->getUserMerchantPoints($user->id, $merchantId);

            return response()->json([
                'success' => true,
                'message' => 'Merchant points retrieved successfully',
                'data' => [
                    'user_id' => $user->id,
                    'merchant_id' => $merchantId,
                    'current_points' => $points,
                    'can_earn_points' => $status['can_earn_points'],
                    'message' => $status['message'],
                ],
            ]);
        } catch (\Throwable $e) {
            Log::error('Error getting merchant points', ['error' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve merchant points',
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Get merchant's points earning information
     * GET /api/points/merchant/{merchant_id}/info
     */
    public function getMerchantPointsInfo(Request $request, $merchantId)
    {
        try {
            $validator = Validator::make(['merchant_id' => $merchantId], [
                'merchant_id' => 'required|exists:merchants,id',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid merchant ID',
                    'errors' => $validator->errors(),
                ], Response::HTTP_UNPROCESSABLE_ENTITY);
            }

            $info = $this->pointsService->getMerchantPointsInfo($merchantId);

            return response()->json([
                'success' => true,
                'message' => 'Merchant points info retrieved successfully',
                'data' => $info,
            ]);
        } catch (\Throwable $e) {
            Log::error('Error getting merchant points info', ['error' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve merchant points info',
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * List all products for a merchant
     * GET /api/points/products/{merchant_id}
     */
    public function listProducts(Request $request, $merchantId)
    {
        try {
            $validator = Validator::make(['merchant_id' => $merchantId], [
                'merchant_id' => 'required|exists:merchants,id',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid merchant ID',
                    'errors' => $validator->errors(),
                ], Response::HTTP_UNPROCESSABLE_ENTITY);
            }

            $products = Product::where('merchant_id', $merchantId)
                ->with('pointsRules')
                ->select(['id', 'name', 'description', 'price', 'currency', 'stock'])
                ->get();

            return response()->json([
                'success' => true,
                'message' => 'Products retrieved successfully',
                'data' => [
                    'merchant_id' => $merchantId,
                    'products' => $products,
                    'count' => $products->count(),
                ],
            ]);
        } catch (\Throwable $e) {
            Log::error('Error listing products', ['error' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve products',
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * List transactions for authenticated user
     * GET /api/points/transactions
     */
    public function listTransactions(Request $request)
    {
        try {
            $user = $request->user();
            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'User not authenticated',
                ], Response::HTTP_UNAUTHORIZED);
            }

            $transactions = Transaction::where('user_id', $user->id)
                ->with(['merchant:id,name', 'items.product:id,name,price'])
                ->select(['id', 'merchant_id', 'amount', 'awarded_points', 'created_at'])
                ->orderBy('created_at', 'desc')
                ->get();

            return response()->json([
                'success' => true,
                'message' => 'Transactions retrieved successfully',
                'data' => [
                    'user_id' => $user->id,
                    'transactions' => $transactions,
                    'count' => $transactions->count(),
                ],
            ]);
        } catch (\Throwable $e) {
            Log::error('Error listing transactions', ['error' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve transactions',
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Calculate potential points for a transaction (without creating it)
     * POST /api/points/calculate
     */
    public function calculatePoints(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'merchant_id' => 'required|exists:merchants,id',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.quantity' => 'required|integer|min:1',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        try {
            // Calculate total amount from products
            $totalAmount = 0;
            foreach ($request->input('items') as $item) {
                $product = Product::find($item['product_id']);
                $totalAmount += $product->price * $item['quantity'];
            }

            // Create a temporary transaction for calculation
            $tempTransaction = new Transaction([
                'merchant_id' => $request->input('merchant_id'),
                'amount' => $totalAmount,
                'user_id' => null,
            ]);

            // Calculate points
            $points = $this->pointsService->calculatePoints($tempTransaction);

            return response()->json([
                'success' => true,
                'message' => 'Points calculated successfully',
                'data' => [
                    'merchant_id' => $request->input('merchant_id'),
                    'amount' => $totalAmount,
                    'items_count' => count($request->input('items')),
                    'potential_points' => $points,
                ],
            ]);
        } catch (\Throwable $e) {
            Log::error('Error calculating points', ['error' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'message' => 'Failed to calculate points',
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Create a transaction and generate QR code
     * POST /api/points/create-transaction
     */
    public function createTransaction(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'merchant_id' => 'required|exists:merchants,id',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.quantity' => 'required|integer|min:1',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        try {
            DB::beginTransaction();

            // Calculate total amount from products
            $totalAmount = 0;
            foreach ($request->input('items') as $item) {
                $product = Product::find($item['product_id']);
                $totalAmount += $product->price * $item['quantity'];
            }

            // Create transaction
            $transaction = Transaction::create([
                'merchant_id' => $request->input('merchant_id'),
                'amount' => $totalAmount,
                'user_id' => null, // Will be set when customer scans QR
            ]);

            // Create transaction items
            foreach ($request->input('items') as $item) {
                TransactionItem::create([
                    'transaction_id' => $transaction->id,
                    'product_id' => $item['product_id'],
                    'quantity' => $item['quantity'],
                ]);
            }

            // Reload the transaction to get the calculated amount
            $transaction->load('items.product');

            // Calculate potential points
            $potentialPoints = $this->pointsService->calculatePoints($transaction);

            // Generate QR code
            $qrData = [
                'transaction_id' => $transaction->id,
                'merchant_id' => $transaction->merchant_id,
                'amount' => $transaction->amount,
                'timestamp' => now()->toISOString(),
                'type' => 'earn_points'
            ];

            $encryptedData = $this->qrService->encryptData($qrData);
            $qrCode = $this->qrService->generateQrCode($encryptedData);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Transaction created successfully',
                'data' => [
                    'transaction_id' => $transaction->id,
                    'merchant_id' => $transaction->merchant_id,
                    'amount' => $transaction->amount,
                    'potential_points' => $potentialPoints,
                    'qr_code' => $qrCode,
                    'items_count' => count($request->input('items')),
                    'created_at' => $transaction->created_at,
                ],
            ]);
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('Error creating transaction', ['error' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'message' => 'Failed to create transaction',
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Process QR scan from customer app
     * POST /api/points/scan-qr
     */
    public function scanQr(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'qr_data' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        try {
            $user = $request->user();
            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'User not authenticated',
                ], Response::HTTP_UNAUTHORIZED);
            }

            $qrInput = $request->input('qr_data');

            // Check if input is a base64 QR code image or encrypted data
            $encryptedData = null;

            // Try to decode as QR code image first
            $decodedText = $this->qrService->decodeQrCode($qrInput);
            if ($decodedText) {
                $encryptedData = $decodedText;
            } else {
                // If not a QR image, assume it's encrypted data
                $encryptedData = $qrInput;
            }

            // Decrypt QR data
            $qrData = $this->qrService->decryptData($encryptedData);

            if (!$qrData || $qrData['type'] !== 'earn_points') {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid QR code',
                ], Response::HTTP_BAD_REQUEST);
            }

            $transaction = Transaction::findOrFail($qrData['transaction_id']);

            // Check if points already awarded
            if ($transaction->user_id) {
                return response()->json([
                    'success' => false,
                    'message' => 'Points already awarded for this transaction',
                ], Response::HTTP_BAD_REQUEST);
            }

            // Award points using the service
            $this->pointsService->awardPoints($transaction);

            // Update transaction with user ID
            $transaction->update(['user_id' => $user->id]);

            // Get the awarded points from the transaction
            $points = $transaction->awarded_points ?? 0;

            return response()->json([
                'success' => true,
                'message' => 'Points awarded successfully',
                'data' => [
                    'user_id' => $user->id,
                    'transaction_id' => $transaction->id,
                    'points_awarded' => $points,
                    'new_balance' => $this->pointsService->getUserMerchantPoints($user->id, $transaction->merchant_id),
                    'merchant_id' => $transaction->merchant_id,
                    'amount' => $transaction->amount,
                ],
            ]);
        } catch (\Throwable $e) {
            Log::error('Error processing QR scan', ['error' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'message' => 'Failed to process QR scan',
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Redeem points for authenticated user
     * POST /api/points/redeem
     */
    public function redeem(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'points' => 'required|integer|min:1',
            'merchant_id' => 'required|exists:merchants,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        try {
            $user = $request->user();
            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'User not authenticated',
                ], Response::HTTP_UNAUTHORIZED);
            }

            $points = (int) $request->input('points');
            $merchantId = (int) $request->input('merchant_id');

            $currentBalance = $this->pointsService->getUserMerchantPoints($user->id, $merchantId);

            if ($currentBalance < $points) {
                return response()->json([
                    'success' => false,
                    'message' => 'Insufficient points balance',
                    'data' => [
                        'current_balance' => $currentBalance,
                        'requested_points' => $points,
                        'merchant_id' => $merchantId,
                    ],
                ], Response::HTTP_BAD_REQUEST);
            }

            $success = $this->pointsService->spendPointsFromUser($user->id, $merchantId, $points);

            if (!$success) {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to redeem points',
                ], Response::HTTP_INTERNAL_SERVER_ERROR);
            }

            return response()->json([
                'success' => true,
                'message' => 'Points redeemed successfully',
                'data' => [
                    'user_id' => $user->id,
                    'points_redeemed' => $points,
                    'new_balance' => $this->pointsService->getUserMerchantPoints($user->id, $merchantId),
                    'merchant_id' => $merchantId,
                ],
            ]);
        } catch (\Throwable $e) {
            Log::error('Error redeeming points', ['error' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'message' => 'Failed to redeem points',
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Transfer points between merchants (if supported)
     * POST /api/points/transfer
     */
    public function transferPoints(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'from_merchant_id' => 'required|exists:merchants,id',
            'to_merchant_id' => 'required|exists:merchants,id|different:from_merchant_id',
            'points' => 'required|integer|min:1',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        try {
            $user = $request->user();
            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'User not authenticated',
                ], Response::HTTP_UNAUTHORIZED);
            }

            $points = (int) $request->input('points');
            $fromMerchantId = (int) $request->input('from_merchant_id');
            $toMerchantId = (int) $request->input('to_merchant_id');

            $fromBalance = $this->pointsService->getUserMerchantPoints($user->id, $fromMerchantId);

            if ($fromBalance < $points) {
                return response()->json([
                    'success' => false,
                    'message' => 'Insufficient points balance in source merchant',
                    'data' => [
                        'from_merchant_balance' => $fromBalance,
                        'requested_points' => $points,
                        'from_merchant_id' => $fromMerchantId,
                    ],
                ], Response::HTTP_BAD_REQUEST);
            }

            DB::beginTransaction();

            // Spend points from source merchant
            $spendSuccess = $this->pointsService->spendPointsFromUser($user->id, $fromMerchantId, $points);
            if (!$spendSuccess) {
                DB::rollBack();
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to spend points from source merchant',
                ], Response::HTTP_INTERNAL_SERVER_ERROR);
            }

            // Add points to destination merchant
            $this->pointsService->addPointsToUser($user->id, $toMerchantId, $points);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Points transferred successfully',
                'data' => [
                    'user_id' => $user->id,
                    'points_transferred' => $points,
                    'from_merchant_id' => $fromMerchantId,
                    'to_merchant_id' => $toMerchantId,
                    'from_merchant_balance' => $this->pointsService->getUserMerchantPoints($user->id, $fromMerchantId),
                    'to_merchant_balance' => $this->pointsService->getUserMerchantPoints($user->id, $toMerchantId),
                ],
            ]);
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('Error transferring points', ['error' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'message' => 'Failed to transfer points',
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Get points history for a specific merchant
     * GET /api/points/history/{merchant_id}
     */
    public function getPointsHistory(Request $request, $merchantId)
    {
        try {
            $user = $request->user();
            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'User not authenticated',
                ], Response::HTTP_UNAUTHORIZED);
            }

            $validator = Validator::make(['merchant_id' => $merchantId], [
                'merchant_id' => 'required|exists:merchants,id',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid merchant ID',
                    'errors' => $validator->errors(),
                ], Response::HTTP_UNPROCESSABLE_ENTITY);
            }

            $transactions = Transaction::where('user_id', $user->id)
                ->where('merchant_id', $merchantId)
                ->whereNotNull('awarded_points')
                ->select(['id', 'amount', 'awarded_points', 'created_at'])
                ->orderBy('created_at', 'desc')
                ->get();

            $userMerchantPoints = UserMerchantPoints::where('user_id', $user->id)
                ->where('merchant_id', $merchantId)
                ->first();

            return response()->json([
                'success' => true,
                'message' => 'Points history retrieved successfully',
                'data' => [
                    'user_id' => $user->id,
                    'merchant_id' => $merchantId,
                    'current_balance' => $userMerchantPoints ? $userMerchantPoints->points : 0,
                    'total_earned' => $userMerchantPoints ? $userMerchantPoints->total_earned : 0,
                    'total_spent' => $userMerchantPoints ? $userMerchantPoints->total_spent : 0,
                    'transactions' => $transactions,
                    'transaction_count' => $transactions->count(),
                ],
            ]);
        } catch (\Throwable $e) {
            Log::error('Error getting points history', ['error' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve points history',
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
