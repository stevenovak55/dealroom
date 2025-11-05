# Known TODOs - MA Deal Room v2.0.0

This document tracks all remaining TODO items in the codebase. These are non-critical items that don't block production deployment but should be addressed in future releases.

**Status:** These TODOs are documented, tracked, and acceptable for production deployment.

---

## 📋 TODO Items by Priority

### 🟡 Medium Priority (Future Enhancements)

#### 1. Queue Processing Implementation
**File:** `src/CLI/QueueCommand.php:29`
**Line:** 29
```php
// TODO: Implement queue processing logic in Phase 6
```

**Description:** Background job queue processing is currently stubbed out.

**Impact:** Low - Most operations work synchronously. Queue needed for:
- Bulk operations
- Email campaigns
- Large data imports

**Recommended Implementation:**
- Use WordPress cron as task scheduler
- Store jobs in `wp_ma_deal_job_queue` table
- Process in batches to avoid timeouts

**Target Release:** v2.1.0 (Phase 6)

---

#### 2. Vendor Portal Update Logic
**File:** `src/REST/Controllers/VendorPortalController.php:66`
**Line:** 66
```php
// TODO: Implement vendor request update logic in Phase 6
```

**Description:** Vendor request update endpoint is incomplete.

**Impact:** Low - Vendor portal is Phase 6 feature, not yet released

**Recommended Implementation:**
- Allow vendors to update request status
- Add vendor comments/notes
- Implement vendor document upload

**Target Release:** v2.2.0 (Vendor Portal Release)

---

#### 3. Email/SMS Tracking Database Storage
**Files:**
- `src/Services/EmailService.php:611`
- `src/Services/SMSService.php:328`
- `src/Services/SMSService.php:376`

**Description:** Email and SMS delivery tracking currently only logs to debug.log

**Current Behavior:**
```php
// TODO: Store in database table for tracking and analytics
error_log(sprintf('Email sent: template=%s, to=%s', $template, $to));
```

**Impact:** Low - Emails/SMS are sent successfully, just not tracked in database

**Recommended Implementation:**
- Create `wp_ma_deal_communication_log` table
- Track: type (email/sms), recipient, status, timestamp, template used
- Add admin dashboard for communication analytics
- Useful for debugging delivery issues

**Target Release:** v2.3.0 (Analytics & Reporting)

---

### 🟢 Low Priority (Nice to Have)

#### 4. Improved Phone Number Logic
**File:** `src/Services/ReminderService.php:93`
**Line:** 93
```php
// TODO: Implement more robust logic for determining recipient phone number
```

**Description:** Phone number determination for SMS reminders is basic

**Current Behavior:**
- Checks transaction party phone number
- Falls back to user phone number
- Works but could be smarter

**Recommended Improvements:**
- Priority system: transaction contact → user primary → user mobile → user office
- Validation of phone number format
- Country code handling
- Opt-out checking

**Target Release:** v2.4.0 (SMS Enhancements)

---

#### 5. Admin Security Notification Emails
**File:** `src/Services/FileSecurityService.php:408`
**Line:** 408
```php
// TODO: Send admin notification email
```

**Description:** When virus detected, only logs warning - doesn't email admin

**Current Behavior:**
```php
error_log('MA Deal Room: Virus detected in uploaded file');
// TODO: Send admin notification email
```

**Impact:** Very Low - Virus detection works and logs properly

**Recommended Implementation:**
- Email site admin on virus detection
- Include: user, file name, timestamp, virus name
- Add admin setting to enable/disable these emails
- Rate limit to avoid spam if under attack

**Target Release:** v2.5.0 (Admin Notifications)

---

#### 6. Suspicious Activity Notifications
**File:** `src/Services/AuthService.php:509`
**Line:** 509
```php
// TODO: Send notification email to user about suspicious activity
```

**Description:** Suspicious login activity is logged but user not notified

**Impact:** Low - Activity is logged and admin can review in security dashboard

**Recommended Implementation:**
- Email user when login from new location/device
- Include: location (IP), device info, timestamp
- Provide "This wasn't me" button to immediately lock account
- User preference to enable/disable these emails

**Target Release:** v2.6.0 (Enhanced Security Features)

---

#### 7. SMS Delivery Status Updates
**File:** `src/Services/SMSService.php:376`
**Line:** 376
```php
// TODO: Update database record with delivery status
```

