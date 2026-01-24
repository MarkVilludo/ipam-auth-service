<?php

namespace App\Actions\Auth;

use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Tymon\JWTAuth\Facades\JWTAuth;
use Tymon\JWTAuth\Exceptions\JWTException;

class LoginUserAction
{
    public function execute(array $credentials): array
    {
        $validator = Validator::make($credentials, [
            'email' => 'required|string|email',
            'password' => 'required|string',
        ]);

        if ($validator->fails()) {
            return [
                'success' => false,
                'errors' => $validator->errors(),
                'status' => 422,
            ];
        }

        try {
            if (!$token = JWTAuth::attempt($credentials)) {
                return [
                    'success' => false,
                    'message' => 'Invalid credentials',
                    'status' => 401,
                ];
            }
        } catch (JWTException $e) {
            return [
                'success' => false,
                'message' => 'Could not create token',
                'status' => 500,
            ];
        }

        $user = auth()->user();

        // Log login event to IP management service
        $this->logLoginEvent($user);

        return [
            'success' => true,
            'message' => 'Login successful',
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
    }

    private function logLoginEvent($user): void
    {
        try {
            $ipServiceUrl = env('IP_SERVICE_URL', 'http://ip:80');
            Http::post("{$ipServiceUrl}/api/internal/audit-log", [
                'action' => 'login',
                'user_id' => $user->id,
                'user_email' => $user->email,
                'description' => "User {$user->email} logged in",
            ]);
        } catch (\Exception $e) {
            Log::warning('Failed to log login event to IP service: ' . $e->getMessage());
        }
    }
}
