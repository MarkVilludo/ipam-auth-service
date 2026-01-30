# Auth Service API Documentation

Base URL: `http://localhost:8000/api/auth` (via Gateway) or `http://localhost:9002/api` (direct)

All endpoints return JSON responses with the following structure:
- Success: `{ "success": true, "data": {...}, "message": "..." }`
- Error: `{ "success": false, "errors": {...} }` or `{ "success": false, "message": "..." }`

---

## 1. Register User

Register a new user account. New users are automatically assigned the 'user' role.

**Endpoint:** `POST /api/auth/register`

**Authentication:** Not required

**Request Body:**
```json
{
  "name": "John Doe",
  "email": "john.doe@example.com",
  "password": "password123"
}
```

**cURL Example:**
```bash
curl -X POST http://localhost:8000/api/auth/register \
  -H "Content-Type: application/json" \
  -d '{
    "name": "John Doe",
    "email": "john.doe@example.com",
    "password": "password123"
  }'
```

**Success Response (201):**
```json
{
  "success": true,
  "message": "User registered successfully",
  "data": {
    "user": {
      "id": 1,
      "name": "John Doe",
      "email": "john.doe@example.com",
      "role": "user"
    },
    "token": "eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9...",
    "token_type": "bearer",
    "expires_in": 3600
  }
}
```

**Error Response (422):**
```json
{
  "success": false,
  "errors": {
    "email": ["The email has already been taken."],
    "password": ["The password must be at least 8 characters."]
  }
}
```

---

## 2. Login

Authenticate user and receive JWT token.

**Endpoint:** `POST /api/auth/login`

**Authentication:** Not required

**Request Body:**
```json
{
  "email": "john.doe@example.com",
  "password": "password123"
}
```

**cURL Example:**
```bash
curl -X POST http://localhost:8000/api/auth/login \
  -H "Content-Type: application/json" \
  -d '{
    "email": "john.doe@example.com",
    "password": "password123"
  }'
```

**Success Response (200):**
```json
{
  "success": true,
  "message": "Login successful",
  "data": {
    "user": {
      "id": 1,
      "name": "John Doe",
      "email": "john.doe@example.com",
      "role": "user"
    },
    "token": "eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9...",
    "token_type": "bearer",
    "expires_in": 3600
  }
}
```

**Error Response (401):**
```json
{
  "success": false,
  "message": "Invalid credentials"
}
```

**Error Response (422):**
```json
{
  "success": false,
  "errors": {
    "email": ["The email field is required."],
    "password": ["The password field is required."]
  }
}
```

---

## 3. Refresh Token

Refresh the JWT token to extend the session.

**Endpoint:** `POST /api/auth/refresh`

**Authentication:** Required (Bearer token)

**Headers:**
```
Authorization: Bearer {token}
```

**cURL Example:**
```bash
curl -X POST http://localhost:8000/api/auth/refresh \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9..."
```

**Success Response (200):**
```json
{
  "success": true,
  "message": "Token refreshed successfully",
  "data": {
    "user": {
      "id": 1,
      "name": "John Doe",
      "email": "john.doe@example.com",
      "role": "user"
    },
    "token": "eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9...",
    "token_type": "bearer",
    "expires_in": 3600
  }
}
```

**Error Response (401):**
```json
{
  "success": false,
  "message": "Could not refresh token"
}
```

---

## 4. Get Authenticated User

Get the currently authenticated user's information.

**Endpoint:** `GET /api/auth/me`

**Authentication:** Required (Bearer token)

**Headers:**
```
Authorization: Bearer {token}
```

**cURL Example:**
```bash
curl -X GET http://localhost:8000/api/auth/me \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9..."
```

**Success Response (200):**
```json
{
  "success": true,
  "data": {
    "id": 1,
    "name": "John Doe",
    "email": "john.doe@example.com",
    "role": "user"
  }
}
```

**Error Response (404):**
```json
{
  "success": false,
  "message": "User not found"
}
```

**Error Response (401):**
```json
{
  "success": false,
  "message": "Unauthenticated"
}
```

