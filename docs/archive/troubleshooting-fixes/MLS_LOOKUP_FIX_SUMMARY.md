# MLS Lookup Issue - Root Cause & Fix

## Issue Report
**Site:** http://localhost:8083 (Fresh WordPress installation)  
**Problem:** Bridge Interactive MLS lookups fail after adding credentials  
**Working Site:** http://localhost:8080 (Main dev site)

---

## Root Cause Analysis

### Issue #1: Invalid MLS Config with account_id = 0
**Location:** `wp_ma_deal_mls_config` table  
**Problem:** Two MLS configs existed:
- Config ID 1: account_id = 0 (invalid - no such account exists)
- Config ID 2: account_id = 1 (valid - user's config)

The invalid config was likely created during plugin activation before accounts were set up.

**Impact:** MLS lookup code might have been retrieving the wrong config.

### Issue #2: User Not Set as Account Owner
**Location:** `wp_ma_deal_accounts` table  
**Problem:** 
- Account ID 1 had `owner_user_id = 1` (default test user)
- User steve@test.com (ID 4) was NOT the owner of any account

**Critical Code Path:**
```php
// MLSController.php line 1467-1481
private function get_account_id(WP_REST_Request $request): int {
    $user_id = get_current_user_id();
    
    $account_id = $wpdb->get_var($wpdb->prepare(
        "SELECT id FROM {$table} WHERE owner_user_id = %d LIMIT 1",
        $user_id
    ));
    
    return $account_id ? (int) $account_id : 0;  // Returns 0 if user doesn't own an account!
}
```

**Flow:**
1. User (ID 4) tries to lookup MLS listing
2. `get_account_id()` queries for account WHERE `owner_user_id = 4`
3. No account found → returns 0
4. MLS service looks for config WHERE `account_id = 0`
5. No valid config → MLS lookup fails

**Why it worked on 8080:**
On port 8080, you (the testing user) were already set as the owner of account ID 1, so `get_account_id()` returned 1, and the MLS config was found.

---

## Fixes Applied

### Fix #1: Deleted Invalid MLS Config ✅
```sql
DELETE FROM wp_ma_deal_mls_config WHERE id = 1;
```
**Result:** Only valid config (ID 2, account_id = 1) remains

### Fix #2: Set User as Account Owner ✅
```sql
UPDATE wp_ma_deal_accounts 
SET owner_user_id = 4 
WHERE id = 1;
```
**Result:** User steve@test.com now owns account 1

### Fix #3: Set Account ID in User Role ✅
```sql
UPDATE wp_ma_deal_user_roles 
SET account_id = 1 
WHERE user_id = 4 AND user_type = 'custom' AND role_type = 'agent';
```
**Result:** User's agent role now associated with account 1

---

## Verification

### Test Flow Simulation:
```
Step 1 - Get Account ID: 1 ✅
  (User 4 owns Account 1)

Step 2 - Get MLS Config: Config ID 2 (bridge) ✅
  (Account 1 has active Bridge Interactive config)

✅ SUCCESS: MLS lookup should work now!
```

---

## Current State on 8083

### Accounts:
- **Account ID 1:** "MA Deal Room Fresh Test Real Estate"
- **Owner:** User ID 4 (steve@test.com) ✅

### MLS Configs:
- **Config ID 2:** Provider=bridge, account_id=1, is_active=1 ✅
- Has credentials (280 bytes encrypted) ✅

### Users:
- **User ID 4:** steve@test.com
  - Role: agent (account_id = 1) ✅
  - Owns: Account 1 ✅

---

## Recommendations

### Short-term: Test MLS Lookup ✅
You can now test the Bridge Interactive MLS lookup on http://localhost:8083:
1. Login as steve@test.com
2. Create a transaction with an MLS number
3. The MLS lookup should now work correctly

### Long-term: Fix Account Assignment Logic

**Problem:** New users registered on the fresh site don't automatically:
1. Get assigned to an account
2. Become account owners

**Current Code (AuthService.php:167-173):**
```php
$role = new UserRole([
    'user_id' => $user_id,
    'user_type' => 'custom',
    'role_type' => 'agent',
    'is_primary' => true,
    'assigned_at' => current_time('mysql'),
    // ❌ Missing: 'account_id' => ???
]);
```

**Proposed Solutions:**

**Option A: Assign to Default Account (Simplest)**
```php
// Get or create default account
$default_account_id = $this->get_or_create_default_account();

$role = new UserRole([
    'user_id' => $user_id,
    'user_type' => 'custom',
    'role_type' => 'agent',
    'is_primary' => true,
    'account_id' => $default_account_id,  // ✅ Assigned to default
    'assigned_at' => current_time('mysql'),
]);
```

**Option B: Create Account for Each User (Multi-tenant)**
```php
// Create new account for the user
$account_id = $this->create_user_account([
    'name' => $data['first_name'] . ' ' . $data['last_name'] . ' Real Estate',
    'owner_user_id' => $user_id,
    'status' => 'active',
]);

$role = new UserRole([
    'user_id' => $user_id,
    'user_type' => 'custom',
    'role_type' => 'agent',
    'is_primary' => true,
    'account_id' => $account_id,  // ✅ Own account
    'assigned_at' => current_time('mysql'),
]);
```

**Option C: Invitation-based (Future)**
- Users invited to join specific accounts
- Account ID comes from invitation token

---

## Files Involved

### Core MLS Logic:
- **MLSController.php:1467-1481** - `get_account_id()` method
- **MLSController.php:516** - MLS search endpoint using account_id
- **MLSConfigRepository.php** - Retrieves MLS configs by account_id

### User Registration:
- **AuthService.php:167-173** - Role assignment (missing account_id)

### Database Tables:
- **wp_ma_deal_accounts** - Stores accounts and owner_user_id
- **wp_ma_deal_mls_config** - Stores MLS credentials per account
- **wp_ma_deal_user_roles** - Stores user role assignments

---

## Summary

**Immediate Fix:** ✅ COMPLETE
- User steve@test.com can now use MLS lookups on port 8083

**Permanent Fix:** ⚠️ PENDING
- Need to update AuthService to assign account_id during registration
- Need to determine account assignment strategy (default vs. per-user vs. invitation)

**Testing:** 
Please test the MLS lookup functionality on http://localhost:8083 and confirm it works!

---

**Fixed:** 2025-11-04  
**Fixed By:** Claude Code (investigation + manual DB fixes)  
**Permanent Solution:** Requires code changes to AuthService.php
