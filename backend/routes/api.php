<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Request;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\CompanyController;
use App\Http\Controllers\Api\LoyaltyRuleController;
use App\Http\Controllers\Api\LoyaltyRewardsController;

// Public authentication routes
Route::post('register', [AuthController::class, 'register']);
Route::post('login', [AuthController::class, 'login']);

// Protected API routes (auth:sanctum)
// Note: THIS CAN ONLY BE ACCESSED IF USER HAS THE TOKEN IN ITS SESSION AND USING IT TO BE VERIFIED THROUGH HTTP AUTH // // HEADERS
Route::middleware('auth:sanctum')->group(function () {
    // Get authenticated user info
    // Eg. {
    //     "user": {
    //         "id": 1,
    //         "name": "John Doe",
    //         "email": "john@example.com",
    //         "created_at": "2025-07-12T08:15:30.000000Z",
    //         "updated_at": "2025-07-12T08:15:30.000000Z"
    //     },
    //     "token": "eyJ0eXAiOiJKV1QiLCJh..."
    // }
    Route::get('user', function (Request $request) {
        return response()->json([
            'user' => $request->user(),
            'token' => $request->bearerToken(),
        ]);
    });

    Route::apiResource('companies', CompanyController::class);
    Route::apiResource('rewards', LoyaltyRewardsController::class);
    Route::apiResource('loyalty-rules', LoyaltyRuleController::class);
    Route::apiResource('loyalty-programs', LoyaltyRuleController::class);

    Route::post('logout', [AuthController::class, 'logout']);
});
