<?php

namespace App\Actions\Auth;

use App\Services\AuditLogService;
use Illuminate\Http\Request;
use Tymon\JWTAuth\Facades\JWTAuth;
use Tymon\JWTAuth\Exceptions\JWTException;

class LogoutUserAction
{
    public function __construct(
        private AuditLogService $auditLogService
    ) {}

    public function execute(?Request $request = null): array
    {
        try {
            $user = auth()->user();
            $userEmail = $user ? $user->email : null;
            $userId = $user ? $user->id : null;

            JWTAuth::invalidate(JWTAuth::getToken());

            // Log logout event to IP management service (non-blocking)
            // Use request() helper if $request is not provided
            if ($userEmail && $userId) {
                $this->auditLogService->logLogout(
                    $userId,
                    $userEmail,
                    $request ?? request(),
                    $user->name ?? null,
                    $user->role ?? null
                );
            }

            return [
                'success' => true,
                'message' => 'Successfully logged out',
                'status' => 200,
            ];
        } catch (JWTException $e) {
            return [
                'success' => false,
                'message' => 'Failed to logout, please try again',
                'status' => 500,
            ];
        }
    }
}
