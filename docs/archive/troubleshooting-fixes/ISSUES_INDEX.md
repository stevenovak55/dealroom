# MA Deal Room - Complete Issues Index

## Critical Issues (Must Fix)

### Issue #1: Account ID Hardcoding
- **File:** `/home/snova/projects/dealroom/ma-deal-room/src/REST/Controllers/CRMSyncController.php`
- **Lines:** 514-519
- **Method:** `get_current_account_id()`
- **Severity:** CRITICAL
- **Category:** Security - Multi-tenant data breach risk

### Issue #2: Unvalidated OAuth State Parameter  
- **File:** `/home/snova/projects/dealroom/ma-deal-room/src/REST/Controllers/CRMSyncController.php`
- **Lines:** 368-406
- **Method:** `oauth_callback()`
- **Severity:** CRITICAL
- **Category:** Security - CSRF vulnerability

### Issue #3: Missing CRMClientFactory::getInstance()
- **File:** `/home/snova/projects/dealroom/ma-deal-room/src/REST/Controllers/CRMSyncController.php`
- **Line:** 30
- **Severity:** CRITICAL
- **Category:** Bug - Undefined static method

### Issue #4: OAuth Callback CSRF Gap
- **File:** `/home/snova/projects/dealroom/ma-deal-room/src/REST/Controllers/CRMSyncController.php`
- **Lines:** 94-98
- **Severity:** CRITICAL
- **Category:** Security - No nonce verification

### Issue #5: getSyncStats() Signature Mismatch
- **File:** `/home/snova/projects/dealroom/ma-deal-room/src/REST/Controllers/CRMSyncController.php`
- **Line:** 172
- **Severity:** CRITICAL
- **Category:** Bug - Runtime error

---

## High Priority Issues

### Issue #6: Missing Rate Limiting on Admin Endpoints
- **File:** `/home/snova/projects/dealroom/ma-deal-room/src/REST/Controllers/CRMSyncController.php`
- **Lines:** 36-99
- **Severity:** HIGH
- **Category:** Security - DoS vulnerability

### Issue #7: Missing JSON Validation
- **File:** `/home/snova/projects/dealroom/ma-deal-room/src/REST/Controllers/CRMSyncController.php`
- **Lines:** 224, 232
- **Severity:** HIGH
- **Category:** Security - Data corruption risk

### Issue #8: Vendor Portal Incomplete
- **File:** `/home/snova/projects/dealroom/ma-deal-room/src/REST/Controllers/VendorPortalController.php`
- **Lines:** 51-84
- **Severity:** HIGH
- **Category:** Feature - Incomplete implementation

### Issue #9: Missing VendorRequest::toArray()
- **File:** `/home/snova/projects/dealroom/ma-deal-room/src/REST/Controllers/VendorPortalController.php`
- **Line:** 77
- **Severity:** HIGH
- **Category:** Bug - Undefined method call

### Issue #10: Factory Cache Not Invalidated
- **File:** `/home/snova/projects/dealroom/ma-deal-room/src/Services/Integration/CRM/CRMClientFactory.php`
- **Lines:** 33-37
- **Severity:** HIGH
- **Category:** Bug - Stale cache issue

### Issue #11: ContactSyncService Methods Missing
- **File:** `/home/snova/projects/dealroom/ma-deal-room/src/Services/Integration/CRM/ContactSyncService.php`
- **Lines:** 60-165
- **Severity:** HIGH
- **Category:** Bug - Incomplete implementation

### Issue #12: SQL Injection Risk
- **File:** `/home/snova/projects/dealroom/ma-deal-room/src/Repositories/TaskDefinitionRepository.php`
- **Lines:** 220-227
- **Severity:** HIGH
- **Category:** Security - SQL injection

### Issue #13: No CRM Endpoint Rate Limiting
- **File:** `/home/snova/projects/dealroom/ma-deal-room/src/REST/Controllers/CRMSyncController.php`
- **Lines:** 111-152, 418-460, 468-502
- **Severity:** HIGH
- **Category:** Security - DoS vulnerability

---

## Medium Priority Issues

### Issue #14: Email Service Error Handling
- **File:** `/home/snova/projects/dealroom/ma-deal-room/src/Services/EmailService.php`
- **Severity:** MEDIUM
- **Category:** Reliability

### Issue #15: EventRepository Initialization
- **File:** `/home/snova/projects/dealroom/ma-deal-room/src/REST/Controllers/BaseController.php`
- **Lines:** 195-210
- **Severity:** MEDIUM
- **Category:** Bug - Null reference

### Issue #16: Weak Password Policy
- **File:** `/home/snova/projects/dealroom/ma-deal-room/src/Services/AuthService.php`
- **Severity:** MEDIUM
- **Category:** Security

### Issue #17: Notification Queue Race Condition
- **File:** `/home/snova/projects/dealroom/ma-deal-room/src/Services/NotificationQueueService.php`
- **Severity:** MEDIUM
- **Category:** Concurrency

