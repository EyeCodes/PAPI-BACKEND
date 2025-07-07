<?php

use App\Http\Controllers\Api\CompanyController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\LoyaltyRuleController;

// Ensure the LoyaltyRuleController class exists in the specified namespace
// If it does not exist, create it in 'App\Http\Controllers\Api' or update the namespace accordingly.
Route::apiResource('company', CompanyController::class);

Route::apiResource('loyalty-rules', LoyaltyRuleController::class);
Route::middleware(['auth:sanctum'])->get('/user', function (Request $request) {
    return $request->user();
});

  