<?php

namespace App\Actions\Auth;

use Tymon\JWTAuth\Facades\JWTAuth;
use Tymon\JWTAuth\Exceptions\JWTException;

class GetAuthenticatedUserAction
{
    public function execute(): array
    {
        try {
            $user = JWTAuth::user();

            if (!$user) {
                return [
                    'success' => false,
                    'message' => 'User not found',
                    'status' => 404,
                ];
            }

            return [
                'success' => true,
                'data' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'role' => $user->role,
                ],
                'status' => 200,
            ];
        } catch (JWTException $e) {
            return [
                'success' => false,
                'message' => 'User not found',
                'status' => 404,
            ];
        }
    }
}
