# Vendor Portal Testing Guide

## Overview
This guide provides step-by-step instructions to test the complete vendor portal functionality on your WordPress development site.

---

## Prerequisites

### 1. Environment Setup
Ensure your development environment is running:

```bash
# Check if Docker containers are running
docker ps

# If not running, start them
cd /home/snova/projects/dealroom
docker-compose up -d

# Verify WordPress is accessible
curl -I http://localhost:8080
```

### 2. Build Frontend Assets
The vendor portal frontend must be built:

```bash
cd /home/snova/projects/dealroom/ma-deal-room/assets/admin

# Install dependencies (if not already done)
npm install

# Build frontend assets
npm run build

# Verify build succeeded
ls -la dist/
```

### 3. Plugin Activation
Ensure the MA Deal Room plugin is activated:

```bash
# Via WP-CLI in Docker
docker exec ma-dealroom-wp wp plugin list

# If not activated, activate it
docker exec ma-dealroom-wp wp plugin activate ma-deal-room
```

---

## Test Data Setup

### Step 1: Create Test Transaction

You need a transaction in the database to create a vendor request. Run this SQL:

```bash
docker exec ma-dealroom-wp wp db query "
INSERT INTO wp_ma_deal_transactions
(account_id, transaction_type, property_address, property_city, property_state, property_zip, status, created_at, updated_at)
VALUES
(1, 'purchase', '123 Main Street', 'Boston', 'MA', '02101', 'active', NOW(), NOW());
"
```

**Get the transaction ID:**

```bash
docker exec ma-dealroom-wp wp db query "
SELECT id, property_address FROM wp_ma_deal_transactions ORDER BY id DESC LIMIT 1;
"
```

**Note the transaction ID** (e.g., `15`)

### Step 2: Create Test Task

Create a task for the transaction:

```bash
# Replace TRANSACTION_ID with your actual transaction ID
docker exec ma-dealroom-wp wp db query "
INSERT INTO wp_ma_deal_tasks
(transaction_id, title, description, due_date, status, owner_role, created_at, updated_at)
VALUES
(TRANSACTION_ID, 'Home Inspection', 'Complete home inspection and submit report', DATE_ADD(NOW(), INTERVAL 7 DAY), 'pending', 'vendor', NOW(), NOW());
"
```

**Get the task ID:**

```bash
docker exec ma-dealroom-wp wp db query "
SELECT id, title FROM wp_ma_deal_tasks ORDER BY id DESC LIMIT 1;
"
```

**Note the task ID** (e.g., `42`)

### Step 3: Create Vendor Request

Create a vendor request with a secure token:

```bash
# Replace TASK_ID and TRANSACTION_ID with your actual IDs
docker exec ma-dealroom-wp wp db query "
INSERT INTO wp_ma_deal_vendor_requests
(task_id, transaction_id, vendor_type, vendor_email, vendor_phone, token, token_expires_at, status, created_at, updated_at)
VALUES
(TASK_ID, TRANSACTION_ID, 'inspector', 'inspector@example.com', '+1-555-0100',
'a1b2c3d4e5f6789012345678901234567890abcdef1234567890abcdef123456',
DATE_ADD(NOW(), INTERVAL 30 DAY), 'sent', NOW(), NOW());
"
```

**Get the vendor request details:**

```bash
docker exec ma-dealroom-wp wp db query "
SELECT id, token, vendor_email, status FROM wp_ma_deal_vendor_requests ORDER BY id DESC LIMIT 1;
"
```

**Copy the token** (the 64-character hex string)

---

## Testing the Vendor Portal

### Test 1: Access Vendor Portal

**URL Format:**
```
http://localhost:8080/vendor/{TOKEN}
```

**Example:**
```
http://localhost:8080/vendor/a1b2c3d4e5f6789012345678901234567890abcdef1234567890abcdef123456
```

**Expected Result:**
✅ Vendor portal page loads
✅ Shows property address: "123 Main Street, Boston, MA 02101"
✅ Shows vendor type: "Inspector"
✅ Shows status badge: "Opened" (blue)
✅ Shows expiration countdown
✅ Shows "Schedule Your Appointment" form

**Troubleshooting:**
- If 404 error: Check if React routing is configured correctly
- If blank page: Check browser console for errors
- If "Invalid token": Verify token matches database exactly

### Test 2: Status Auto-Update (sent → opened)

Check the database to verify status changed automatically:

```bash
docker exec ma-dealroom-wp wp db query "
SELECT status, last_opened_at FROM wp_ma_deal_vendor_requests
WHERE token = 'YOUR_TOKEN_HERE';
"
```