---

## 5. Logout

Invalidate the current JWT token and log out the user.

**Endpoint:** `POST /api/auth/logout`

**Authentication:** Required (Bearer token)

**Headers:**
```
Authorization: Bearer {token}
```

**cURL Example:**
```bash
curl -X POST http://localhost:8000/api/auth/logout \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9..."
```

**Success Response (200):**
```json
{
  "success": true,
  "message": "Successfully logged out"
}
```

**Error Response (500):**
```json
{
  "success": false,
  "message": "Failed to logout, please try again"
}
```

**Error Response (401):**
```json
{
  "success": false,
  "message": "Unauthenticated"
}
```

---

## Complete Authentication Flow Example

### Step 1: Register a new user
```bash
curl -X POST http://localhost:8000/api/auth/register \
  -H "Content-Type: application/json" \
  -d '{
    "name": "Jane Smith",
    "email": "jane.smith@example.com",
    "password": "securepassword123"
  }'
```

**Response:**
```json
{
  "success": true,
  "message": "User registered successfully",
  "data": {
    "user": {
      "id": 2,
      "name": "Jane Smith",
      "email": "jane.smith@example.com",
      "role": "user"
    },
    "token": "eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJpc3MiOiJodHRwOi8vbG9jYWxob3N0OjgwMDAvYXV0aCIsImlhdCI6MTcwNjEyMzQ1NiwiZXhwIjoxNzA2MTI3MDU2LCJuYmYiOjE3MDYxMjM0NTYsImp0aSI6IjEyMzQ1Njc4OTAiLCJzdWIiOiIyIiwibmFtZSI6IkphbmUgU21pdGgiLCJlbWFpbCI6ImphbmUuc21pdGhAZXhhbXBsZS5jb20iLCJyb2xlIjoidXNlciJ9.xyz123...",
    "token_type": "bearer",
    "expires_in": 3600
  }
}
```

### Step 2: Use the token for authenticated requests
```bash
# Save token to variable (bash)
TOKEN="eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJpc3MiOiJodHRwOi8vbG9jYWxob3N0OjgwMDAvYXV0aCIsImlhdCI6MTcwNjEyMzQ1NiwiZXhwIjoxNzA2MTI3MDU2LCJuYmYiOjE3MDYxMjM0NTYsImp0aSI6IjEyMzQ1Njc4OTAiLCJzdWIiOiIyIiwibmFtZSI6IkphbmUgU21pdGgiLCJlbWFpbCI6ImphbmUuc21pdGhAZXhhbXBsZS5jb20iLCJyb2xlIjoidXNlciJ9.xyz123..."

# Get user info
curl -X GET http://localhost:8000/api/auth/me \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer $TOKEN"
```

### Step 3: Refresh token before expiration
```bash
curl -X POST http://localhost:8000/api/auth/refresh \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer $TOKEN"
```

### Step 4: Logout
```bash
curl -X POST http://localhost:8000/api/auth/logout \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer $TOKEN"
```

---

## Error Codes

| Status Code | Description |
|------------|-------------|
| 200 | Success |
| 201 | Created (Registration successful) |
| 401 | Unauthorized (Invalid credentials or missing/invalid token) |
| 404 | Not Found (User not found) |
| 422 | Validation Error (Invalid input data) |
| 500 | Internal Server Error |

---

## Notes

1. **Token Expiration**: Tokens expire after 60 minutes (3600 seconds) by default. Use the refresh endpoint to extend the session.

2. **Role Management**: 
   - New users are automatically assigned the 'user' role
   - To assign 'super_admin' role, use Spatie's role management:
     ```php
     $user->assignRole('super_admin');
     ```

3. **Token Storage**: Store the token securely and include it in the `Authorization` header for all protected endpoints.

4. **Gateway vs Direct Access**:
   - Via Gateway: `http://localhost:8000/api/auth/*`
   - Direct to Auth Service: `http://localhost:9002/api/*`

5. **CORS**: The gateway is configured to handle CORS for frontend applications.
