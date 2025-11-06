#!/bin/bash

# Vendor Portal Test Setup Script
# This script creates test data for testing the vendor portal

set -e  # Exit on error

echo "=================================="
echo "Vendor Portal Test Data Setup"
echo "=================================="
echo ""

# Colors for output
GREEN='\033[0;32m'
BLUE='\033[0;34m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

# Check if Docker is running
if ! docker ps > /dev/null 2>&1; then
    echo "❌ Error: Docker is not running. Please start Docker first."
    exit 1
fi

echo "✓ Docker is running"

# Check if WordPress container exists
if ! docker ps | grep -q ma-dealroom-wp; then
    echo "❌ Error: WordPress container (ma-dealroom-wp) is not running."
    echo "   Run: docker-compose up -d"
    exit 1
fi

echo "✓ WordPress container is running"

# Step 1: Create test transaction
echo ""
echo -e "${BLUE}Step 1: Creating test transaction...${NC}"

docker exec ma-dealroom-wp wp db query "
INSERT INTO wp_ma_deal_transactions
(account_id, transaction_type, property_address, property_city, property_state, property_zip, status, created_at, updated_at)
VALUES
(1, 'purchase', '456 Test Avenue', 'Boston', 'MA', '02101', 'active', NOW(), NOW())
ON DUPLICATE KEY UPDATE updated_at = NOW();
" > /dev/null 2>&1

TRANSACTION_ID=$(docker exec ma-dealroom-wp wp db query "
SELECT id FROM wp_ma_deal_transactions ORDER BY id DESC LIMIT 1;
" --skip-column-names 2>/dev/null | tr -d '\r')

echo -e "${GREEN}✓ Transaction created (ID: $TRANSACTION_ID)${NC}"

# Step 2: Create test task
echo ""
echo -e "${BLUE}Step 2: Creating test task...${NC}"

docker exec ma-dealroom-wp wp db query "
INSERT INTO wp_ma_deal_tasks
(transaction_id, title, description, due_date, status, owner_role, created_at, updated_at)
VALUES
($TRANSACTION_ID, 'Home Inspection', 'Complete home inspection and submit detailed report', DATE_ADD(NOW(), INTERVAL 7 DAY), 'pending', 'vendor', NOW(), NOW());
" > /dev/null 2>&1

TASK_ID=$(docker exec ma-dealroom-wp wp db query "
SELECT id FROM wp_ma_deal_tasks ORDER BY id DESC LIMIT 1;
" --skip-column-names 2>/dev/null | tr -d '\r')

echo -e "${GREEN}✓ Task created (ID: $TASK_ID)${NC}"

# Step 3: Generate secure token
echo ""
echo -e "${BLUE}Step 3: Generating secure token...${NC}"

# Generate random 64-character hex token
TOKEN=$(openssl rand -hex 32)

echo -e "${GREEN}✓ Token generated${NC}"

# Step 4: Create vendor request
echo ""
echo -e "${BLUE}Step 4: Creating vendor request...${NC}"

docker exec ma-dealroom-wp wp db query "
INSERT INTO wp_ma_deal_vendor_requests
(task_id, transaction_id, vendor_type, vendor_email, vendor_phone, token, token_expires_at, status, created_at, updated_at)
VALUES
($TASK_ID, $TRANSACTION_ID, 'inspector', 'inspector@example.com', '+1-555-0100',
'$TOKEN',
DATE_ADD(NOW(), INTERVAL 30 DAY), 'sent', NOW(), NOW());
" > /dev/null 2>&1

VENDOR_REQUEST_ID=$(docker exec ma-dealroom-wp wp db query "
SELECT id FROM wp_ma_deal_vendor_requests ORDER BY id DESC LIMIT 1;
" --skip-column-names 2>/dev/null | tr -d '\r')

echo -e "${GREEN}✓ Vendor request created (ID: $VENDOR_REQUEST_ID)${NC}"

# Display results
echo ""
echo "=================================="
echo -e "${GREEN}✓ Test Data Created Successfully!${NC}"
echo "=================================="
echo ""
echo "Test Data Details:"
echo "-----------------------------------"
echo "Transaction ID:      $TRANSACTION_ID"
echo "Property:           456 Test Avenue, Boston, MA 02101"
echo "Task ID:            $TASK_ID"
echo "Vendor Request ID:  $VENDOR_REQUEST_ID"
echo "Vendor Type:        Inspector"
echo "Vendor Email:       inspector@example.com"
echo "Status:             sent"
echo "Token Expires:      30 days from now"
echo ""
echo -e "${YELLOW}Vendor Portal URL:${NC}"
echo "-----------------------------------"
echo "http://localhost:8080/vendor/$TOKEN"
echo ""
echo -e "${BLUE}Copy the URL above and paste it in your browser to test the vendor portal.${NC}"
echo ""

# Save to file for reference
cat > /tmp/vendor-portal-test-data.txt << EOF
Vendor Portal Test Data
Generated: $(date)

Transaction ID:     $TRANSACTION_ID
Task ID:           $TASK_ID
Vendor Request ID: $VENDOR_REQUEST_ID
Token:             $TOKEN

Portal URL:
http://localhost:8080/vendor/$TOKEN

Test Credentials:
- Vendor Type:  Inspector
- Vendor Email: inspector@example.com
- Vendor Phone: +1-555-0100
- Property:     456 Test Avenue, Boston, MA 02101
EOF

echo "Test data saved to: /tmp/vendor-portal-test-data.txt"
echo ""
echo "Next Steps:"
echo "1. Copy the Portal URL above"
echo "2. Open it in your browser"
echo "3. Follow the testing guide: VENDOR_PORTAL_TESTING_GUIDE.md"
echo ""
