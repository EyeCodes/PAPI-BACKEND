<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Kreait\Firebase\Contract\Auth as FirebaseAuth;
use App\Models\User;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class FirebaseAuthMiddleware
{
    protected FirebaseAuth $firebaseAuth;

    public function __construct(FirebaseAuth $firebaseAuth)
    {
        $this->firebaseAuth = $firebaseAuth;
    }

    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $token = $request->bearerToken();

        if (!$token) {
            return response()->json([
                'success' => false,
                'message' => 'No Firebase ID token provided',
            ], 401);
        }

        try {
            // Verify the Firebase ID token
            $verifiedIdToken = $this->firebaseAuth->verifyIdToken($token);
            $uid = $verifiedIdToken->claims()->get('sub');
            $email = $verifiedIdToken->claims()->get('email');
            $name = $verifiedIdToken->claims()->get('name', $email);

            // Find or create user
            $user = User::where('firebase_uid', $uid)->first();

            if (!$user) {
                // Create new user from Firebase data
                $user = User::create([
                    'name' => $name,
                    'email' => $email,
                    'firebase_uid' => $uid,
                    'password' => bcrypt(Str::random(16)), // Random password since Firebase handles auth
                    'points' => 0,
                ]);

                // Assign customer role by default
                $user->assignRole('customer');

                Log::info('New user created from Firebase', [
                    'firebase_uid' => $uid,
                    'email' => $email,
                    'user_id' => $user->id
                ]);
            }

            // Add user to request for use in controllers
            $request->merge(['user' => $user]);
            $request->setUserResolver(fn() => $user);
        } catch (\Exception $e) {
            Log::error('Firebase token verification failed', [
                'error' => $e->getMessage(),
                'token' => substr($token, 0, 20) . '...'
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Invalid Firebase ID token',
                'error' => $e->getMessage(),
            ], 401);
        }

        return $next($request);
    }
}
