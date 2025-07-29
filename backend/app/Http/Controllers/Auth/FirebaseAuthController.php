<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Kreait\Firebase\Contract\Auth as FirebaseAuth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class FirebaseAuthController extends Controller
{
    protected FirebaseAuth $firebaseAuth;

    public function __construct(FirebaseAuth $firebaseAuth)
    {
        $this->firebaseAuth = $firebaseAuth;
    }

    /**
     * Register a new user with Firebase
     * POST /api/firebase/register
     */
    public function register(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'firebase_token' => 'required|string',
            'name' => 'nullable|string|max:255',
            'email' => 'nullable|email|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            // Verify the Firebase ID token
            $verifiedIdToken = $this->firebaseAuth->verifyIdToken($request->firebase_token);
            $uid = $verifiedIdToken->claims()->get('sub');
            $email = $verifiedIdToken->claims()->get('email');
            $name = $request->input('name') ?: $verifiedIdToken->claims()->get('name', $email);

            // Check if user already exists
            $existingUser = User::where('firebase_uid', $uid)->orWhere('email', $email)->first();

            if ($existingUser) {
                return response()->json([
                    'success' => false,
                    'message' => 'User already exists',
                    'data' => [
                        'user' => [
                            'id' => $existingUser->id,
                            'name' => $existingUser->name,
                            'email' => $existingUser->email,
                            'points' => $existingUser->points,
                            'roles' => $existingUser->roles->pluck('name'),
                        ],
                    ],
                ], 409);
            }

            // Create new user
            $user = User::create([
                'name' => $name,
                'email' => $email,
                'firebase_uid' => $uid,
                'password' => bcrypt(Str::random(16)), // Random password since Firebase handles auth
                'points' => 0,
            ]);

            // Assign customer role
            $user->assignRole('customer');

            Log::info('New user registered via Firebase', [
                'firebase_uid' => $uid,
                'email' => $email,
                'user_id' => $user->id
            ]);

            return response()->json([
                'success' => true,
                'message' => 'User registered successfully',
                'data' => [
                    'user' => [
                        'id' => $user->id,
                        'name' => $user->name,
                        'email' => $user->email,
                        'points' => $user->points,
                        'roles' => $user->roles->pluck('name'),
                    ],
                ],
            ], 201);
        } catch (\Exception $e) {
            Log::error('Firebase registration failed', ['error' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'message' => 'Registration failed',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Login user with Firebase
     * POST /api/firebase/login
     */
    public function login(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'firebase_token' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            // Verify the Firebase ID token
            $verifiedIdToken = $this->firebaseAuth->verifyIdToken($request->firebase_token);
            $uid = $verifiedIdToken->claims()->get('sub');
            $email = $verifiedIdToken->claims()->get('email');

            // Find user
            $user = User::where('firebase_uid', $uid)->orWhere('email', $email)->first();

            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'User not found. Please register first.',
                ], 404);
            }

            // Update firebase_uid if not set
            if (!$user->firebase_uid) {
                $user->update(['firebase_uid' => $uid]);
            }

            Log::info('User logged in via Firebase', [
                'firebase_uid' => $uid,
                'email' => $email,
                'user_id' => $user->id
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Login successful',
                'data' => [
                    'user' => [
                        'id' => $user->id,
                        'name' => $user->name,
                        'email' => $user->email,
                        'points' => $user->points,
                        'roles' => $user->roles->pluck('name'),
                    ],
                ],
            ]);
        } catch (\Exception $e) {
            Log::error('Firebase login failed', ['error' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'message' => 'Login failed',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