**Expected Result:**
✅ Status changed from 'sent' to 'opened'
✅ last_opened_at timestamp updated

### Test 3: Schedule Appointment

**Steps:**
1. In the vendor portal, fill out the scheduling form:
   - **Date:** Select tomorrow's date
   - **Time:** Select 10:00 AM
2. Click "Schedule Appointment"
3. Wait for success notification

**Expected Result:**
✅ Success toast notification appears
✅ Status badge changes to "Scheduled" (yellow)
✅ Scheduled date/time displayed on page
✅ Form changes to completion form

**Verify in Database:**

```bash
docker exec ma-dealroom-wp wp db query "
SELECT status, scheduled_date, scheduled_time FROM wp_ma_deal_vendor_requests
WHERE token = 'YOUR_TOKEN_HERE';
"
```

**Expected:**
- status: 'scheduled'
- scheduled_date: tomorrow's date (YYYY-MM-DD)
- scheduled_time: 10:00:00

**Check Email Logs:**

```bash
docker logs ma-dealroom-wp 2>&1 | grep -i "scheduling confirmation"
```

**Expected:**
✅ Log entry: "Scheduling confirmation email sent"

### Test 4: Document Upload

**Steps:**
1. Scroll to "Upload Document" section
2. Prepare a test PDF file or image (under 10MB)
3. Either:
   - **Drag and drop** the file onto the upload zone
   - **Click** the upload zone and select a file
4. Wait for upload to complete

**Expected Result:**
✅ Progress bar shows 0-100%
✅ Success message appears
✅ File preview displays (thumbnail for images, icon for PDF)
✅ File name and size shown
✅ "Replace" and "Delete" buttons appear

**Test File Validation:**

Try these invalid uploads:
- **Large file (>10MB):** Should show error "File size exceeds 10MB"
- **Wrong type (.txt, .docx):** Should show error "Invalid file type"

**Verify Upload in WordPress:**

```bash
# Check WordPress media library
docker exec ma-dealroom-wp wp db query "
SELECT ID, post_title, guid FROM wp_posts
WHERE post_type = 'attachment'
ORDER BY ID DESC LIMIT 5;
"
```

**Verify in Database:**

```bash
docker exec ma-dealroom-wp wp db query "
SELECT document_url FROM wp_ma_deal_vendor_requests
WHERE token = 'YOUR_TOKEN_HERE';
"
```

**Expected:**
✅ document_url contains WordPress media URL

### Test 5: Mark Task Complete

**Steps:**
1. Scroll to "Mark Task as Completed" section
2. Enter completion notes (minimum 10 characters):
   ```
   Inspection completed. Property is in excellent condition.
   Minor issues noted in attached report.
   ```
3. Ensure document is uploaded (from Test 4)
4. Click "Mark as Completed"
5. Confirm in the dialog

**Expected Result:**
✅ Confirmation dialog appears
✅ After confirming, success notification shows
✅ Status badge changes to "Completed" (green)
✅ Form is replaced with completion summary
✅ Shows completion notes
✅ Shows document download link

**Verify in Database:**

```bash
docker exec ma-dealroom-wp wp db query "
SELECT status, completion_notes, document_url FROM wp_ma_deal_vendor_requests
WHERE token = 'YOUR_TOKEN_HERE';
"
```

**Expected:**
- status: 'completed'
- completion_notes: Your entered text
- document_url: WordPress media URL

**Check Email Logs:**

```bash
docker logs ma-dealroom-wp 2>&1 | grep -i "completion confirmation"
```

**Expected:**
✅ Log entry: "Completion confirmation email sent"

### Test 6: Verify Completed State

**Steps:**
1. Refresh the page
2. Observe the UI

**Expected Result:**
✅ Status shows "Completed" (green badge)
✅ No forms visible (all disabled)
✅ Success message displayed
✅ Completion notes visible
✅ Document download link works
✅ Cannot schedule or complete again

---

## Email Testing

### Option 1: Check WordPress Logs (Email Simulation)

If email isn't configured to actually send:

```bash
# View all vendor-related email logs
docker logs ma-dealroom-wp 2>&1 | grep -i "vendor"

# View scheduling confirmation logs
docker logs ma-dealroom-wp 2>&1 | grep "Scheduling confirmation"

# View completion confirmation logs
docker logs ma-dealroom-wp 2>&1 | grep "Completion confirmation"
```

### Option 2: MailHog (if configured)

If MailHog is running in your Docker setup:

