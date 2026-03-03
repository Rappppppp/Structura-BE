<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Api\ApiController;
use App\Http\Requests\RegisterRequest;
use App\Http\Requests\LoginRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use DB;

class AuthController extends ApiController
{
    /**
     * Register a new user account.
     *
     * @param RegisterRequest $request
     * @return JsonResponse
     */
    public function register(RegisterRequest $request): JsonResponse
    {
        try {
            $user = DB::transaction(function () use ($request) {
                return User::create([
                    'name' => $request->input('name'),
                    'email' => $request->input('email'),
                    'password' => Hash::make($request->input('password')),
                    'role' => $request->input('role', 'user'),
                    'company' => $request->input('company'),
                    'phone_number' => $request->input('phone_number'),
                ]);
            });

            $token = $user->createToken('api-token', ['*'])->plainTextToken;

            return $this->success(
                [
                    'user' => new UserResource($user),
                    'token' => $token,
                ],
                'Account registered successfully',
                201
            );
        } catch (\Exception $e) {
            return $this->error('Registration failed: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Authenticate user and issue token.
     *
     * @param LoginRequest $request
     * @return JsonResponse
     * @throws ValidationException
     */
    public function login(LoginRequest $request): JsonResponse
    {
        // Ensure request is not rate limited before proceeding
        $request->ensureIsNotRateLimited();

        $user = User::where('email', $request->input('email'))->first();

        // Check credentials
        if (! $user || ! Hash::check($request->input('password'), $user->password)) {
            throw ValidationException::withMessages([
                'email' => 'The provided credentials are incorrect.',
            ]);
        }

        // Check if user account is active
        if ($user->trashed()) {
            throw ValidationException::withMessages([
                'email' => 'This account has been disabled.',
            ]);
        }

        // Create token with appropriate abilities
        $abilities = ['*'];
        if ($user->role === 'admin') {
            $abilities = ['admin'];
        }

        $token = $user->createToken('api-token', $abilities)->plainTextToken;

        return $this->success(
            [
                'user' => new UserResource($user),
                'token' => $token,
                'remember_me' => $request->shouldRemember(),
            ],
            'Login successful'
        );
    }

    /**
     * Logout the authenticated user.
     *
     * @param \Illuminate\Http\Request $request
     * @return JsonResponse
     */
    public function logout(\Illuminate\Http\Request $request): JsonResponse
    {
        try {
            $user = $request->user();

            if ($user && $user->currentAccessToken()) {
                $user->currentAccessToken()->delete();
            }

            return $this->success([], 'Logged out successfully');
        } catch (\Exception $e) {
            return $this->error('Logout failed: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Get the currently authenticated user.
     *
     * @param \Illuminate\Http\Request $request
     * @return JsonResponse
     */
    public function me(\Illuminate\Http\Request $request): JsonResponse
    {
        return $this->success(
            ['user' => new UserResource($request->user())],
            'Current user'
        );
    }

    /**
     * Refresh the current token (optional feature).
     *
     * @param \Illuminate\Http\Request $request
     * @return JsonResponse
     */
    public function refresh(\Illuminate\Http\Request $request): JsonResponse
    {
        try {
            $user = $request->user();

            // Delete old token
            $user->currentAccessToken()->delete();

            // Create new token
            $token = $user->createToken('api-token', ['*'])->plainTextToken;

            return $this->success(
                [
                    'user' => new UserResource($user),
                    'token' => $token,
                ],
                'Token refreshed successfully'
            );
        } catch (\Exception $e) {
            return $this->error('Token refresh failed: ' . $e->getMessage(), 500);
        }
    }
}
