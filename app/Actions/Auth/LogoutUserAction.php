<?php

namespace App\Actions\Auth;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Tymon\JWTAuth\Facades\JWTAuth;
use Tymon\JWTAuth\Exceptions\JWTException;

class LogoutUserAction
{
    public function execute(): array
    {
        try {
            $user = auth()->user();
            $userEmail = $user ? $user->email : null;
            $userId = $user ? $user->id : null;

            JWTAuth::invalidate(JWTAuth::getToken());

            // Log logout event to IP management service
            if ($userEmail && $userId) {
                $this->logLogoutEvent($userId, $userEmail);
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

    private function logLogoutEvent(int $userId, string $userEmail): void
    {
        try {
            $ipServiceUrl = env('IP_SERVICE_URL', 'http://ip:80');
            Http::post("{$ipServiceUrl}/api/internal/audit-log", [
                'action' => 'logout',
                'user_id' => $userId,
                'user_email' => $userEmail,
                'description' => "User {$userEmail} logged out",
            ]);
        } catch (\Exception $e) {
            Log::warning('Failed to log logout event to IP service: ' . $e->getMessage());
        }
    }
}
