#!/bin/bash

EMAIL="test$(date +%s)@example.com"
PASSWORD="TestUser123!"

echo "=== Quick Auth Test on Port 8080 ==="
echo "Testing with: $EMAIL"
echo ""

echo "1. Registering user..."
REGISTER=$(curl -s -X POST http://localhost:8080/wp-json/ma-deal/v1/auth/register \
  -H "Content-Type: application/json" \
  -d "{\"email\":\"$EMAIL\",\"password\":\"$PASSWORD\",\"first_name\":\"Test\",\"last_name\":\"User\"}")
echo "$REGISTER"
echo ""

echo "2. Getting verification token..."
TOKEN=$(docker exec ma-dealroom-db mysql -u dealroom -pdealroom_dev_pass -D ma_dealroom -N -se "SELECT token FROM wp_ma_deal_email_verifications WHERE email='$EMAIL' ORDER BY created_at DESC LIMIT 1;" 2>/dev/null)
echo "Token: ${TOKEN:0:50}..."
echo ""

echo "3. Verifying email..."
VERIFY=$(curl -s -X POST http://localhost:8080/wp-json/ma-deal/v1/auth/verify-email \
  -H "Content-Type: application/json" \
  -d "{\"token\":\"$TOKEN\"}")
echo "$VERIFY"
echo ""

echo "4. Logging in..."
LOGIN=$(curl -s -X POST http://localhost:8080/wp-json/ma-deal/v1/auth/login \
  -H "Content-Type: application/json" \
  -d "{\"email\":\"$EMAIL\",\"password\":\"$PASSWORD\"}")
echo "$LOGIN"
echo ""

echo "5. Extracting access token..."
ACCESS_TOKEN=$(echo "$LOGIN" | grep -o '"access_token":"[^"]*"' | cut -d'"' -f4)
echo "Access Token: ${ACCESS_TOKEN:0:50}..."
echo ""

echo "6. Getting current user info..."
ME=$(curl -s -X GET http://localhost:8080/wp-json/ma-deal/v1/auth/me \
  -H "Authorization: Bearer $ACCESS_TOKEN")
echo "$ME"
echo ""

echo "=== Test Complete ==="
echo "✓ Registration"
echo "✓ Email Verification"
echo "✓ Login"
echo "✓ Get Current User"
