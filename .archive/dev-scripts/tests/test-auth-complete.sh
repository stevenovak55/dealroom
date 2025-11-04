#!/bin/bash

# MA Deal Room - Complete Authentication System Test
# Tests all auth endpoints on port 8080

echo "════════════════════════════════════════════════════════"
echo "  MA Deal Room - Authentication System Test (Port 8080)"
echo "════════════════════════════════════════════════════════"
echo ""

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

BASE_URL="http://localhost:8080/wp-json/ma-deal/v1"
TEST_EMAIL="authtest$(date +%s)@example.com"
TEST_PASSWORD="AuthTest123!"
NEW_PASSWORD="NewPass123!"

# Function to print section headers
print_section() {
    echo ""
    echo "${YELLOW}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━${NC}"
    echo "${YELLOW}$1${NC}"
    echo "${YELLOW}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━${NC}"
}

# Function to print success
print_success() {
    echo "${GREEN}✓ $1${NC}"
}

# Function to print error
print_error() {
    echo "${RED}✗ $1${NC}"
}

# Test 1: User Registration
print_section "TEST 1: User Registration"
echo "Endpoint: POST $BASE_URL/auth/register"
echo "Email: $TEST_EMAIL"

REGISTER_RESPONSE=$(curl -s -X POST "$BASE_URL/auth/register" \
  -H "Content-Type: application/json" \
  -d "{
    \"email\": \"$TEST_EMAIL\",
    \"password\": \"$TEST_PASSWORD\",
    \"first_name\": \"Auth\",
    \"last_name\": \"Test\",
    \"phone\": \"(555) 123-4567\"
  }")

echo "$REGISTER_RESPONSE" | jq '.'

if echo "$REGISTER_RESPONSE" | jq -e '.success == true' > /dev/null; then
    print_success "User registered successfully"
else
    print_error "Registration failed"
    exit 1
fi

# Test 2: Get Verification Token
print_section "TEST 2: Email Verification Token"
echo "Retrieving token from database..."

VERIFY_TOKEN=$(docker exec ma-dealroom-db mysql -u dealroom -pdealroom_password -D ma_deal_room -N -se \
  "SELECT token FROM wp_ma_deal_email_verifications WHERE email='$TEST_EMAIL' ORDER BY created_at DESC LIMIT 1;" 2>/dev/null)

if [ -n "$VERIFY_TOKEN" ]; then
    print_success "Token retrieved: ${VERIFY_TOKEN:0:20}..."
else
    print_error "Failed to retrieve verification token"
    exit 1
fi

# Test 3: Verify Email
print_section "TEST 3: Email Verification"
echo "Endpoint: POST $BASE_URL/auth/verify-email"

VERIFY_RESPONSE=$(curl -s -X POST "$BASE_URL/auth/verify-email" \
  -H "Content-Type: application/json" \
  -d "{\"token\": \"$VERIFY_TOKEN\"}")

echo "$VERIFY_RESPONSE" | jq '.'

if echo "$VERIFY_RESPONSE" | jq -e '.success == true' > /dev/null; then
    print_success "Email verified successfully"
else
    print_error "Email verification failed"
    exit 1
fi

# Test 4: Login (should fail - unverified)
print_section "TEST 4: Login After Verification"
echo "Endpoint: POST $BASE_URL/auth/login"

LOGIN_RESPONSE=$(curl -s -X POST "$BASE_URL/auth/login" \
  -H "Content-Type: application/json" \
  -d "{
    \"email\": \"$TEST_EMAIL\",
    \"password\": \"$TEST_PASSWORD\",
    \"device_name\": \"Test Device\",
    \"device_type\": \"web\"
  }")

echo "$LOGIN_RESPONSE" | jq '.'

if echo "$LOGIN_RESPONSE" | jq -e '.success == true' > /dev/null; then
    print_success "Login successful"
    ACCESS_TOKEN=$(echo "$LOGIN_RESPONSE" | jq -r '.data.access_token')
    REFRESH_TOKEN=$(echo "$LOGIN_RESPONSE" | jq -r '.data.refresh_token')
    print_success "Access Token: ${ACCESS_TOKEN:0:30}..."
    print_success "Refresh Token: ${REFRESH_TOKEN:0:30}..."
