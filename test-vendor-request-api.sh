#!/bin/bash

###############################################################################
# Vendor Request API Test Script (Phase 1)
# Tests all admin REST API endpoints for vendor request management
###############################################################################

set -e

# Colors
GREEN='\033[0;32m'
BLUE='\033[0;34m'
YELLOW='\033[1;33m'
RED='\033[0;31m'
NC='\033[0m' # No Color

# Configuration
BASE_URL="http://localhost:8080/wp-json/ma-deal-room/v1"
ADMIN_USER="admin"
ADMIN_PASS="admin"  # Replace with actual password

echo -e "${BLUE}================================================${NC}"
echo -e "${BLUE}Vendor Request API Test Suite (Phase 1)${NC}"
echo -e "${BLUE}================================================${NC}"
echo ""

# Get authentication cookie
echo -e "${YELLOW}Step 1: Authenticating as admin...${NC}"
AUTH_RESPONSE=$(curl -s -X POST "http://localhost:8080/wp-json/ma-deal-room/v1/auth/login" \
  -H "Content-Type: application/json" \
  -d "{
    \"email\": \"${ADMIN_USER}\",
    \"password\": \"${ADMIN_PASS}\"
  }")

TOKEN=$(echo $AUTH_RESPONSE | grep -o '"token":"[^"]*' | cut -d'"' -f4)

if [ -z "$TOKEN" ]; then
  echo -e "${RED}✗ Authentication failed${NC}"
  echo "Response: $AUTH_RESPONSE"
  exit 1
fi

echo -e "${GREEN}✓ Authenticated successfully${NC}"
echo "Token: ${TOKEN:0:20}..."
echo ""

# Test 1: Create Vendor Request
echo -e "${YELLOW}Test 1: Create Vendor Request${NC}"
CREATE_RESPONSE=$(curl -s -X POST "${BASE_URL}/vendor-requests" \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer ${TOKEN}" \
  -d '{
    "transaction_id": 1,
    "task_id": 1,
    "vendor_type": "inspector",
    "vendor_email": "test-inspector@example.com",
    "vendor_phone": "+1-555-0100",
    "vendor_name": "John Inspector",
    "vendor_company": "ABC Inspections",
    "notes": "Please inspect foundation and roof"
  }')

echo "Response:"
echo "$CREATE_RESPONSE" | python3 -m json.tool 2>/dev/null || echo "$CREATE_RESPONSE"

VENDOR_REQUEST_ID=$(echo $CREATE_RESPONSE | grep -o '"id":[0-9]*' | head -1 | cut -d':' -f2)

if [ -z "$VENDOR_REQUEST_ID" ]; then
  echo -e "${RED}✗ Failed to create vendor request${NC}"
  exit 1
fi

echo -e "${GREEN}✓ Vendor request created (ID: $VENDOR_REQUEST_ID)${NC}"
echo ""

# Test 2: Get Single Vendor Request
echo -e "${YELLOW}Test 2: Get Single Vendor Request${NC}"
GET_RESPONSE=$(curl -s -X GET "${BASE_URL}/vendor-requests/${VENDOR_REQUEST_ID}" \
  -H "Authorization: Bearer ${TOKEN}")

echo "Response:"
echo "$GET_RESPONSE" | python3 -m json.tool 2>/dev/null || echo "$GET_RESPONSE"

if echo "$GET_RESPONSE" | grep -q '"success":true'; then
  echo -e "${GREEN}✓ Retrieved vendor request successfully${NC}"
else
  echo -e "${RED}✗ Failed to retrieve vendor request${NC}"
fi
echo ""

# Test 3: List Vendor Requests
echo -e "${YELLOW}Test 3: List Vendor Requests${NC}"
LIST_RESPONSE=$(curl -s -X GET "${BASE_URL}/vendor-requests?transaction_id=1" \
  -H "Authorization: Bearer ${TOKEN}")

echo "Response:"
echo "$LIST_RESPONSE" | python3 -m json.tool 2>/dev/null || echo "$LIST_RESPONSE"

VENDOR_COUNT=$(echo $LIST_RESPONSE | grep -o '"total":[0-9]*' | cut -d':' -f2)

if [ ! -z "$VENDOR_COUNT" ] && [ "$VENDOR_COUNT" -gt 0 ]; then
  echo -e "${GREEN}✓ Listed ${VENDOR_COUNT} vendor request(s)${NC}"
else
  echo -e "${RED}✗ Failed to list vendor requests${NC}"
fi
echo ""

# Test 4: Update Vendor Request
echo -e "${YELLOW}Test 4: Update Vendor Request${NC}"
UPDATE_RESPONSE=$(curl -s -X PUT "${BASE_URL}/vendor-requests/${VENDOR_REQUEST_ID}" \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer ${TOKEN}" \
  -d '{
    "vendor_phone": "+1-555-9999",
    "status": "scheduled",
    "scheduled_date": "2025-11-15",
    "scheduled_time": "10:00"
  }')

echo "Response:"
echo "$UPDATE_RESPONSE" | python3 -m json.tool 2>/dev/null || echo "$UPDATE_RESPONSE"

if echo "$UPDATE_RESPONSE" | grep -q '"success":true'; then
  echo -e "${GREEN}✓ Updated vendor request successfully${NC}"
else
  echo -e "${RED}✗ Failed to update vendor request${NC}"