1. Open MailHog interface: `http://localhost:8025`
2. Look for emails from "MA Deal Room"
3. Verify:
   - ✅ Scheduling confirmation email received
   - ✅ Completion confirmation email received
   - ✅ Emails contain correct property address
   - ✅ Emails contain portal link with token
   - ✅ Emails render correctly (HTML)

### Option 3: Test Email Templates Directly

Run the test script:

```bash
cd /home/snova/projects/dealroom
php test-vendor-notification-emails.php
```

**Expected Output:**
```
Testing Vendor Notification Email System
========================================

Test 1: Scheduling Confirmation Email
✓ Template file exists
✓ Email sent successfully
✓ Scheduling confirmation sent to inspector@example.com

Test 2: Completion Confirmation Email
✓ Template file exists
✓ Email sent successfully
✓ Completion confirmation sent to inspector@example.com

Test 3: Appointment Reminder Email
✓ Template file exists
✓ Email sent successfully
✓ Reminder sent to inspector@example.com

All tests passed!
```

---

## Mobile Testing

### Test on Mobile Viewport

**Using Chrome DevTools:**
1. Open vendor portal in Chrome
2. Press F12 to open DevTools
3. Click "Toggle device toolbar" (Ctrl+Shift+M)
4. Select device: iPhone 12 Pro, Samsung Galaxy S20, iPad

**Test These:**
✅ Layout adapts to mobile screen
✅ Forms are easy to use on touch
✅ Buttons are large enough for touch
✅ Date/time pickers work on mobile
✅ File upload works on mobile
✅ Navigation is easy
✅ Text is readable

---

## Error Testing

### Test 1: Invalid Token

**URL:**
```
http://localhost:8080/vendor/invalid_token_12345
```

**Expected Result:**
✅ Error message: "Invalid or expired token"
✅ Retry button or support contact shown

### Test 2: Expired Token

**Manually expire a token:**

```bash
docker exec ma-dealroom-wp wp db query "
UPDATE wp_ma_deal_vendor_requests
SET token_expires_at = DATE_SUB(NOW(), INTERVAL 1 DAY)
WHERE token = 'YOUR_TOKEN_HERE';
"
```

**Access the portal**

**Expected Result:**
✅ Error message: "This vendor request has expired"
✅ Status automatically updated to 'expired' in database

### Test 3: Network Error

**In Chrome DevTools:**
1. Open vendor portal
2. Open DevTools (F12)
3. Go to "Network" tab
4. Select "Offline" from throttling dropdown
5. Try to schedule appointment

**Expected Result:**
✅ Error toast notification
✅ User-friendly error message
✅ Retry option available

### Test 4: Past Date Validation

**Steps:**
1. In scheduling form, try to select yesterday's date
2. Click "Schedule Appointment"

**Expected Result:**
✅ Error message: "Scheduled date must be in the future"
✅ Form not submitted

### Test 5: Completion Notes Validation

**Steps:**
1. In completion form, enter only 5 characters
2. Click "Mark as Completed"

**Expected Result:**
✅ Error message: "Completion notes must be at least 10 characters"
✅ Character counter shows remaining characters needed

---

## Performance Testing

### Check Bundle Size

```bash
cd /home/snova/projects/dealroom/ma-deal-room/assets/admin
npm run build

# Check bundle size
ls -lh dist/*.js
```

**Expected:**
- Total bundle: ~650 KB minified
- Gzipped: ~180 KB

### Check Page Load Time

**In Chrome DevTools:**
1. Open vendor portal
2. Open DevTools (F12) → Network tab
3. Refresh page (Ctrl+R)
4. Check "Load" time at bottom

**Expected:**
✅ Page loads in < 2 seconds on local dev
✅ No 404 errors in console
✅ No JavaScript errors

---

## Database Verification

### Check Complete Vendor Request

```bash
docker exec ma-dealroom-wp wp db query "
SELECT
  id,
  vendor_type,
  vendor_email,
  status,
  scheduled_date,
  scheduled_time,
  completion_notes,
  document_url,
  last_opened_at,
  created_at,
  updated_at
FROM wp_ma_deal_vendor_requests
WHERE token = 'YOUR_TOKEN_HERE';
"
```

**Expected Values:**
- status: 'completed'
- scheduled_date: Not NULL (YYYY-MM-DD)
- scheduled_time: Not NULL (HH:MM:SS)
- completion_notes: Your entered text
- document_url: WordPress media URL
- last_opened_at: Recent timestamp

### Check Event Logs

```bash
docker exec ma-dealroom-wp wp db query "
SELECT
  event_type,
  entity_type,
  created_at,
  event_data
FROM wp_ma_deal_events
WHERE entity_type = 'vendor_request'
ORDER BY created_at DESC
LIMIT 5;
"
```

