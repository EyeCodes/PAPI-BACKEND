<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Hash;

class UserProfileController extends Controller
{
    /**
     * Get user's assets with overall financial summary
     */
    public function getAssets(Request $request): JsonResponse
    {
        try {
            $user = $request->user();

            // Get assets from purchases table
            $assets = $user->purchases()->where('asset_type', 'asset')->orderBy('created_at', 'desc')->get();

            // Calculate financial summary
            $totalAssets = $assets->sum('asset_value');
            $monthlyIncome = $user->salary ?? 0;
            $totalLiabilities = $user->purchases()->where('asset_type', 'liability')->sum('liability_amount');
            $totalExpenses = $user->purchases()->sum('amount'); // ALL expenses
            $netWorth = $totalAssets - $totalLiabilities; // Net Worth = Total Assets - Total Liabilities

            return response()->json([
                'success' => true,
                'data' => [
                    'assets' => $assets,
                    'summary' => [
                        'salary' => $monthlyIncome,
                        'assets' => $totalAssets,
                        'liabilities' => $totalLiabilities,
                        'expenses' => $totalExpenses,
                        'net_worth' => $netWorth
                    ]
                ]
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to get assets', [
                'user_id' => $request->user()->id,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to get assets',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get user's liabilities with overall financial summary
     */
    public function getLiabilities(Request $request): JsonResponse
    {
        try {
            $user = $request->user();

            // Get liabilities from purchases table
            $liabilities = $user->purchases()->where('asset_type', 'liability')->orderBy('created_at', 'desc')->get();

            // Calculate financial summary
            $totalLiabilities = $liabilities->sum('liability_amount');
            $monthlyIncome = $user->salary ?? 0;
            $totalAssets = $user->purchases()->where('asset_type', 'asset')->sum('asset_value');
            $totalExpenses = $user->purchases()->sum('amount'); // ALL expenses
            $netWorth = $totalAssets - $totalLiabilities; // Net Worth = Total Assets - Total Liabilities

            return response()->json([
                'success' => true,
                'data' => [
                    'liabilities' => $liabilities,
                    'summary' => [
                        'salary' => $monthlyIncome,
                        'assets' => $totalAssets,
                        'liabilities' => $totalLiabilities,
                        'expenses' => $totalExpenses,
                        'net_worth' => $netWorth
                    ]
                ]
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to get liabilities', [
                'user_id' => $request->user()->id,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to get liabilities',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get overall financial information
     */
    public function getFinancialInfo(Request $request): JsonResponse
    {
        try {
            $user = $request->user();

            // Calculate financial summary from purchases table
            $monthlyIncome = $user->salary ?? 0;
            $totalAssets = $user->purchases()->where('asset_type', 'asset')->sum('asset_value');
            $totalLiabilities = $user->purchases()->where('asset_type', 'liability')->sum('liability_amount');
            $totalExpenses = $user->purchases()->sum('amount'); // ALL expenses
            $netWorth = $totalAssets - $totalLiabilities; // Net Worth = Total Assets - Total Liabilities

            return response()->json([
                'success' => true,
                'data' => [
                    'salary' => $monthlyIncome,
                    'assets' => $totalAssets,
                    'liabilities' => $totalLiabilities,
                    'expenses' => $totalExpenses,
                    'net_worth' => $netWorth
                ]
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to get financial info', [
                'user_id' => $request->user()->id,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to get financial info',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get user profile information
     */
    public function getProfile(Request $request): JsonResponse
    {
        try {
            $user = $request->user();

            return response()->json([
                'success' => true,
                'data' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'salary' => $user->salary,
                    'email_verified_at' => $user->email_verified_at,
                    'created_at' => $user->created_at,
                    'updated_at' => $user->updated_at
                ]
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to get profile', [
                'user_id' => $request->user()->id,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to get profile',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update user profile information
     */
    public function updateProfile(Request $request): JsonResponse
    {
        try {
            $user = $request->user();

            // Validate the request
            $validated = $request->validate([
                'name' => 'sometimes|string|max:255',
                'email' => 'sometimes|email|unique:users,email,' . $user->id,
                'salary' => 'sometimes|numeric|min:0|max:999999999.99'
            ]);

            // Update the user
            $user->update($validated);

            return response()->json([
                'success' => true,
                'message' => 'Profile updated successfully',
                'data' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'salary' => $user->salary,
                    'updated_at' => $user->updated_at
                ]
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            Log::error('Failed to update profile', [
                'user_id' => $request->user()->id,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to update profile',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Change user password
     */
    public function changePassword(Request $request): JsonResponse
    {
        try {
            $user = $request->user();

            // Validate the request
            $validated = $request->validate([
                'current_password' => 'required|string',
                'new_password' => 'required|string|min:8|confirmed',
                'new_password_confirmation' => 'required|string|min:8'
            ]);

            // Check if current password is correct
            if (!Hash::check($validated['current_password'], $user->password)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Current password is incorrect'
                ], 400);
            }

            // Update password
            $user->update([
                'password' => Hash::make($validated['new_password'])
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Password changed successfully'
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            Log::error('Failed to change password', [
                'user_id' => $request->user()->id,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to change password',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Check if user has salary set
     */
    public function checkSalary(Request $request): JsonResponse
    {
        try {
            $user = $request->user();
            $hasSalary = !is_null($user->salary) && $user->salary > 0;

            return response()->json([
                'success' => true,
                'data' => [
                    'has_salary' => $hasSalary,
                    'salary' => $user->salary,
                    'message' => $hasSalary ? 'Salary is set' : 'Salary is not set'
                ]
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to check salary', [
                'user_id' => $request->user()->id,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to check salary',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
