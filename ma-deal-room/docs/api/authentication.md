# Authentication API Documentation

**Base URL:** `/wp-json/ma-deal/v1/auth`  
**Version:** 2.0.0  
**Last Updated:** 2025-10-31

## Overview

The MA Deal Room Authentication API provides JWT-based authentication for custom users alongside WordPress cookie-based authentication.

## Rate Limiting

- **Register/Login/Password Reset:** Strict (5 req/sec, 20 req/min, 100 req/hour)
- **Other endpoints:** Default (20 req/sec, 100 req/min, 1000 req/hour)

Headers included in responses:
```
X-RateLimit-Limit: 20
X-RateLimit-Remaining: 15
X-RateLimit-Reset: 1699123456
```

## Authentication

### JWT Tokens
- **Access Token:** 15 minutes (use in `Authorization: Bearer {token}` header)
- **Refresh Token:** 7 days (use to get new access tokens)

---

## Endpoints

### POST /auth/register
Register a new custom user.

**Request:**
```json
{
  "email": "user@example.com",
  "password": "SecurePassword123!",
  "first_name": "John",
  "last_name": "Doe",
  "phone": "+1-555-0123"
}
```

**Response (201):**
```json
{
  "success": true,
  "message": "User registered successfully",
  "data": {
    "user": { "id": 123, "email": "user@example.com", "status": "pending_verification" },
    "message": "Please check your email to verify your account."
  }
}
```

---

### POST /auth/login
Authenticate and receive JWT tokens.

**Request:**
```json
{
  "email": "user@example.com",
  "password": "SecurePassword123!",
  "device_name": "Chrome on Windows",
  "device_type": "web"
}
```

**Response (200):**
```json
{
  "success": true,
  "message": "Login successful",
  "data": {
    "access_token": "eyJhbGc...",
    "refresh_token": "dGhpc2lz...",
    "expires_in": 900,
    "user": { "id": 123, "email": "user@example.com", "email_verified": true }
  }
}
```

---

### POST /auth/logout
Invalidate session.

**Headers:** `Authorization: Bearer {access_token}`

**Request:**
```json
{
  "refresh_token": "dGhpc2lz..."
}
```

**Response (200):**
```json
{
  "success": true,
  "message": "Logout successful"
}
```

---

### POST /auth/refresh
Get new access token.

**Request:**
```json
{
  "refresh_token": "dGhpc2lz..."
}
```

**Response (200):**
```json
{
  "success": true,
  "data": {
    "access_token": "eyJhbGc...",
    "expires_in": 900
  }
}
```

---

### POST /auth/verify-email
Verify email address.

**Request:**
```json
{
  "token": "abc123..."
}
```

**Response (200):**
```json
{
  "success": true,
  "message": "Email verified successfully"
}
```

---

### POST /auth/resend-verification
Resend verification email. **Requires authentication.**

**Response (200):**
```json
{
  "success": true,
  "message": "Verification email sent"
}
```

---

### POST /auth/request-password-reset
Request password reset link.

**Request:**
```json
{
  "email": "user@example.com"
}
```

**Response (200):**
```json
{
  "success": true,
  "message": "If an account exists, you will receive reset instructions."
}
```

---

### POST /auth/reset-password
Reset password with token.

**Request:**
```json
{
  "token": "xyz789...",
  "new_password": "NewSecurePass123!"
}
```

**Response (200):**
```json
{
  "success": true,
  "message": "Password reset successfully"
}
```

---

### POST /auth/change-password
Change password for logged-in user. **Requires authentication.**

**Request:**
```json
{
  "current_password": "OldPassword123!",
  "new_password": "NewSecurePass123!"
}
```

**Response (200):**
```json
{
  "success": true,
  "message": "Password changed successfully"
}
```

---

### GET /auth/me
Get current user info. **Requires authentication.**

**Response (200):**
```json
{
  "success": true,
  "data": {
    "id": 123,
    "email": "user@example.com",
    "first_name": "John",
    "last_name": "Doe",
    "email_verified": true,
    "two_factor_enabled": false,
    "user_type": "custom"
  }
}
```

---

### POST /auth/verify-token
Validate an access token.

**Request:**
```json
{
  "token": "eyJhbGc..."
}
```

**Response (200):**
```json
{
  "success": true,
  "data": {
    "valid": true,
    "user_id": 123,
    "user_type": "custom"
  }
}
```

---

## Security

### Password Requirements
- Minimum 12 characters
- Uppercase, lowercase, number, special character required

### Account Lockout
- 5 failed attempts → 30-minute lockout

### Token Expiration
- Access: 15 minutes
- Refresh: 7 days
- Email verification: 24 hours
- Password reset: 1 hour

---

## Testing Example

```bash
# 1. Register
curl -X POST http://localhost:8080/wp-json/ma-deal/v1/auth/register \
  -H "Content-Type: application/json" \
  -d '{
    "email": "test@example.com",
    "password": "TestPass123!",
    "first_name": "Test",
    "last_name": "User"
  }'

# 2. Login
curl -X POST http://localhost:8080/wp-json/ma-deal/v1/auth/login \
  -H "Content-Type: application/json" \
  -d '{
    "email": "test@example.com",
    "password": "TestPass123!"
  }'

# 3. Get user info
curl -X GET http://localhost:8080/wp-json/ma-deal/v1/auth/me \
  -H "Authorization: Bearer {access_token}"

# 4. Logout
curl -X POST http://localhost:8080/wp-json/ma-deal/v1/auth/logout \
  -H "Authorization: Bearer {access_token}" \
  -H "Content-Type: application/json" \
  -d '{
    "refresh_token": "{refresh_token}"
  }'
```

---

**For complete examples and integration guides, see the full documentation.**
