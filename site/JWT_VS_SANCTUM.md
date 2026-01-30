# JWT vs Sanctum: Current Implementation Explanation

## Current Status

**Your codebase is currently using JWT Authentication (`tymon/jwt-auth`), NOT Sanctum.**

Evidence:
- `composer.json` includes `"tymon/jwt-auth": "^2.1"`
- User model implements `JWTSubject` interface
- Actions use `JWTAuth::attempt()`, `JWTAuth::fromUser()`, etc.
- Config file: `config/jwt.php` exists

---

## JWT Methods Explanation (Current Implementation)

### `getJWTIdentifier()`

**Purpose:** Returns the unique identifier that will be stored in the JWT token's "subject" (sub) claim.

**What it does:**
```php
public function getJWTIdentifier()
{
    return $this->getKey(); // Returns the user's ID (primary key)
}
```

**In the JWT token, this becomes:**
```json
{
  "sub": 1,  // The user ID
  "iat": 1234567890,
  "exp": 1234571490,
  ...
}
```

**Why it's needed:** When you decode a JWT token, you need to know which user it belongs to. The `sub` claim contains the user ID, which is then used to retrieve the user from the database.

---

### `getJWTCustomClaims()`

**Purpose:** Returns additional data to be embedded in the JWT token payload.

**What it does:**
```php
public function getJWTCustomClaims()
{
    return [
        'name' => $this->name,
        'email' => $this->email,
        'role' => $this->role,
    ];
}
```

**In the JWT token, this becomes:**
```json
{
  "sub": 1,
  "name": "John Doe",
  "email": "john@example.com",
  "role": "user",
  "iat": 1234567890,
  "exp": 1234571490
}
```

**Why it's useful:**
- **Stateless:** You can get user info without querying the database
- **Performance:** No database lookup needed for basic user info
- **Microservices:** Other services can decode the token and get user info without calling the auth service

**Trade-off:** Token size increases, but you save database queries.

---

## How JWT Works in Your Code

### 1. Login Flow
```php
// LoginUserAction.php
$token = JWTAuth::attempt($credentials);
// This internally:
// 1. Validates credentials
// 2. Gets the user
// 3. Calls getJWTIdentifier() to get user ID
// 4. Calls getJWTCustomClaims() to get additional data
// 5. Creates and signs the JWT token
```

### 2. Token Usage
```php
// Client sends token in header:
Authorization: Bearer eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9...

// Middleware validates token and loads user:
auth()->user(); // User loaded from token claims
```

### 3. Token Refresh
```php
// RefreshTokenAction.php
$token = JWTAuth::refresh(JWTAuth::getToken());
// Creates new token with same user data (from getJWTIdentifier + getJWTCustomClaims)
```

---

## Sanctum vs JWT Comparison

| Feature | JWT (Current) | Sanctum |
|---------|---------------|---------|
| **Token Type** | Stateless JWT | Stateful database tokens |
| **Storage** | No database needed | Stores tokens in `personal_access_tokens` table |
| **Token Size** | Larger (contains user data) | Smaller (just token ID) |
| **Database Queries** | None (stateless) | One query per request (to validate token) |
| **Revocation** | Hard (need blacklist) | Easy (delete from database) |
| **Microservices** | Perfect (stateless) | Requires shared database or API calls |
| **User Model Methods** | `getJWTIdentifier()`, `getJWTCustomClaims()` | `createToken()`, `tokens` relationship |
| **Token Expiration** | Built into JWT | Configurable per token |

---

## If You Want to Switch to Sanctum

If you prefer Sanctum, here's what would change:

### 1. User Model Changes

**Remove:**
```php
use Tymon\JWTAuth\Contracts\JWTSubject;

class User extends Authenticatable implements JWTSubject
{
    public function getJWTIdentifier() { ... }
    public function getJWTCustomClaims() { ... }
}
```

**Add:**
```php
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens; // Instead of JWTSubject
}
```

### 2. Action Changes

**JWT (Current):**
```php
$token = JWTAuth::attempt($credentials);
JWTAuth::invalidate($token);
```

**Sanctum:**
```php
$user = User::where('email', $email)->first();
if (Hash::check($password, $user->password)) {
    $token = $user->createToken('auth-token')->plainTextToken;
}
$user->currentAccessToken()->delete(); // Logout
```

### 3. Token Response

**JWT:** Returns the full JWT token string
**Sanctum:** Returns a hashed token stored in database

---

## Recommendation

**Keep JWT if:**
- ✅ You're building microservices (stateless is better)
- ✅ You want better performance (no DB queries per request)
- ✅ You need tokens to work across multiple services
- ✅ Token size isn't a concern

**Switch to Sanctum if:**
- ✅ You need easy token revocation
- ✅ You want Laravel's built-in solution (no third-party package)
- ✅ You prefer database-backed tokens for security
- ✅ You're building a single monolithic application

---

## Current Implementation is JWT

Your codebase is **correctly configured for JWT**. The methods `getJWTIdentifier()` and `getJWTCustomClaims()` are **required** for JWT to work.

If you want to switch to Sanctum, I can help migrate the code. Otherwise, these methods are essential for your current JWT implementation.
