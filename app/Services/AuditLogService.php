<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class AuditLogService
{
    /**
     * IP Management Service URL
     */
    protected string $ipServiceUrl;

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->ipServiceUrl = env('IP_SERVICE_URL', 'http://ip:80');
    }

    /**
     * Log an event to the IP management service audit log
     *
     * @param string $action The action being logged (e.g., 'login', 'logout')
     * @param int $userId The user ID
     * @param string $userEmail The user email
     * @param Request|null $request The HTTP request (for IP, user agent, session)
     * @param string|null $description Optional description
     * @param string|null $sessionId Optional session ID (will be generated if not provided)
     * @param string|null $userName Optional user name (for syncing user in IP service)
     * @param string|null $userRole Optional user role (for syncing user in IP service)
     * @return bool True if successful, false otherwise
     */
    public function logEvent(
        string $action,
        int $userId,
        string $userEmail,
        ?Request $request = null,
        ?string $description = null,
        ?string $sessionId = null,
        ?string $userName = null,
        ?string $userRole = null
    ): bool {
        try {
            // Generate session ID if not provided
            if (!$sessionId && $request) {
                $sessionId = $this->getOrGenerateSessionId($request);
            } elseif (!$sessionId) {
                $sessionId = Str::uuid()->toString();
            }

            // Get IP address and user agent from request
            $ipAddress = $request ? $request->ip() : null;
            $userAgent = $request ? $request->userAgent() : null;

            // Build the payload
            $payload = [
                'action' => $action,
                'user_id' => $userId,
                'user_email' => $userEmail,
                'description' => $description ?? "User {$userEmail} {$action}",
                'session_id' => $sessionId,
            ];

            if ($userName !== null) {
                $payload['name'] = $userName;
            }
            if ($userRole !== null) {
                $payload['role'] = $userRole;
            }
            // Add IP address and user agent if available
            if ($ipAddress) {
                $payload['ip_address'] = $ipAddress;
            }
            if ($userAgent) {
                $payload['user_agent'] = $userAgent;
            }

            // Make the HTTP request
            /** @var \Illuminate\Http\Client\Response $response */
            $response = Http::timeout(5)
                ->post("{$this->ipServiceUrl}/api/internal/audit-log", $payload);

            $statusCode = $response->status();

            if ($statusCode >= 200 && $statusCode < 300) {
                return true;
            }

            Log::warning('Audit log request failed', [
                'status' => $statusCode,
                'response' => $response->json() ?? (string) $response->body(),
                'action' => $action,
                'user_id' => $userId,
            ]);

            return false;
        } catch (\Exception $e) {
            Log::warning('Failed to log audit event to IP service', [
                'action' => $action,
                'user_id' => $userId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return false;
        }
    }

    /**
     * Log a login event
     *
     * @param int $userId
     * @param string $userEmail
     * @param Request|null $request
     * @param string|null $userName
     * @param string|null $userRole
     * @return bool
     */
    public function logLogin(
        int $userId,
        string $userEmail,
        ?Request $request = null,
        ?string $userName = null,
        ?string $userRole = null
    ): bool {
        return $this->logEvent(
            'login',
            $userId,
            $userEmail,
            $request,
            "User {$userEmail} logged in",
            null,
            $userName,
            $userRole
        );
    }

    /**
     * Log a logout event
     *
     * @param int $userId
     * @param string $userEmail
     * @param Request|null $request
     * @param string|null $userName
     * @param string|null $userRole
     * @return bool
     */
    public function logLogout(
        int $userId,
        string $userEmail,
        ?Request $request = null,
        ?string $userName = null,
        ?string $userRole = null
    ): bool {
        return $this->logEvent(
            'logout',
            $userId,
            $userEmail,
            $request,
            "User {$userEmail} logged out",
            null,
            $userName,
            $userRole
        );
    }

    /**
     * Get or generate a session ID from the request
     *
     * @param Request $request
     * @return string
     */
    protected function getOrGenerateSessionId(Request $request): string
    {
        // Try to get session ID from header (for API calls)
        $sessionId = $request->header('X-Session-ID');

        if ($sessionId) {
            return $sessionId;
        }

        // Try to get from Laravel session
        if (session()->isStarted()) {
            if (!session()->has('audit_session_id')) {
                $sessionId = Str::uuid()->toString();
                session(['audit_session_id' => $sessionId]);
            } else {
                $sessionId = session('audit_session_id');
            }
        } else {
            // Generate a new session ID if no session is available
            $sessionId = Str::uuid()->toString();
        }

        return $sessionId;
    }
}