### Issue #18: Hardcoded Configuration
- **File:** `/home/snova/projects/dealroom/ma-deal-room/src/REST/Controllers/CRMSyncController.php`
- **Line:** 518
- **Severity:** MEDIUM
- **Category:** Configuration

### Issue #19: Missing Pagination
- **File:** `/home/snova/projects/dealroom/ma-deal-room/src/Repositories/BaseRepository.php`
- **Lines:** 192-228
- **Severity:** MEDIUM
- **Category:** Performance

### Issue #20: Insufficient CRM Logging
- **File:** `/home/snova/projects/dealroom/ma-deal-room/src/Services/Integration/CRM/`
- **Severity:** MEDIUM
- **Category:** Observability

### Issue #21: No Backup Before Delete
- **File:** `/home/snova/projects/dealroom/ma-deal-room/src/Repositories/NotificationRepository.php`
- **Lines:** 110-122
- **Severity:** MEDIUM
- **Category:** Data protection

### Issue #22: Incomplete DocuSign Webhook
- **File:** `/home/snova/projects/dealroom/ma-deal-room/src/REST/Controllers/DocuSignController.php`
- **Severity:** MEDIUM
- **Category:** Feature - Incomplete

---

## Low Priority Issues

### Issue #23: Code Style Inconsistencies
- **Issue:** Mixed snake_case/camelCase
- **Severity:** LOW
- **Category:** Code quality

### Issue #24: Missing JSDoc Comments
- **File:** `/home/snova/projects/dealroom/ma-deal-room/assets/admin/src/api/`
- **Severity:** LOW
- **Category:** Documentation

### Issue #25: Incomplete Error Messages
- **Severity:** LOW
- **Category:** UX

### Issue #26: Missing Unit Tests
- **Severity:** LOW
- **Category:** Testing

### Issue #27: Deprecated PHP Functions
- **Severity:** LOW
- **Category:** Compatibility

---

## Files by Issue Count

1. **src/REST/Controllers/CRMSyncController.php** - 6+ issues
   - Account ID (CRITICAL)
   - OAuth state validation (CRITICAL)
   - Factory getInstance (CRITICAL)
   - Sync stats (CRITICAL)
   - Rate limiting (HIGH)
   - JSON validation (HIGH)
   - Rate limiting (HIGH)
   - Hardcoded config (MEDIUM)

2. **src/REST/Controllers/VendorPortalController.php** - 2 issues
   - Incomplete update (HIGH)
   - Missing toArray() (HIGH)

3. **src/Services/Integration/CRM/CRMClientFactory.php** - 1 issue
   - Cache invalidation (HIGH)

4. **src/Services/Integration/CRM/ContactSyncService.php** - 1 issue
   - Missing methods (HIGH)

5. **src/Repositories/TaskDefinitionRepository.php** - 1 issue
   - SQL injection (HIGH)

6. **src/REST/Controllers/BaseController.php** - 1 issue
   - EventRepository init (MEDIUM)

7. **src/Services/EmailService.php** - 1 issue
   - Error handling (MEDIUM)

8. **src/Services/AuthService.php** - 1 issue
   - Password policy (MEDIUM)

9. **src/Services/NotificationQueueService.php** - 1 issue
   - Race condition (MEDIUM)

10. **src/Repositories/BaseRepository.php** - 1 issue
    - Pagination (MEDIUM)

11. **src/Repositories/NotificationRepository.php** - 1 issue
    - No backup (MEDIUM)

12. **src/REST/Controllers/DocuSignController.php** - 1 issue
    - Webhook incomplete (MEDIUM)

---

## Priority Fix Order

### Day 1 (Must Fix Today)
1. Issue #2 - OAuth state validation
2. Issue #3 - Factory getInstance

### Day 2-3 (Critical Path)
3. Issue #1 - Account ID resolution
4. Issue #4 - OAuth CSRF check
5. Issue #5 - getSyncStats() method

### Week 1 (Before ANY deployment)
6. Issue #8 - Vendor portal complete
7. Issue #9 - VendorRequest toArray()
8. Issue #10 - Cache invalidation
9. Issue #12 - SQL injection fix
10. Issue #6 - Rate limiting

### Week 2 (Before production launch)
11. Issue #7 - JSON validation
12. Issue #11 - Contact sync methods
13. Issue #13 - CRM rate limiting
14. Issue #15 - EventRepository
15. Issue #16 - Password policy

### Week 3+ (Before full rollout)
All remaining MEDIUM and LOW priority issues

---

## Testing Checklist

- [ ] Account isolation test (Issue #1)
- [ ] OAuth CSRF test (Issue #2, #4)
- [ ] CRM Factory instantiation (Issue #3)
- [ ] getSyncStats endpoint test (Issue #5)
- [ ] Vendor portal update (Issue #8, #9)
- [ ] Cache invalidation on token refresh (Issue #10)
- [ ] Contact sync execution (Issue #11, #12)
- [ ] Rate limiting under load (Issue #6, #13)
- [ ] JSON validation with invalid input (Issue #7)
- [ ] All endpoints with null event_repo (Issue #15)

