# Audit Logging Integration

This document explains how the `ipam-auth-service` integrates with the audit logging system in `ipam-ip-management-service`.

## Overview

The auth service logs login and logout events to the IP management service's audit log system. This allows all authentication events to be tracked in a centralized audit log.

## Configuration

Set the following environment variable in your `.env` file:

```env
IP_SERVICE_URL=http://ip:80
```

For local development:
```env
IP_SERVICE_URL=http://localhost:8000/ip
```

## Usage

### Automatic Logging

Login and logout events are automatically logged when using the `LoginUserAction` and `LogoutUserAction` classes. No additional code is required.

### Manual Logging

If you need to log custom events, you can use the `AuditLogService`:

```php
use App\Services\AuditLogService;
use Illuminate\Http\Request;

class YourController extends Controller
{
    public function __construct(
        private AuditLogService $auditLogService
    ) {}

    public function someAction(Request $request)
    {
        $user = auth()->user();
        
        // Log a custom event
        $this->auditLogService->logEvent(
            'custom_action',
            $user->id,
            $user->email,
            $request,
            'Description of what happened'
        );
    }
}
```

### Available Methods

#### `logEvent()`
Log any custom event:
```php
$auditLogService->logEvent(
    string $action,        // Event name (e.g., 'login', 'logout', 'custom_action')
    int $userId,           // User ID
    string $userEmail,     // User email
    ?Request $request,     // HTTP request (for IP, user agent, session)
    ?string $description, // Optional description
    ?string $sessionId     // Optional session ID (auto-generated if not provided)
): bool
```

#### `logLogin()`
Convenience method for login events:
```php
$auditLogService->logLogin(
    int $userId,
    string $userEmail,
    ?Request $request
): bool
```

#### `logLogout()`
Convenience method for logout events:
```php
$auditLogService->logLogout(
    int $userId,
    string $userEmail,
    ?Request $request
): bool
```

## Session Tracking

The service automatically tracks sessions:
1. First checks for `X-Session-ID` header (for API calls)
2. Falls back to Laravel session storage
3. Generates a new UUID if no session is available

This ensures all activities within a session can be tracked together.

## Error Handling

The audit logging is **non-blocking**. If logging fails:
- The error is logged to Laravel's log system
- The original operation (login/logout) continues normally
- No exception is thrown to the user

This ensures that audit logging issues don't affect the user experience.

## API Endpoint

The service calls the following endpoint in the IP management service:

```
POST {IP_SERVICE_URL}/api/internal/audit-log
```

**Payload:**
```json
{
    "action": "login|logout|custom",
    "user_id": 1,
    "user_email": "user@example.com",
    "description": "Optional description",
    "session_id": "uuid-string",
    "ip_address": "192.168.1.1",
    "user_agent": "Mozilla/5.0..."
}
```

## Security Note

The `/api/internal/audit-log` endpoint should be protected by service-to-service authentication in production. Consider:
- IP whitelisting
- API key authentication
- Mutual TLS (mTLS)
- Network-level security (private network/VPN)

## Testing

To test audit logging:

1. Ensure `IP_SERVICE_URL` is correctly configured
2. Perform a login/logout action
3. Check the IP management service's audit log dashboard
4. Verify the event appears with correct user, IP, and session information

## Troubleshooting

### Events not appearing in audit log

1. Check that `IP_SERVICE_URL` is correctly set
2. Verify the IP management service is running and accessible
3. Check Laravel logs for any HTTP errors:
   ```bash
   tail -f storage/logs/laravel.log
   ```
4. Verify network connectivity between services

### Session ID not persisting

- Ensure sessions are properly configured in `config/session.php`
- For API calls, include `X-Session-ID` header in requests
- Check that session driver is working (database/file/redis)
