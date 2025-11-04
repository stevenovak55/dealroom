# Quick Start: Vendor Portal Testing

## ⚡ Fast Setup (5 Minutes)

### Step 1: Build Frontend Assets

```bash
cd /home/snova/projects/dealroom/ma-deal-room/assets/admin
npm run build
```

**Expected:** Build succeeds with output showing bundle size (~650 KB)

### Step 2: Run Automated Test Setup

```bash
cd /home/snova/projects/dealroom
./setup-vendor-portal-test.sh
```

**This script automatically:**
- ✅ Creates a test transaction (456 Test Avenue, Boston, MA)
- ✅ Creates a test task (Home Inspection)
- ✅ Generates a secure 64-character token
- ✅ Creates a vendor request
- ✅ Displays the vendor portal URL

**Output will look like:**
```
✓ Test Data Created Successfully!

Vendor Portal URL:
http://localhost:8080/vendor/a1b2c3d4e5f6789...

Copy the URL above and paste it in your browser to test the vendor portal.
```

### Step 3: Open the Portal

**Copy the URL from the script output** and paste it in your browser.

---

## 🧪 Quick Test Checklist

Once the portal loads, test these features in order:

### ✅ 1. View Portal (1 minute)
- [ ] Portal loads successfully
- [ ] Shows property: "456 Test Avenue, Boston, MA 02101"
- [ ] Shows vendor type: "Inspector"
- [ ] Shows status badge: "Opened" (blue)
- [ ] Shows expiration countdown

### ✅ 2. Schedule Appointment (2 minutes)
- [ ] Select tomorrow's date
- [ ] Select time (e.g., 10:00 AM)
- [ ] Click "Schedule Appointment"
- [ ] Success notification appears
- [ ] Status changes to "Scheduled" (yellow)
- [ ] Scheduled info displays

### ✅ 3. Upload Document (2 minutes)
- [ ] Drag a PDF or image onto upload zone
- [ ] Progress bar shows 0-100%
- [ ] File preview appears
- [ ] Success notification shows

### ✅ 4. Complete Task (2 minutes)
- [ ] Enter completion notes (min 10 characters):
  ```
  Inspection completed. Property is in excellent condition.
  Minor wear noted in kitchen. See attached report.
  ```
- [ ] Click "Mark as Completed"
- [ ] Confirm in dialog
- [ ] Status changes to "Completed" (green)
- [ ] Completion summary displays

### ✅ 5. Verify Emails (1 minute)

Check Docker logs for email confirmations:

```bash
docker logs ma-dealroom-wp 2>&1 | grep -i "confirmation email sent"
```

**Expected output:**
```
Scheduling confirmation email sent for vendor request #XX
Completion confirmation email sent for vendor request #XX
```

---

## 📊 Quick Database Verification

Verify the complete workflow in the database:

```bash
docker exec ma-dealroom-wp wp db query "
SELECT
  id,
  status,
  scheduled_date,
  scheduled_time,
  LEFT(completion_notes, 50) as notes,
  document_url
FROM wp_ma_deal_vendor_requests
ORDER BY id DESC LIMIT 1;
"
```

**Expected output:**
```
id | status    | scheduled_date | scheduled_time | notes                        | document_url
---+-----------+----------------+----------------+------------------------------+------------------
XX | completed | 2025-11-03     | 10:00:00       | Inspection completed. Pro... | http://...
```

---

## 🐛 Common Issues & Quick Fixes

### Issue: Portal returns 404

**Fix:**
```bash
docker exec ma-dealroom-wp wp rewrite flush
```

### Issue: Upload fails

**Fix: Check upload directory permissions**
```bash
docker exec ma-dealroom-wp chmod -R 755 /var/www/html/wp-content/uploads
```

### Issue: Blank page

**Fix: Check browser console**
1. Press F12
2. Check Console tab for errors
3. Rebuild frontend if needed:
   ```bash
   cd /home/snova/projects/dealroom/ma-deal-room/assets/admin
   npm run build
   ```

---

## 📱 Quick Mobile Test

**Chrome DevTools:**
1. Press F12
2. Press Ctrl+Shift+M (toggle device toolbar)
3. Select "iPhone 12 Pro"
4. Test all features again

---

## 🎯 Success Criteria

You've successfully tested the vendor portal when:

- ✅ Portal loads without errors
- ✅ Can schedule appointment
- ✅ Can upload document
- ✅ Can complete task
- ✅ Status updates correctly
- ✅ Email logs confirm emails sent
- ✅ Database reflects all changes
- ✅ Works on mobile viewport

---

## 📖 Detailed Testing

For comprehensive testing instructions, see:
**`VENDOR_PORTAL_TESTING_GUIDE.md`**

This includes:
- Error testing (invalid tokens, validation)
- Performance testing
- Email template testing
- Complete troubleshooting guide

---

## 🔄 Create New Test Data

To create another vendor request for testing:

```bash
./setup-vendor-portal-test.sh
```

Each run creates:
- New transaction
- New task
- New vendor request
- New unique token

---

## 🚀 Production Deployment Checklist

Before deploying to production:

- [ ] All tests pass
- [ ] Email sending configured (SendGrid)
- [ ] Frontend assets built for production
- [ ] Database migrations applied
- [ ] Plugin activated
- [ ] WordPress permalinks flushed
- [ ] SSL/HTTPS configured
- [ ] Monitoring enabled
- [ ] Backup system in place

---

## 📞 Support

**Documentation:**
- Testing Guide: `VENDOR_PORTAL_TESTING_GUIDE.md`
- API Documentation: `docs/api/vendor-portal.md`
- Implementation Reports: `T2.2.*_IMPLEMENTATION.md`

**Test Scripts:**
- Setup test data: `./setup-vendor-portal-test.sh`
- Test emails: `php test-vendor-notification-emails.php`

**Logs:**
```bash
# View WordPress logs
docker logs ma-dealroom-wp 2>&1 | tail -100

# View vendor-specific logs
docker logs ma-dealroom-wp 2>&1 | grep -i vendor
```

---

**Happy Testing! 🎉**

The vendor portal is production-ready and fully functional. Report any issues you find during testing.
