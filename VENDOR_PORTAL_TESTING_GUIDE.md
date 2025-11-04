# Vendor Portal - Comprehensive Testing Guide

**Version:** 1.0.0
**Date:** November 4, 2025
**Status:** Ready for Testing

---

## 📋 Table of Contents

1. [Quick Start](#quick-start)
2. [Frontend UI Testing (Standalone)](#frontend-ui-testing-standalone)
3. [Backend API Testing (Full Stack)](#backend-api-testing-full-stack)
4. [End-to-End Testing](#end-to-end-testing)
5. [Mobile Testing](#mobile-testing)
6. [Error Scenarios](#error-scenarios)
7. [Performance Testing](#performance-testing)
8. [Security Testing](#security-testing)

---

## 🚀 Quick Start

### Option 1: Frontend UI Testing (No Backend Required)

**Perfect for:** Visual UI testing, design verification, component interaction

```bash
# Simply open the test HTML file in your browser
open vendor-portal-test.html
# OR
firefox vendor-portal-test.html
# OR
chrome vendor-portal-test.html
```

This standalone page shows all components with mock data. Use it to verify:
- ✅ All components render correctly
- ✅ Styling and gradients display properly
- ✅ Forms are functional
- ✅ Mobile responsiveness
- ✅ Icons load from CDN

---

### Option 2: Full Backend Testing (Requires WordPress/Docker)

**Perfect for:** API testing, database operations, real data flows

**Prerequisites:**
- Docker installed and running
- Docker Compose configured
- WordPress environment set up

```bash
# 1. Start the Docker environment
docker-compose up -d

# 2. Wait for services to be ready (30-60 seconds)
docker-compose ps

# 3. Run the test data setup script
./setup-vendor-portal-test.sh

# 4. Copy the generated URL and open in browser
# Example: http://localhost:8080/vendor/abc123...
```

---

## 🎨 Frontend UI Testing (Standalone)

### Step 1: Open Test Page

```bash
open vendor-portal-test.html
```

### Step 2: Visual Inspection Checklist

Use the interactive checklist on the page itself, or follow these steps:

#### **Dashboard Component**
- [ ] Header gradient (blue to indigo) displays correctly
- [ ] Transaction ID shows in header
- [ ] Status badge displays with correct color (green for scheduled)
- [ ] Property information section is readable
- [ ] Document status shows correctly
- [ ] All text is properly aligned

#### **Scheduling Form Component**
- [ ] Header gradient (purple to pink) displays correctly
- [ ] Date picker is functional
- [ ] Time picker is functional
- [ ] Name input field works
- [ ] Availability windows display in list
- [ ] "Add Availability Window" button is visible
- [ ] "Remove" buttons work for availability slots

#### **Document Uploader Component**
- [ ] Header gradient (green to emerald) displays correctly
- [ ] Drag-and-drop zone has dashed border
- [ ] Hover effect changes border to blue
- [ ] Upload icon displays (from Lucide)
- [ ] File type restrictions are listed
- [ ] Success state shows green checkmark
- [ ] Uploaded file name and size display

#### **Message Thread Component**
- [ ] Header gradient (purple to pink) displays correctly
- [ ] "2 new" badge shows in header
- [ ] Date divider ("Today") displays
- [ ] Agent messages align LEFT with purple avatar
- [ ] Vendor messages align RIGHT with blue background
- [ ] Timestamps show for each message
- [ ] Read receipt shows for vendor messages
- [ ] Message input textarea is functional
- [ ] Send button displays with icon

#### **Completion Form Component**
- [ ] Header gradient (green to emerald) displays correctly
- [ ] Date picker defaults to today
- [ ] Notes textarea accepts input
- [ ] Character counter shows "0/1000"
- [ ] Submit button displays with checkmark icon
- [ ] "What happens next?" info box is visible
- [ ] List items display with bullets

### Step 3: Responsive Design Testing

Resize your browser window to test different screen sizes:

**Desktop (1920x1080)**
- [ ] All components display full width
- [ ] Multi-column layouts work
- [ ] No horizontal scrolling

**Tablet (768x1024)**
- [ ] Components stack appropriately
- [ ] Text remains readable
- [ ] Touch targets are adequate size

**Mobile (375x667)**
- [ ] All components are vertically stacked
- [ ] Forms are easy to fill out
- [ ] Buttons are thumb-friendly (48x48px minimum)
- [ ] No text is cut off

### Step 4: Browser Compatibility

Test in multiple browsers:

- [ ] Chrome/Chromium (latest)
- [ ] Firefox (latest)
- [ ] Safari (latest)
- [ ] Edge (latest)
- [ ] Mobile Safari (iOS)
- [ ] Mobile Chrome (Android)

---

## 🔧 Backend API Testing (Full Stack)

### Prerequisites

1. **Start WordPress Environment**

```bash
# Start all containers
docker-compose up -d

# Verify containers are running
docker ps | grep ma-dealroom

# Expected output:
# ma-dealroom-wp        (WordPress)
# ma-dealroom-db        (MySQL)
# ma-dealroom-redis     (Redis)
# ma-dealroom-pma       (PHPMyAdmin)
```

2. **Create Test Data**

```bash
# Run the automated test setup script
./setup-vendor-portal-test.sh

# This creates:
# - Test transaction
# - Test task
# - Test vendor request
# - Secure token (64-char hex)
```

The script will output:
- Transaction ID
- Task ID
- Vendor Request ID
- **Token** (SAVE THIS!)
- Portal URL

**Save the token for API testing!**

---

### API Endpoint Tests

Replace `{TOKEN}` with your actual token from the setup script.

#### Test 1: Get Vendor Portal Data

**Endpoint:** `GET /wp-json/ma-deal-room/v1/vendor/{TOKEN}`

```bash
# Using curl
curl -X GET "http://localhost:8080/wp-json/ma-deal-room/v1/vendor/YOUR_TOKEN_HERE" \
  -H "Content-Type: application/json"

# Expected Response (200 OK):
{
  "success": true,
  "data": {
    "vendor_request": {
      "id": 1,
      "status": "sent",
      "vendor_type": "inspector",
      "vendor_email": "inspector@example.com",
      ...
    },
    "transaction": {
      "id": 1,
      "property_address": "456 Test Avenue",
      "property_city": "Boston",
      ...
    },
    "messages": [],
    "availability_windows": [],
    "rating": null
  }
}
```

**Verification:**
- [ ] Status code is 200
- [ ] Response contains `vendor_request` object
- [ ] Response contains `transaction` object
- [ ] All expected fields are present
- [ ] No PHP errors in response

---

#### Test 2: Schedule Appointment

**Endpoint:** `POST /wp-json/ma-deal-room/v1/vendor/{TOKEN}/schedule`

```bash
curl -X POST "http://localhost:8080/wp-json/ma-deal-room/v1/vendor/YOUR_TOKEN_HERE/schedule" \
  -H "Content-Type: application/json" \
  -d '{
    "scheduled_date": "2025-11-15",
    "scheduled_time": "10:00",
    "vendor_name": "John Smith",
    "vendor_company": "ABC Inspections"
  }'

# Expected Response (200 OK):
{
  "success": true,
  "message": "Appointment scheduled successfully"
}
```

**Verification:**
- [ ] Status code is 200
- [ ] Success message returned
- [ ] Database updated (check wp_ma_deal_vendor_requests)
- [ ] Status changed to "scheduled"
- [ ] scheduled_date and scheduled_time are set

**Database Verification:**

```bash
docker exec ma-dealroom-wp wp db query \
  "SELECT id, status, scheduled_date, scheduled_time, vendor_name
   FROM wp_ma_deal_vendor_requests
   ORDER BY id DESC LIMIT 1;"
```

---

#### Test 3: Submit Availability Windows

**Endpoint:** `POST /wp-json/ma-deal-room/v1/vendor/{TOKEN}/availability`

```bash
curl -X POST "http://localhost:8080/wp-json/ma-deal-room/v1/vendor/YOUR_TOKEN_HERE/availability" \
  -H "Content-Type: application/json" \
  -d '{
    "availability_windows": [
      {
        "available_date": "2025-11-11",
        "start_time": "09:00",
        "end_time": "12:00"
      },
      {
        "available_date": "2025-11-12",
        "start_time": "14:00",
        "end_time": "17:00"
      }
    ]
  }'

# Expected Response (200 OK):
{
  "success": true,
  "message": "Availability windows submitted successfully"
}
```

**Verification:**
- [ ] Status code is 200
- [ ] Success message returned
- [ ] Database has 2 new rows in wp_ma_deal_vendor_availability
- [ ] All dates and times are correct

**Database Verification:**

```bash
docker exec ma-dealroom-wp wp db query \
  "SELECT vendor_request_id, available_date, start_time, end_time
   FROM wp_ma_deal_vendor_availability
   ORDER BY id DESC LIMIT 2;"
```

---

#### Test 4: Upload Document

**Endpoint:** `POST /wp-json/ma-deal-room/v1/vendor/{TOKEN}/upload`

```bash
# Create a test PDF file
echo "Test inspection report" > test-report.pdf

# Upload via curl
curl -X POST "http://localhost:8080/wp-json/ma-deal-room/v1/vendor/YOUR_TOKEN_HERE/upload" \
  -F "file=@test-report.pdf"

# Expected Response (200 OK):
{
  "success": true,
  "message": "Document uploaded successfully",
  "data": {
    "document_url": "http://localhost:8080/wp-content/uploads/2025/11/test-report.pdf"
  }
}
```

**Verification:**
- [ ] Status code is 200
- [ ] Document URL returned
- [ ] File exists in uploads directory
- [ ] Database updated with document_url
- [ ] File is accessible via returned URL

---

#### Test 5: Send Message

**Endpoint:** `POST /wp-json/ma-deal-room/v1/vendor/{TOKEN}/message`

```bash
curl -X POST "http://localhost:8080/wp-json/ma-deal-room/v1/vendor/YOUR_TOKEN_HERE/message" \
  -H "Content-Type: application/json" \
  -d '{
    "sender_name": "John Smith",
    "message": "I have completed the inspection and uploaded the report."
  }'

# Expected Response (200 OK):
{
  "success": true,
  "message": "Message sent successfully",
  "data": {
    "message_id": 1
  }
}
```

**Verification:**
- [ ] Status code is 200
- [ ] Message ID returned
- [ ] Database has new row in wp_ma_deal_vendor_messages
- [ ] sender_type is "vendor"
- [ ] is_read is 0 (unread)

---

#### Test 6: Get Messages

**Endpoint:** `GET /wp-json/ma-deal-room/v1/vendor/{TOKEN}/messages`

```bash
curl -X GET "http://localhost:8080/wp-json/ma-deal-room/v1/vendor/YOUR_TOKEN_HERE/messages" \
  -H "Content-Type: application/json"

# Expected Response (200 OK):
{
  "success": true,
  "data": {
    "messages": [
      {
        "id": 1,
        "sender_name": "John Smith",
        "sender_type": "vendor",
        "message": "I have completed the inspection...",
        "is_read": 0,
        "created_at": "2025-11-04 10:30:00"
      }
    ],
    "unread_count": 1
  }
}
```

**Verification:**
- [ ] Status code is 200
- [ ] Messages array contains message(s)
- [ ] unread_count is correct
- [ ] Messages sorted by created_at (newest first)

---

#### Test 7: Mark Messages as Read

**Endpoint:** `POST /wp-json/ma-deal-room/v1/vendor/{TOKEN}/messages/read`

```bash
curl -X POST "http://localhost:8080/wp-json/ma-deal-room/v1/vendor/YOUR_TOKEN_HERE/messages/read" \
  -H "Content-Type: application/json"

# Expected Response (200 OK):
{
  "success": true,
  "message": "Messages marked as read"
}
```

**Verification:**
- [ ] Status code is 200
- [ ] Database updated (is_read = 1 for vendor messages)
- [ ] Subsequent GET /messages shows unread_count = 0

---

#### Test 8: Complete Request

**Endpoint:** `POST /wp-json/ma-deal-room/v1/vendor/{TOKEN}/complete`

```bash
curl -X POST "http://localhost:8080/wp-json/ma-deal-room/v1/vendor/YOUR_TOKEN_HERE/complete" \
  -H "Content-Type: application/json" \
  -d '{
    "completion_date": "2025-11-04",
    "completion_notes": "Inspection completed. No major issues found. Minor repairs recommended in report."
  }'

# Expected Response (200 OK):
{
  "success": true,
  "message": "Request marked as complete"
}
```

**Verification:**
- [ ] Status code is 200
- [ ] Database updated (status = "completed")
- [ ] completion_date is set
- [ ] completion_notes is saved
- [ ] Cannot complete without document uploaded (should return error)

---

### Error Scenarios to Test

#### Invalid Token

```bash
curl -X GET "http://localhost:8080/wp-json/ma-deal-room/v1/vendor/INVALID_TOKEN" \
  -H "Content-Type: application/json"

# Expected Response (401 Unauthorized):
{
  "code": "invalid_token",
  "message": "Invalid or expired token",
  "data": {
    "status": 401
  }
}
```

**Verification:**
- [ ] Status code is 401
- [ ] Error message is clear
- [ ] No PHP errors

---

#### Expired Token

```bash
# Manually expire token in database
docker exec ma-dealroom-wp wp db query \
  "UPDATE wp_ma_deal_vendor_requests
   SET token_expires_at = '2025-01-01 00:00:00'
   WHERE token = 'YOUR_TOKEN';"

# Try to access portal
curl -X GET "http://localhost:8080/wp-json/ma-deal-room/v1/vendor/YOUR_TOKEN" \
  -H "Content-Type: application/json"

# Expected Response (401 Unauthorized):
{
  "code": "token_expired",
  "message": "Token has expired",
  "data": {
    "status": 401
  }
}
```

**Verification:**
- [ ] Status code is 401
- [ ] Error message indicates expiration
- [ ] No PHP errors

---

#### Missing Required Fields

```bash
# Try to schedule without required fields
curl -X POST "http://localhost:8080/wp-json/ma-deal-room/v1/vendor/YOUR_TOKEN/schedule" \
  -H "Content-Type: application/json" \
  -d '{}'

# Expected Response (400 Bad Request):
{
  "code": "missing_required_fields",
  "message": "Missing required fields: scheduled_date",
  "data": {
    "status": 400
  }
}
```

**Verification:**
- [ ] Status code is 400
- [ ] Error message lists missing fields
- [ ] No PHP errors

---

#### Complete Without Document

```bash
# Try to complete request without uploading document first
curl -X POST "http://localhost:8080/wp-json/ma-deal-room/v1/vendor/YOUR_TOKEN/complete" \
  -H "Content-Type: application/json" \
  -d '{
    "completion_date": "2025-11-04"
  }'

# Expected Response (400 Bad Request):
{
  "code": "document_required",
  "message": "Please upload a document before completing the request",
  "data": {
    "status": 400
  }
}
```

**Verification:**
- [ ] Status code is 400
- [ ] Error message is clear
- [ ] No PHP errors

---

## 🔄 End-to-End Testing

Complete vendor workflow from start to finish:

### Scenario 1: Happy Path

1. **Access Portal**
   - [ ] Vendor receives email with portal link
   - [ ] Clicks link and portal loads
   - [ ] Dashboard shows property information

2. **View Details**
   - [ ] Property address displays correctly
   - [ ] Task description is clear
   - [ ] Due date is shown

3. **Schedule Appointment**
   - [ ] Vendor selects date and time
   - [ ] Enters name and company
   - [ ] Submits schedule form
   - [ ] Status changes to "scheduled"

4. **Upload Document**
   - [ ] Vendor drags PDF into upload zone
   - [ ] File validates (type and size)
   - [ ] Upload completes successfully
   - [ ] Document URL is saved

5. **Send Messages**
   - [ ] Vendor sends message to agent
   - [ ] Message appears in thread
   - [ ] Agent receives notification (if enabled)

6. **Complete Request**
   - [ ] Vendor fills out completion form
   - [ ] Adds completion notes
   - [ ] Submits completion
   - [ ] Status changes to "completed"

7. **Verification**
   - [ ] Agent can see completion in admin
   - [ ] All data is saved correctly
   - [ ] Vendor receives confirmation

---

### Scenario 2: Availability Submission Path

1. **Access Portal**
   - [ ] Vendor loads portal with token

2. **Submit Availability**
   - [ ] Vendor adds 3 availability windows
   - [ ] Each window has date and time range
   - [ ] Submits availability
   - [ ] Confirmation message appears

3. **Agent Schedules**
   - [ ] Agent sees availability windows
   - [ ] Agent selects preferred time
   - [ ] Vendor is notified

4. **Continue Workflow**
   - [ ] Same as Happy Path steps 4-7

---

## 📱 Mobile Testing

### Devices to Test

- [ ] iPhone 14 Pro (iOS 17)
- [ ] iPhone SE (small screen)
- [ ] Samsung Galaxy S23 (Android 13)
- [ ] iPad Air (tablet)
- [ ] Desktop Chrome (responsive mode)

### Mobile-Specific Checks

#### Touch Interactions
- [ ] All buttons are minimum 48x48px
- [ ] Form inputs are easy to tap
- [ ] No accidental clicks
- [ ] Scroll works smoothly

#### Layout
- [ ] Components stack vertically
- [ ] No horizontal scroll
- [ ] Text is readable (16px minimum)
- [ ] Images scale properly

#### Forms
- [ ] Date picker opens native mobile picker
- [ ] Time picker opens native mobile picker
- [ ] Keyboard doesn't obscure inputs
- [ ] Auto-zoom disabled on input focus

#### File Upload
- [ ] Can upload from camera
- [ ] Can upload from photo library
- [ ] Can upload from files app
- [ ] Preview works on mobile

---

## ⚡ Performance Testing

### Load Time
- [ ] Initial page load < 3 seconds
- [ ] Time to interactive < 5 seconds
- [ ] No blocking scripts

### Asset Sizes
- [ ] JS bundle < 1MB (compressed)
- [ ] CSS bundle < 100KB (compressed)
- [ ] Images optimized
- [ ] CDN assets load quickly (Tailwind, Lucide)

### API Performance
- [ ] GET /vendor/{token} responds < 500ms
- [ ] POST requests respond < 1s
- [ ] File uploads complete in reasonable time

### Database Performance
- [ ] Queries use proper indexes
- [ ] No N+1 query problems
- [ ] Joins are optimized

**Check with:**

```bash
docker exec ma-dealroom-wp wp db query "EXPLAIN SELECT * FROM wp_ma_deal_vendor_requests WHERE token = 'YOUR_TOKEN';"
```

---

## 🔒 Security Testing

### Token Security
- [ ] Tokens are 64 characters (secure random)
- [ ] Tokens expire after 30 days
- [ ] Expired tokens are rejected
- [ ] Invalid tokens return 401
- [ ] Tokens are validated on every request

### Input Validation
- [ ] SQL injection prevented (prepared statements)
- [ ] XSS prevented (proper escaping)
- [ ] File upload restrictions enforced
- [ ] File size limits enforced (10MB max)
- [ ] File type validation (whitelist)

### File Upload Security
- [ ] Only allowed file types accepted
- [ ] Files renamed on server
- [ ] Files stored outside web root (or protected)
- [ ] No executable files allowed
- [ ] Malware scanning (if available)

### API Security
- [ ] CORS properly configured
- [ ] No sensitive data in error messages
- [ ] Rate limiting implemented (if applicable)
- [ ] Logs don't expose tokens

---

## 📊 Test Results Template

Use this template to document your testing:

```markdown
# Vendor Portal Test Results

**Date:** November 4, 2025
**Tester:** [Your Name]
**Environment:** [Local Docker / Staging / Production]

## Frontend UI Testing
- ✅ All components render correctly
- ✅ Responsive design works on mobile
- ✅ Icons load from CDN
- ❌ Minor styling issue with X component

## Backend API Testing
- ✅ All 8 API endpoints tested
- ✅ Data saves correctly to database
- ✅ Error handling works as expected
- ⚠️  Performance concern with message loading (see notes)

## End-to-End Testing
- ✅ Happy path completed successfully
- ✅ Availability submission works
- ❌ Edge case: Concurrent uploads need testing

## Issues Found
1. **Minor:** Button hover color on mobile (line 234)
2. **Medium:** Message thread doesn't auto-scroll on mobile
3. **Critical:** None

## Recommendations
- Consider adding loading skeleton states
- Add file upload progress bar
- Implement real-time message updates (WebSocket/polling)

## Overall Status
✅ PASS - Ready for production with minor fixes

**Signature:** _________________
```

---

## 🛠️ Troubleshooting

### Issue: "Cannot connect to backend"

**Solution:**
```bash
# Check if Docker containers are running
docker-compose ps

# Restart if needed
docker-compose restart wordpress

# Check logs
docker-compose logs wordpress | tail -50
```

---

### Issue: "Token not found"

**Solution:**
```bash
# Verify token exists in database
docker exec ma-dealroom-wp wp db query \
  "SELECT id, token, token_expires_at, status
   FROM wp_ma_deal_vendor_requests
   WHERE token = 'YOUR_TOKEN';"

# If empty, run setup script again
./setup-vendor-portal-test.sh
```

---

### Issue: "File upload fails"

**Solution:**
```bash
# Check upload directory permissions
docker exec ma-dealroom-wp ls -la /var/www/html/wp-content/uploads/

# Fix permissions if needed
docker exec ma-dealroom-wp chmod 755 /var/www/html/wp-content/uploads/
```

---

### Issue: "PHP errors in response"

**Solution:**
```bash
# Check WordPress error log
docker exec ma-dealroom-wp tail -f /var/www/html/wp-content/debug.log

# Check PHP error log
docker-compose logs wordpress | grep -i error
```

---

## 📚 Additional Resources

- **Frontend Build:** See `ma-deal-room/assets/admin/README.md`
- **Backend Implementation:** See `VENDOR_PORTAL_BACKEND_IMPLEMENTATION.md`
- **API Documentation:** See REST API endpoints in `src/REST/Controllers/VendorPortalController.php`
- **Database Schema:** See `database/migrations/030_enhance_vendor_portal.sql`

---

## ✅ Sign-Off Checklist

Before marking testing as complete:

- [ ] All frontend components tested visually
- [ ] All 8 API endpoints tested successfully
- [ ] Error scenarios tested and handled correctly
- [ ] Mobile responsiveness verified on 3+ devices
- [ ] Performance meets acceptable thresholds
- [ ] Security checks passed
- [ ] End-to-end scenarios completed
- [ ] Test results documented
- [ ] Issues logged and prioritized
- [ ] Stakeholders notified of completion

---

**Testing Status:** 🟢 Ready to Test
**Last Updated:** November 4, 2025
**Next Review:** After first test cycle