**Expected:**
✅ Multiple 'updated' events for vendor_request
✅ event_data contains status changes

---

## Troubleshooting

### Issue: Vendor Portal Returns 404

**Solution:**
1. Check if frontend is built:
   ```bash
   ls -la /home/snova/projects/dealroom/ma-deal-room/assets/admin/dist/
   ```

2. Check if route is registered:
   ```bash
   docker exec ma-dealroom-wp wp db query "
   SELECT option_value FROM wp_options WHERE option_name = 'rewrite_rules';
   "
   ```

3. Flush rewrite rules:
   ```bash
   docker exec ma-dealroom-wp wp rewrite flush
   ```

### Issue: Email Not Sending

**Check WordPress mail function:**

```bash
docker exec ma-dealroom-wp wp eval "
\$result = wp_mail('test@example.com', 'Test Subject', 'Test body');
var_dump(\$result);
"
```

**Check logs:**

```bash
docker logs ma-dealroom-wp 2>&1 | tail -50
```

### Issue: Document Upload Fails

**Check WordPress upload directory:**

```bash
docker exec ma-dealroom-wp ls -la /var/www/html/wp-content/uploads/
```

**Check permissions:**

```bash
docker exec ma-dealroom-wp wp eval "
\$upload_dir = wp_upload_dir();
var_dump(\$upload_dir);
"
```

### Issue: Blank Page

**Check browser console:**
1. Open DevTools (F12)
2. Go to Console tab
3. Look for JavaScript errors

**Check frontend build:**

```bash
cd /home/snova/projects/dealroom/ma-deal-room/assets/admin
npm run build 2>&1 | tee build.log
```

---

## Complete Test Workflow Checklist

Use this checklist to verify everything works:

- [ ] **Setup**
  - [ ] Docker containers running
  - [ ] Frontend assets built
  - [ ] Plugin activated
  - [ ] Test data created (transaction, task, vendor request)

- [ ] **Access & Display**
  - [ ] Portal loads with valid token
  - [ ] Property details display correctly
  - [ ] Status badge shows "Opened"
  - [ ] Expiration countdown visible
  - [ ] Status timeline displays

- [ ] **Scheduling**
  - [ ] Date picker works (future dates only)
  - [ ] Time picker works
  - [ ] Validation prevents past dates
  - [ ] Schedule button submits successfully
  - [ ] Status changes to "Scheduled"
  - [ ] Scheduled info displays
  - [ ] Email log confirms email sent

- [ ] **Document Upload**
  - [ ] Drag-and-drop works
  - [ ] Click-to-browse works
  - [ ] Progress bar shows 0-100%
  - [ ] File validation works (type, size)
  - [ ] Preview displays correctly
  - [ ] Replace/delete buttons work
  - [ ] URL saved to database

- [ ] **Completion**
  - [ ] Notes textarea accepts input
  - [ ] Character counter works
  - [ ] Minimum 10 characters enforced
  - [ ] Confirmation dialog appears
  - [ ] Complete button submits successfully
  - [ ] Status changes to "Completed"
  - [ ] Completion summary displays
  - [ ] Email log confirms email sent

- [ ] **Error Handling**
  - [ ] Invalid token shows error
  - [ ] Expired token shows error
  - [ ] Network errors handled gracefully
  - [ ] Validation errors display clearly
  - [ ] Forms prevent invalid submissions

- [ ] **Mobile Responsive**
  - [ ] Layout adapts to mobile
  - [ ] Touch targets adequate
  - [ ] Forms usable on mobile
  - [ ] Text readable

---

## Success Criteria

✅ **All tests pass**
✅ **No console errors**
✅ **Email logs confirm emails sent**
✅ **Database reflects all changes**
✅ **Mobile responsive**
✅ **Production-ready**

---

## Next Steps After Testing

Once testing is complete:

1. **Document any issues found**
2. **Test with real email addresses** (configure SendGrid)
3. **User acceptance testing** with actual vendors
4. **Performance testing** under load
5. **Deploy to staging environment**
6. **Final production deployment**

---

## Support

If you encounter issues during testing:

1. **Check logs:** `docker logs ma-dealroom-wp 2>&1 | tail -100`
2. **Check browser console:** F12 → Console tab
3. **Verify database:** Run SQL queries above
4. **Review documentation:** See implementation reports in project root

**Testing timestamp:** Run date: `date`
**Environment:** WordPress development site (localhost:8080)
**Plugin version:** MA Deal Room v1.0.0
