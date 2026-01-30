<?php

namespace App\Actions\Auth;

use App\Services\AuditLogService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Tymon\JWTAuth\Facades\JWTAuth;
use Tymon\JWTAuth\Exceptions\JWTException;

class LoginUserAction
{
    public function __construct(
        private AuditLogService $auditLogService
    ) {}

    public function execute(array $credentials, ?Request $request = null): array
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

        // Log login event to IP management service (non-blocking)
        // Use request() helper if $request is not provided
        $this->auditLogService->logLogin(
            $user->id,
            $user->email,
            $request ?? request(),
            $user->name ?? null,
            $user->role ?? null
        );

        return [
            'success' => true,
            'message' => 'Login successful',
            'data' => [
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'role' => $user->role ?? null,
                ],
                'token' => $token,
                'token_type' => 'bearer',
                'expires_in' => config('jwt.ttl') * 60,
            ],
            'status' => 200,
        ];
    }
}
