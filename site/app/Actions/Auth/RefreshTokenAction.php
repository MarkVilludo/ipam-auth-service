<?php

namespace App\Actions\Auth;

use Tymon\JWTAuth\Facades\JWTAuth;
use Tymon\JWTAuth\Exceptions\JWTException;

class RefreshTokenAction
{
    public function execute(): array
    {
        try {
            $token = JWTAuth::refresh(JWTAuth::getToken());
            $user = JWTAuth::user();

            return [
                'success' => true,
                'message' => 'Token refreshed successfully',
                'data' => [
                    'user' => [
                        'id' => $user->id,
                        'name' => $user->name,
                        'email' => $user->email,
                        'role' => $user->role,
                    ],
                    'token' => $token,
                    'token_type' => 'bearer',
                    'expires_in' => config('jwt.ttl') * 60,
                ],
                'status' => 200,
            ];
        } catch (JWTException $e) {
            return [
                'success' => false,
                'message' => 'Could not refresh token',
                'status' => 401,
            ];
        }
    }
}
