<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\PointsController;
use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\Auth\FirebaseAuthController;
use App\Http\Controllers\FinancialAdvisorController;
use App\Http\Controllers\UserProfileController;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

// Authentication Routes (Public)
Route::post('/login', [AuthController::class, 'login']);
Route::post('/register', [AuthController::class, 'register']);
Route::post('/logout', [AuthController::class, 'logout'])->middleware('auth:sanctum');

// Firebase Authentication Routes (Public)
Route::post('/firebase/login', [FirebaseAuthController::class, 'login']);
Route::post('/firebase/register', [FirebaseAuthController::class, 'register']);

// Points API Routes (Protected - Sanctum)
Route::prefix('points')->middleware('auth:sanctum')->group(function () {
    // Points Summary and Status
    Route::get('/summary', [PointsController::class, 'getPointsSummary']);
    Route::get('/merchant/{merchant_id}', [PointsController::class, 'getMerchantPoints']);
    Route::get('/merchant/{merchant_id}/info', [PointsController::class, 'getMerchantPointsInfo']);
    Route::get('/history/{merchant_id}', [PointsController::class, 'getPointsHistory']);

    // Product Management
    Route::get('/products/{merchant_id}', [PointsController::class, 'listProducts']);

    // Transaction Management
    Route::get('/transactions', [PointsController::class, 'listTransactions']);
    Route::post('/calculate', [PointsController::class, 'calculatePoints']);
    Route::post('/create-transaction', [PointsController::class, 'createTransaction']);

    // QR Code Operations
    Route::post('/scan-qr', [PointsController::class, 'scanQr']);

    // Points Operations
    Route::post('/redeem', [PointsController::class, 'redeem']);
    Route::post('/transfer', [PointsController::class, 'transferPoints']);
});

// Firebase Points API Routes (Protected - Firebase)
Route::prefix('firebase/points')->middleware('firebase.auth')->group(function () {
    // Points Summary and Status
    Route::get('/summary', [PointsController::class, 'getPointsSummary']);
    Route::get('/merchant/{merchant_id}', [PointsController::class, 'getMerchantPoints']);
    Route::get('/merchant/{merchant_id}/info', [PointsController::class, 'getMerchantPointsInfo']);
    Route::get('/history/{merchant_id}', [PointsController::class, 'getPointsHistory']);

    // Product Management
    Route::get('/products/{merchant_id}', [PointsController::class, 'listProducts']);

    // Transaction Management
    Route::get('/transactions', [PointsController::class, 'listTransactions']);
    Route::post('/calculate', [PointsController::class, 'calculatePoints']);
    Route::post('/create-transaction', [PointsController::class, 'createTransaction']);

    // QR Code Operations
    Route::post('/scan-qr', [PointsController::class, 'scanQr']);

    // Points Operations
    Route::post('/redeem', [PointsController::class, 'redeem']);
    Route::post('/transfer', [PointsController::class, 'transferPoints']);
});

// Financial Advisor API Routes
Route::prefix('financial-advisor')->middleware(['auth:sanctum'])->group(function () {
    Route::post('/chat', [FinancialAdvisorController::class, 'processMessage']);
    Route::get('/categories', [FinancialAdvisorController::class, 'getCategories']);
    Route::get('/configuration', [FinancialAdvisorController::class, 'getConfiguration']);
    Route::post('/change-provider', [FinancialAdvisorController::class, 'changeProvider']);
    Route::post('/test-provider', [FinancialAdvisorController::class, 'testProvider']);
});

// Firebase Financial Advisor Routes
Route::prefix('firebase/financial-advisor')->middleware(['firebase.auth'])->group(function () {
    Route::post('/chat', [FinancialAdvisorController::class, 'processMessage']);
    Route::get('/categories', [FinancialAdvisorController::class, 'getCategories']);
    Route::get('/configuration', [FinancialAdvisorController::class, 'getConfiguration']);
    Route::post('/change-provider', [FinancialAdvisorController::class, 'changeProvider']);
    Route::post('/test-provider', [FinancialAdvisorController::class, 'testProvider']);
});

// Financial API Routes (Protected - Sanctum)
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/assets', [UserProfileController::class, 'getAssets']);
    Route::get('/liabilities', [UserProfileController::class, 'getLiabilities']);
    Route::get('/financial-info', [UserProfileController::class, 'getFinancialInfo']);
});

// Profile Management Routes (Protected - Sanctum)
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/profile', [UserProfileController::class, 'getProfile']);
    Route::put('/profile', [UserProfileController::class, 'updateProfile']);
    Route::post('/change-password', [UserProfileController::class, 'changePassword']);
    Route::get('/check-salary', [UserProfileController::class, 'checkSalary']);
});

// Firebase Financial API Routes (Protected - Firebase)
Route::prefix('firebase')->middleware('firebase.auth')->group(function () {
    Route::get('/assets', [UserProfileController::class, 'getAssets']);
    Route::get('/liabilities', [UserProfileController::class, 'getLiabilities']);
    Route::get('/financial-info', [UserProfileController::class, 'getFinancialInfo']);
});

// Firebase Profile Management Routes (Protected - Firebase)
Route::prefix('firebase')->middleware('firebase.auth')->group(function () {
    Route::get('/profile', [UserProfileController::class, 'getProfile']);
    Route::put('/profile', [UserProfileController::class, 'updateProfile']);
    Route::post('/change-password', [UserProfileController::class, 'changePassword']);
    Route::get('/check-salary', [UserProfileController::class, 'checkSalary']);
});
