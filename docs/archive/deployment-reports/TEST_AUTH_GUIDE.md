# Authentication System Testing Guide (Port 8080)

**All endpoints are ready and working on port 8080!**

Base URL: `http://localhost:8080/wp-json/ma-deal/v1`

---

## Test 1: User Registration

```bash
curl -X POST http://localhost:8080/wp-json/ma-deal/v1/auth/register \
  -H "Content-Type: application/json" \
  -d '{
    "email": "YOUR_EMAIL@example.com",
    "password": "YourPass123!",
    "first_name": "Test",
    "last_name": "User",
    "phone": "(555) 123-4567"
  }'
```

**Expected Response:**
```json
{
  "success": true,
  "message": "User registered successfully",
  "data": {
    "user": {
      "id": 12,
      "email": "YOUR_EMAIL@example.com",
      "status": "pending_verification"
    }
  }
}
```

---

## Test 2: Get Verification Token

Since emails aren't configured in dev, get the token from database:

```bash
docker exec ma-dealroom-db mysql -u dealroom -pdealroom_password -D ma_deal_room \
  -e "SELECT email, token, expires_at FROM wp_ma_deal_email_verifications ORDER BY created_at DESC LIMIT 1;"
```

**Copy the token value.**

---

## Test 3: Verify Email

Replace `YOUR_TOKEN` with the token from step 2:

```bash
curl -X POST http://localhost:8080/wp-json/ma-deal/v1/auth/verify-email \
  -H "Content-Type: application/json" \
  -d '{"token": "YOUR_TOKEN"}'
```

**Expected Response:**
```json
{
  "success": true,
  "message": "Email verified successfully"
}
```

---

## Test 4: Login

```bash
curl -X POST http://localhost:8080/wp-json/ma-deal/v1/auth/login \
  -H "Content-Type: application/json" \
  -d '{
    "email": "YOUR_EMAIL@example.com",
    "password": "YourPass123!",
    "device_name": "Test Device",
    "device_type": "web"
  }'
```

**Expected Response:**
```json
{
  "success": true,
  "message": "Login successful",
  "data": {
    "access_token": "eyJhbGciOiJIUz...",
    "refresh_token": "abc123...",
    "expires_in": 900,
    "user": {
      "id": 12,
      "email": "YOUR_EMAIL@example.com",
      "email_verified": true
    }
  }
}
```

**Save the access_token and refresh_token!**

---

## Test 5: Get Current User

Replace `YOUR_ACCESS_TOKEN` with the access_token from step 4:

```bash
curl -X GET http://localhost:8080/wp-json/ma-deal/v1/auth/me \
  -H "Authorization: Bearer YOUR_ACCESS_TOKEN"
```

**Expected Response:**
```json
{
  "success": true,
  "data": {
    "id": 12,
    "email": "YOUR_EMAIL@example.com",
    "first_name": "Test",
    "last_name": "User",
    "two_factor_enabled": false
  }
}
```

---

## Test 6: Refresh Token

```bash
curl -X POST http://localhost:8080/wp-json/ma-deal/v1/auth/refresh \
  -H "Content-Type: application/json" \
  -d '{"refresh_token": "YOUR_REFRESH_TOKEN"}'
```

**Expected Response:**
```json
{
  "success": true,
  "data": {
    "access_token": "eyJhbGciOiJIUz...",
    "expires_in": 900
  }
}
```

---

## Test 7: Change Password

```bash
curl -X POST http://localhost:8080/wp-json/ma-deal/v1/auth/change-password \
  -H "Authorization: Bearer YOUR_ACCESS_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "current_password": "YourPass123!",
    "new_password": "NewPass123!"
  }'
```

**Expected Response:**
```json
{
  "success": true,
  "message": "Password changed successfully"
}
```

---

## Test 8: Request Password Reset

```bash
curl -X POST http://localhost:8080/wp-json/ma-deal/v1/auth/request-password-reset \
  -H "Content-Type: application/json" \
  -d '{"email": "YOUR_EMAIL@example.com"}'
```

**Get reset token from database:**
```bash
docker exec ma-dealroom-db mysql -u dealroom -pdealroom_password -D ma_deal_room \
  -e "SELECT email, token FROM wp_ma_deal_password_resets ORDER BY created_at DESC LIMIT 1;"
```

---

## Test 9: Reset Password

```bash
curl -X POST http://localhost:8080/wp-json/ma-deal/v1/auth/reset-password \
  -H "Content-Type: application/json" \
  -d '{
    "token": "YOUR_RESET_TOKEN",
    "new_password": "ResetPass123!"
  }'
```

---

## Test 10: Enable 2FA

```bash
curl -X POST http://localhost:8080/wp-json/ma-deal/v1/2fa/enable \
  -H "Authorization: Bearer YOUR_ACCESS_TOKEN" \
  -H "Content-Type: application/json"
```

**Expected Response:**
```json
{
  "success": true,
  "data": {
    "secret": "JBSWY3DPEHPK3PXP",
    "qr_code": "data:image/png;base64,...",
    "backup_codes": [
      "12345678",
      "23456789",
      ...
    ]
  }
}
```

**Save the secret and backup codes!**

---

## Test 11: Check 2FA Status

```bash
curl -X GET http://localhost:8080/wp-json/ma-deal/v1/2fa/status \
  -H "Authorization: Bearer YOUR_ACCESS_TOKEN"
```

---

## Test 12: Logout

```bash
curl -X POST http://localhost:8080/wp-json/ma-deal/v1/auth/logout \
  -H "Authorization: Bearer YOUR_ACCESS_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{"refresh_token": "YOUR_REFRESH_TOKEN"}'
```

---

## Quick Test Flow

Here's a complete flow in one go:

```bash
# 1. Register
curl -X POST http://localhost:8080/wp-json/ma-deal/v1/auth/register \
  -H "Content-Type: application/json" \
  -d '{"email":"quicktest@example.com","password":"QuickTest123!","first_name":"Quick","last_name":"Test"}'

# 2. Get token
TOKEN=$(docker exec ma-dealroom-db mysql -u dealroom -pdealroom_password -D ma_deal_room -N -se "SELECT token FROM wp_ma_deal_email_verifications WHERE email='quicktest@example.com' ORDER BY created_at DESC LIMIT 1;" 2>/dev/null)
echo "Token: $TOKEN"

# 3. Verify
curl -X POST http://localhost:8080/wp-json/ma-deal/v1/auth/verify-email \
  -H "Content-Type: application/json" \
  -d "{\"token\":\"$TOKEN\"}"

# 4. Login
curl -X POST http://localhost:8080/wp-json/ma-deal/v1/auth/login \
  -H "Content-Type: application/json" \
  -d '{"email":"quicktest@example.com","password":"QuickTest123!"}'
```

---

## All Endpoints Ready ✓

- ✅ POST /auth/register
- ✅ POST /auth/verify-email
- ✅ POST /auth/login
- ✅ GET /auth/me
- ✅ POST /auth/refresh
- ✅ POST /auth/logout
- ✅ POST /auth/change-password
- ✅ POST /auth/request-password-reset
- ✅ POST /auth/reset-password
- ✅ POST /2fa/enable
- ✅ POST /2fa/verify-setup
- ✅ POST /2fa/verify
- ✅ POST /2fa/verify-backup
- ✅ POST /2fa/disable
- ✅ POST /2fa/backup-codes/regenerate
- ✅ GET /2fa/status

**All systems are ready for testing on port 8080!**
