<?php

use App\Http\Controllers\Api\CompanyController;
use App\Http\Controllers\ItemController;
use App\Http\Controllers\LoyaltyProgramController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::apiResource('company', CompanyController::class);
Route::apiResource('items', ItemController::class);
Route::apiResource('loyalty-program', LoyaltyProgramController::class);

Route::middleware(['auth:sanctum'])->get('/user', function (Request $request) {
    return $request->user();
});