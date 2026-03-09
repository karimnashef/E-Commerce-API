<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\AuthService;
use App\Http\Requests\RegisterRequest;
use App\Http\Requests\LoginRequest;
use App\Http\Requests\ChangePasswordRequest;
use App\Http\Requests\UpdateProfileRequest;
use App\Http\Requests\ResetPasswordRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AuthController extends Controller
{
    protected AuthService $authService;

    public function __construct(AuthService $authService)
    {
        $this->authService = $authService;
    }


    public function profile(Request $request)
    {
        $response = $this->authService->getUser($request->user());

        return response()->json($response, 200);
    }

    public function generateSecurityKey(Request $request)
    {
        $response = $this->authService->generateSecurityKey($request->user());

        return response()->json($response, 200);
    }

    public function register(RegisterRequest $request)
    {
        try {

            $response = $this->authService->register($request->validated());
            return response()->json($response, 201);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Registration failed. Please try again.',
            ], 500);
        }
    }

    public function login(LoginRequest $request)
    {
        try {
            $response = $this->authService->login($request->validated());

            if (!$response['success']) {
                return response()->json($response, 401);
            }

            return response()->json($response, 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Login failed. Please try again.',
            ], 500);
        }
    }

    public function logout(Request $request)
    {
        $response = $this->authService->logout($request->user());

        return response()->json($response, 200);
    }

    public function updateProfile(UpdateProfileRequest $request)
    {
        try {
            $response = $this->authService->updateProfile(
                $request->validated()
            );

            return response()->json($response, 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update profile.',
            ], 500);
        }
    }

    public function changePassword(ChangePasswordRequest $request)
    {
        try {
            $response = $this->authService->changePassword(
                $request->validated()
            );

            return response()->json($response, 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to change password.',
            ], 500);
        }
    }

    public function forgotPassword(ResetPasswordRequest $request)
    {
        $response = $this->authService->resetPassword([
            'name' => $request->name,
            'key' => $request->key
        ]);

        return response()->json($response, 200);
    }

    /**
     * Get list of active tokens
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getActiveSessions(Request $request)
    {
        $tokens = $request->user()->activeTokens()
            ->get()
            ->map(function ($token) {
                return [
                    'id' => $token->id,
                    'name' => $token->name,
                    'last_used_at' => $token->last_used_at,
                    'created_at' => $token->created_at,
                ];
            });

        return response()->json([
            'success' => true,
            'sessions' => $tokens,
        ], 200);
    }

    /**
     * Revoke a specific token
     *
     * @param Request $request
     * @param int $tokenId
     * @return \Illuminate\Http\JsonResponse
     */
    public function revokeSession(Request $request, int $tokenId)
    {
        $token = $request->user()->tokens()->find($tokenId);

        if (!$token) {
            return response()->json([
                'success' => false,
                'message' => 'Session not found.',
            ], 404);
        }

        $token->delete();

        return response()->json([
            'success' => true,
            'message' => 'Session revoked successfully.',
        ], 200);
    }
}