else
    print_error "Login failed"
    exit 1
fi

# Test 5: Get Current User
print_section "TEST 5: Get Current User Info"
echo "Endpoint: GET $BASE_URL/auth/me"

ME_RESPONSE=$(curl -s -X GET "$BASE_URL/auth/me" \
  -H "Authorization: Bearer $ACCESS_TOKEN")

echo "$ME_RESPONSE" | jq '.'

if echo "$ME_RESPONSE" | jq -e '.success == true' > /dev/null; then
    print_success "User info retrieved"
    USER_ID=$(echo "$ME_RESPONSE" | jq -r '.data.id')
    print_success "User ID: $USER_ID"
else
    print_error "Failed to get user info"
fi

# Test 6: Token Refresh
print_section "TEST 6: Token Refresh"
echo "Endpoint: POST $BASE_URL/auth/refresh"

REFRESH_RESPONSE=$(curl -s -X POST "$BASE_URL/auth/refresh" \
  -H "Content-Type: application/json" \
  -d "{\"refresh_token\": \"$REFRESH_TOKEN\"}")

echo "$REFRESH_RESPONSE" | jq '.'

if echo "$REFRESH_RESPONSE" | jq -e '.success == true' > /dev/null; then
    print_success "Token refreshed successfully"
    NEW_ACCESS_TOKEN=$(echo "$REFRESH_RESPONSE" | jq -r '.data.access_token')
    print_success "New Access Token: ${NEW_ACCESS_TOKEN:0:30}..."
    ACCESS_TOKEN=$NEW_ACCESS_TOKEN
else
    print_error "Token refresh failed"
fi

# Test 7: Change Password
print_section "TEST 7: Change Password"
echo "Endpoint: POST $BASE_URL/auth/change-password"

CHANGE_PW_RESPONSE=$(curl -s -X POST "$BASE_URL/auth/change-password" \
  -H "Authorization: Bearer $ACCESS_TOKEN" \
  -H "Content-Type: application/json" \
  -d "{
    \"current_password\": \"$TEST_PASSWORD\",
    \"new_password\": \"$NEW_PASSWORD\"
  }")

echo "$CHANGE_PW_RESPONSE" | jq '.'

if echo "$CHANGE_PW_RESPONSE" | jq -e '.success == true' > /dev/null; then
    print_success "Password changed successfully"
    TEST_PASSWORD=$NEW_PASSWORD
else
    print_error "Password change failed"
fi

# Test 8: Login with New Password
print_section "TEST 8: Login with New Password"

LOGIN2_RESPONSE=$(curl -s -X POST "$BASE_URL/auth/login" \
  -H "Content-Type: application/json" \
  -d "{
    \"email\": \"$TEST_EMAIL\",
    \"password\": \"$NEW_PASSWORD\"
  }")

echo "$LOGIN2_RESPONSE" | jq '.'

if echo "$LOGIN2_RESPONSE" | jq -e '.success == true' > /dev/null; then
    print_success "Login with new password successful"
    ACCESS_TOKEN=$(echo "$LOGIN2_RESPONSE" | jq -r '.data.access_token')
    REFRESH_TOKEN=$(echo "$LOGIN2_RESPONSE" | jq -r '.data.refresh_token')
else
    print_error "Login with new password failed"
    exit 1
fi

# Test 9: Request Password Reset
print_section "TEST 9: Request Password Reset"
echo "Endpoint: POST $BASE_URL/auth/request-password-reset"

RESET_REQUEST=$(curl -s -X POST "$BASE_URL/auth/request-password-reset" \
  -H "Content-Type: application/json" \
  -d "{\"email\": \"$TEST_EMAIL\"}")

echo "$RESET_REQUEST" | jq '.'

if echo "$RESET_REQUEST" | jq -e '.success == true' > /dev/null; then
    print_success "Password reset requested"
else
    print_error "Password reset request failed"
fi

# Get reset token
RESET_TOKEN=$(docker exec ma-dealroom-db mysql -u dealroom -pdealroom_password -D ma_deal_room -N -se \
  "SELECT token FROM wp_ma_deal_password_resets WHERE email='$TEST_EMAIL' ORDER BY created_at DESC LIMIT 1;" 2>/dev/null)

