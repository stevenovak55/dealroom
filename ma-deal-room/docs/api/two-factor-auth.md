# Two-Factor Authentication API Documentation

**Base URL:** `/wp-json/ma-deal/v1/2fa`
**Version:** 2.0.0
**Last Updated:** 2025-10-31

## Overview

The MA Deal Room Two-Factor Authentication API provides TOTP-based 2FA with backup codes for enhanced account security.

## Rate Limiting

- **Default:** 20 req/sec, 100 req/min, 1000 req/hour
- All endpoints include rate limiting headers

Headers included in responses:
```
X-RateLimit-Limit: 20
X-RateLimit-Remaining: 15
X-RateLimit-Reset: 1699123456
```

## Authentication

All endpoints (except `/verify` and `/verify-backup`) require authentication via:
- **JWT Token:** `Authorization: Bearer {access_token}`
- **WordPress Session:** Standard WordPress cookie authentication

---

## Endpoints

### POST /2fa/enable
Enable 2FA - Step 1: Generate secret and QR code

**Authentication Required:** Yes

**Request:**
```json
{}
```

**Response (200):**
```json
{
  "success": true,
  "message": "2FA setup initiated",
  "data": {
    "secret": "JBSWY3DPEHPK3PXP",
    "qr_code": "data:image/png;base64,iVBORw0KGgoAAAANSUhEUg...",
    "backup_codes": [
      "12345678",
      "23456789",
      "34567890",
      "45678901",
      "56789012",
      "67890123",
      "78901234",
      "89012345"
    ],
    "message": "Scan the QR code with your authenticator app (Google Authenticator, Authy, etc.), then verify with a code to complete setup."
  }
}
```

**Error Responses:**
- `400` - 2FA already enabled
- `401` - Not authenticated

---

### POST /2fa/verify-setup
Enable 2FA - Step 2: Verify setup and activate

**Authentication Required:** Yes

**Request:**
```json
{
  "code": "123456"
}
```

**Response (200):**
```json
{
  "success": true,
  "message": "Two-factor authentication enabled successfully",
  "data": {
    "enabled": true
  }
}
```

**Error Responses:**
- `400` - Invalid code or missing code
- `401` - Not authenticated

---

### POST /2fa/disable
Disable 2FA for current user

**Authentication Required:** Yes

**Request:**
```json
{
  "password": "UserPassword123!"
}
```

**Response (200):**
```json
{
  "success": true,
  "message": "Two-factor authentication disabled successfully"
}
```

**Error Responses:**
- `400` - Password required or incorrect
- `401` - Not authenticated

---

### POST /2fa/verify
Verify 2FA code during login

**Authentication Required:** No (public endpoint)

**Request:**
```json
{
  "user_id": 123,
  "user_type": "custom",
  "code": "123456"
}
```

**Response (200):**
```json
{
  "success": true,
  "message": "Code verified successfully",
  "data": {
    "verified": true
  }
}
```

**Error Responses:**
- `400` - Missing parameters or invalid code
- `401` - Invalid 2FA code

---

### POST /2fa/verify-backup
Verify backup code during login

**Authentication Required:** No (public endpoint)

**Request:**
```json
{
  "user_id": 123,
  "user_type": "custom",
  "code": "12345678"
}
```

**Response (200):**
```json
{
  "success": true,
  "message": "Backup code verified successfully",
  "data": {
    "verified": true,
    "message": "Backup code accepted. Consider regenerating your backup codes."
  }
}
```

**Error Responses:**
- `400` - Missing parameters or invalid code
- `401` - Invalid backup code or already used

**Note:** Each backup code can only be used once.

---

### POST /2fa/backup-codes/regenerate
Regenerate backup codes

**Authentication Required:** Yes

**Request:**
```json
{
  "password": "UserPassword123!"
}
```

**Response (200):**
```json
{
  "success": true,
  "message": "Backup codes regenerated successfully",
  "data": {
    "backup_codes": [
      "98765432",
      "87654321",
      "76543210",
      "65432109",
      "54321098",
      "43210987",
      "32109876",
      "21098765"
    ],
    "message": "New backup codes generated. Save these in a secure location."
  }
}
```

**Error Responses:**
- `400` - Password required or incorrect
- `401` - Not authenticated

**Important:** Old backup codes are invalidated when new ones are generated.

---

### GET /2fa/status
Get 2FA status for current user

**Authentication Required:** Yes

**Response (200):**
```json
{
  "success": true,
  "data": {
    "enabled": true,
    "method": "totp"
  }
}
```

**Error Responses:**
- `401` - Not authenticated
- `404` - User not found

---

## Security Features

### TOTP (Time-based One-Time Password)
- **Algorithm:** SHA-1
- **Digits:** 6
- **Period:** 30 seconds
- **Compatible with:** Google Authenticator, Authy, Microsoft Authenticator, 1Password

### Backup Codes
- **Count:** 8 codes per set
- **Format:** 8 digits each
- **Single Use:** Each code can only be used once
- **Storage:** Hashed in database

### QR Code
- **Format:** PNG data URL (base64 encoded)
- **Protocol:** `otpauth://totp/`
- **Issuer:** MA Deal Room

---

## 2FA Setup Workflow

1. **Enable 2FA** - `POST /2fa/enable`
   - Receive secret and QR code
   - Save backup codes securely
   - Scan QR code with authenticator app

2. **Verify Setup** - `POST /2fa/verify-setup`
   - Enter 6-digit code from authenticator app
   - 2FA is activated upon successful verification

3. **Login with 2FA** - After regular login:
   - System detects 2FA is enabled
   - User enters 6-digit TOTP code or backup code
   - Verify via `/2fa/verify` or `/2fa/verify-backup`
   - Login completes after successful 2FA verification

---

## Testing Example

```bash
# 1. Login first (get access token)
curl -X POST http://localhost:8080/wp-json/ma-deal/v1/auth/login \
  -H "Content-Type: application/json" \
  -d '{
    "email": "user@example.com",
    "password": "UserPassword123!"
  }'

# 2. Enable 2FA (Step 1)
curl -X POST http://localhost:8080/wp-json/ma-deal/v1/2fa/enable \
  -H "Authorization: Bearer {access_token}" \
  -H "Content-Type: application/json"

# 3. Scan QR code with authenticator app, then verify (Step 2)
curl -X POST http://localhost:8080/wp-json/ma-deal/v1/2fa/verify-setup \
  -H "Authorization: Bearer {access_token}" \
  -H "Content-Type: application/json" \
  -d '{
    "code": "123456"
  }'

# 4. Check 2FA status
curl -X GET http://localhost:8080/wp-json/ma-deal/v1/2fa/status \
  -H "Authorization: Bearer {access_token}"

# 5. Disable 2FA (if needed)
curl -X POST http://localhost:8080/wp-json/ma-deal/v1/2fa/disable \
  -H "Authorization: Bearer {access_token}" \
  -H "Content-Type: application/json" \
  -d '{
    "password": "UserPassword123!"
  }'
```

---

**For complete examples and integration guides, see the full documentation.**
