<?php

namespace App\Http\Controllers;
use App\Http\Controllers\Api\CompanyController;
use App\Http\Controllers\ItemController;
use App\Http\Controllers\LoyaltyPointController;
use App\Http\Controllers\LoyaltyProgramController;
use App\Http\Controllers\LoyaltyRewardController;
use App\Http\Controllers\PointTransactionController;
use App\Http\Controllers\RewardRedemptionController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::apiResource('company', CompanyController::class);
Route::apiResource('items', ItemController::class);
Route::apiResource('loyalty-programs', LoyaltyProgramController::class);
Route::apiResource('loyalty-points', LoyaltyPointController::class);
Route::apiResource('loyalty-rewards', LoyaltyRewardController::class);
Route::apiResource('point-transaction', PointTransactionController::class);
Route::apiResource('loyalty_redemptions', RewardRedemptionController::class);


Route::middleware(['auth:sanctum'])->get('/user', function (Request $request) {
    return $request->user();
});