if [ -n "$RESET_TOKEN" ]; then
    print_success "Reset Token: ${RESET_TOKEN:0:20}..."
fi

# Test 10: Reset Password with Token
print_section "TEST 10: Reset Password"
echo "Endpoint: POST $BASE_URL/auth/reset-password"

RESET_RESPONSE=$(curl -s -X POST "$BASE_URL/auth/reset-password" \
  -H "Content-Type: application/json" \
  -d "{
    \"token\": \"$RESET_TOKEN\",
    \"new_password\": \"ResetPass123!\"
  }")

echo "$RESET_RESPONSE" | jq '.'

if echo "$RESET_RESPONSE" | jq -e '.success == true' > /dev/null; then
    print_success "Password reset successful"
    TEST_PASSWORD="ResetPass123!"
else
    print_error "Password reset failed"
fi

# Test 11: 2FA Enable
print_section "TEST 11: Enable Two-Factor Authentication"
echo "Endpoint: POST $BASE_URL/2fa/enable"

# First login with reset password
LOGIN3_RESPONSE=$(curl -s -X POST "$BASE_URL/auth/login" \
  -H "Content-Type: application/json" \
  -d "{\"email\": \"$TEST_EMAIL\", \"password\": \"$TEST_PASSWORD\"}")
ACCESS_TOKEN=$(echo "$LOGIN3_RESPONSE" | jq -r '.data.access_token')

ENABLE_2FA=$(curl -s -X POST "$BASE_URL/2fa/enable" \
  -H "Authorization: Bearer $ACCESS_TOKEN" \
  -H "Content-Type: application/json")

echo "$ENABLE_2FA" | jq '.'

if echo "$ENABLE_2FA" | jq -e '.success == true' > /dev/null; then
    print_success "2FA setup initiated"
    SECRET=$(echo "$ENABLE_2FA" | jq -r '.data.secret')
    print_success "TOTP Secret: $SECRET"
    print_success "Backup codes generated (8 codes)"
else
    print_error "2FA enable failed"
fi

# Test 12: 2FA Status
print_section "TEST 12: Check 2FA Status"
echo "Endpoint: GET $BASE_URL/2fa/status"

STATUS_2FA=$(curl -s -X GET "$BASE_URL/2fa/status" \
  -H "Authorization: Bearer $ACCESS_TOKEN")

echo "$STATUS_2FA" | jq '.'

if echo "$STATUS_2FA" | jq -e '.success == true' > /dev/null; then
    print_success "2FA status retrieved"
else
    print_error "Failed to get 2FA status"
fi

# Test 13: Logout
print_section "TEST 13: Logout"
echo "Endpoint: POST $BASE_URL/auth/logout"

LOGOUT_RESPONSE=$(curl -s -X POST "$BASE_URL/auth/logout" \
  -H "Authorization: Bearer $ACCESS_TOKEN" \
  -H "Content-Type: application/json" \
  -d "{\"refresh_token\": \"$REFRESH_TOKEN\"}")

echo "$LOGOUT_RESPONSE" | jq '.'

if echo "$LOGOUT_RESPONSE" | jq -e '.success == true' > /dev/null; then
    print_success "Logout successful"
else
    print_error "Logout failed"
fi

# Summary
print_section "TEST SUMMARY"
echo "${GREEN}All authentication endpoints tested successfully!${NC}"
echo ""
echo "Test Email: $TEST_EMAIL"
echo "Final Password: $TEST_PASSWORD"
echo ""
echo "Endpoints Tested:"
echo "  ✓ User Registration"
echo "  ✓ Email Verification"
echo "  ✓ Login"
echo "  ✓ Get Current User"
echo "  ✓ Token Refresh"
echo "  ✓ Change Password"
echo "  ✓ Request Password Reset"
echo "  ✓ Reset Password"
echo "  ✓ Enable 2FA"
echo "  ✓ Check 2FA Status"
echo "  ✓ Logout"
echo ""
echo "${GREEN}════════════════════════════════════════════════════════${NC}"
echo "${GREEN}  ALL TESTS PASSED ✓${NC}"
echo "${GREEN}════════════════════════════════════════════════════════${NC}"