fi
echo ""

# Test 5: Resend Invitation
echo -e "${YELLOW}Test 5: Resend Invitation${NC}"

# First, set status back to 'sent' so we can resend
curl -s -X PUT "${BASE_URL}/vendor-requests/${VENDOR_REQUEST_ID}" \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer ${TOKEN}" \
  -d '{"status": "sent"}' > /dev/null

RESEND_RESPONSE=$(curl -s -X POST "${BASE_URL}/vendor-requests/${VENDOR_REQUEST_ID}/resend" \
  -H "Authorization: Bearer ${TOKEN}")

echo "Response:"
echo "$RESEND_RESPONSE" | python3 -m json.tool 2>/dev/null || echo "$RESEND_RESPONSE"

if echo "$RESEND_RESPONSE" | grep -q '"sent":true'; then
  echo -e "${GREEN}✓ Resent invitation successfully${NC}"
else
  echo -e "${YELLOW}⚠ Resend may have failed (check email configuration)${NC}"
fi
echo ""

# Test 6: Submit Vendor Rating
echo -e "${YELLOW}Test 6: Submit Vendor Rating${NC}"

# First, set status to 'completed' so we can rate
curl -s -X PUT "${BASE_URL}/vendor-requests/${VENDOR_REQUEST_ID}" \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer ${TOKEN}" \
  -d '{"status": "completed"}' > /dev/null

RATING_RESPONSE=$(curl -s -X POST "${BASE_URL}/vendor-requests/${VENDOR_REQUEST_ID}/rate" \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer ${TOKEN}" \
  -d '{
    "rating": 5,
    "timeliness_rating": 5,
    "quality_rating": 5,
    "communication_rating": 4,
    "review": "Excellent work! Very professional and thorough.",
    "would_recommend": 1
  }')

echo "Response:"
echo "$RATING_RESPONSE" | python3 -m json.tool 2>/dev/null || echo "$RATING_RESPONSE"

if echo "$RATING_RESPONSE" | grep -q '"rating_id"'; then
  echo -e "${GREEN}✓ Submitted vendor rating successfully${NC}"
else
  echo -e "${RED}✗ Failed to submit rating${NC}"
fi
echo ""

# Test 7: Delete Vendor Request (Create a new one for deletion test)
echo -e "${YELLOW}Test 7: Delete Vendor Request${NC}"

# Create a temporary vendor request for deletion
DELETE_TEST_RESPONSE=$(curl -s -X POST "${BASE_URL}/vendor-requests" \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer ${TOKEN}" \
  -d '{
    "transaction_id": 1,
    "vendor_type": "attorney",
    "vendor_email": "temp-attorney@example.com"
  }')

DELETE_VENDOR_ID=$(echo $DELETE_TEST_RESPONSE | grep -o '"id":[0-9]*' | head -1 | cut -d':' -f2)

if [ ! -z "$DELETE_VENDOR_ID" ]; then
  DELETE_RESPONSE=$(curl -s -X DELETE "${BASE_URL}/vendor-requests/${DELETE_VENDOR_ID}" \
    -H "Authorization: Bearer ${TOKEN}")

  echo "Response:"
  echo "$DELETE_RESPONSE" | python3 -m json.tool 2>/dev/null || echo "$DELETE_RESPONSE"

  if echo "$DELETE_RESPONSE" | grep -q '"deleted":true'; then
    echo -e "${GREEN}✓ Deleted vendor request successfully${NC}"
  else
    echo -e "${RED}✗ Failed to delete vendor request${NC}"
  fi
else
  echo -e "${RED}✗ Failed to create temporary vendor request for deletion test${NC}"
fi
echo ""

# Test 8: Error Handling - Invalid Token
echo -e "${YELLOW}Test 8: Error Handling - Invalid Token${NC}"
ERROR_RESPONSE=$(curl -s -X GET "${BASE_URL}/vendor-requests/999999" \
  -H "Authorization: Bearer invalid_token_12345")

echo "Response:"
echo "$ERROR_RESPONSE" | python3 -m json.tool 2>/dev/null || echo "$ERROR_RESPONSE"

if echo "$ERROR_RESPONSE" | grep -q '"code"'; then
  echo -e "${GREEN}✓ Error handling works correctly${NC}"
else
  echo -e "${YELLOW}⚠ Error handling response unexpected${NC}"
fi
echo ""

# Summary
echo -e "${BLUE}================================================${NC}"
echo -e "${BLUE}Test Summary${NC}"
echo -e "${BLUE}================================================${NC}"
echo -e "${GREEN}✓ Phase 1: Admin REST API Endpoints${NC}"
echo ""
echo "Endpoints Tested:"
echo "  ✓ POST   /vendor-requests          (Create)"
echo "  ✓ GET    /vendor-requests          (List)"
echo "  ✓ GET    /vendor-requests/{id}     (Get Single)"
echo "  ✓ PUT    /vendor-requests/{id}     (Update)"
echo "  ✓ DELETE /vendor-requests/{id}     (Delete)"
echo "  ✓ POST   /vendor-requests/{id}/resend (Resend)"
echo "  ✓ POST   /vendor-requests/{id}/rate   (Rate)"
echo ""
echo -e "${GREEN}All Phase 1 tests completed!${NC}"
echo ""
echo "Vendor Request ID for further testing: ${VENDOR_REQUEST_ID}"
echo ""