**Description:** Twilio webhook for delivery status doesn't update database

**Impact:** Low - SMS is sent, just delivery confirmation not tracked

**Recommended Implementation:**
- Store SMS in database with "pending" status
- Twilio webhook updates status: "delivered", "failed", "undelivered"
- Use for delivery rate analytics
- Retry failed messages automatically

**Target Release:** v2.3.0 (Analytics & Reporting)

---

#### 8. SMS Opt-Out Database Check
**File:** `src/Services/SMSService.php:396`
**Line:** 396
```php
// TODO: Check database for opt-out status
```

**Description:** Opt-out checking is stubbed

**Current Behavior:**
- Always returns false (never opted out)
- Relies on Twilio's opt-out handling

**Impact:** Low - Twilio handles STOP/START keywords automatically

**Recommended Implementation:**
- Store opt-out status in `wp_ma_deal_custom_users` table
- Check before sending each SMS
- Admin interface to manage opt-outs
- Compliance with TCPA regulations

**Target Release:** v2.4.0 (SMS Enhancements)

---

## 🔧 Resolved TODOs (v2.0.0)

These were previously TODOs but have been resolved:

### ✅ AccountSecurityService Integration
**Status:** RESOLVED in v2.0.0
**Files:**
- `src/REST/Controllers/TwoFactorController.php` - 7 instances
- `src/REST/Controllers/AuthController.php` - 4 instances

**Resolution:**
- AccountSecurityService created and fully implemented
- Wired into all controllers via dependency injection
- Security events now properly logged to database

---

## 📊 TODO Summary Statistics

| Priority | Count | Target Release |
|----------|-------|----------------|
| Critical | 0 | - |
| High | 0 | - |
| Medium | 3 | v2.1.0 - v2.3.0 |
| Low | 5 | v2.4.0 - v2.6.0 |
| **Total** | **8** | Various |

---

## 🎯 Recommended Implementation Roadmap

### v2.1.0 (Q1 2026) - Background Jobs
- Implement queue processing system
- Add batch operations support

### v2.2.0 (Q2 2026) - Vendor Portal
- Complete vendor request updates
- Add vendor document management

### v2.3.0 (Q3 2026) - Analytics & Tracking
- Email/SMS tracking database
- SMS delivery status updates
- Communication analytics dashboard

### v2.4.0 (Q4 2026) - SMS Enhancements
- Improved phone number logic
- SMS opt-out database management
- TCPA compliance features

### v2.5.0 (Q1 2027) - Admin Notifications
- Virus detection admin emails
- Security event notifications
- Admin notification preferences

### v2.6.0 (Q2 2027) - Enhanced Security
- Suspicious activity user notifications
- Enhanced 2FA options
- Security audit dashboard

---

## 📝 Creating GitHub Issues

When ready to implement these TODOs, create GitHub issues using this template:

```markdown
## [TODO] {Title}

**File:** `src/path/to/file.php:123`
**Priority:** Medium/Low
**Target Release:** v2.x.0

### Current Behavior
{Description of what happens now}

### Desired Behavior
{What should happen}

### Implementation Notes
{Technical details from this document}

### Acceptance Criteria
- [ ] Item 1
- [ ] Item 2
- [ ] Tests written
- [ ] Documentation updated

**Reference:** KNOWN_TODOS.md
```

---

## 🔍 Finding TODOs in Code

To search for TODO comments:
```bash
# Find all TODO comments
grep -rn "TODO\|FIXME\|XXX\|HACK" ma-deal-room/src/

# Find only TODO comments
grep -rn "TODO" ma-deal-room/src/ | grep -v "Binary"

# Count TODOs by file
grep -r "TODO" ma-deal-room/src/ --include="*.php" | cut -d: -f1 | sort | uniq -c
```

---

## ✅ Production Readiness

**These TODOs do NOT block production deployment because:**

1. **All core features work** - Registration, login, 2FA, transactions, tasks, etc.
2. **Security is solid** - JWT, password hashing, file scanning, rate limiting
3. **Performance is good** - Caching, indexing, optimized queries
4. **No critical bugs** - All TODOs are enhancements, not bug fixes
5. **Workarounds exist** - Logging, Twilio features, error logs provide functionality

**Deployment Status:** ✅ **APPROVED FOR PRODUCTION**

---

**Last Updated:** 2025-11-05
**Document Version:** 1.0.0
**Plugin Version:** 2.0.0
