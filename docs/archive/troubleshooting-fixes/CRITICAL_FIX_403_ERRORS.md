# CRITICAL FIX: 403 Forbidden Errors on All REST API Endpoints

## Issue Summary

After the initial MLS configuration plugin update, **all REST API endpoints were returning 403 Forbidden errors**, causing "most items not to show" on both dev and live sites. This affected:

- Transactions list
- Analytics dashboard
- Templates
- Task definitions
- Documents
- All other data fetching

## Root Cause

Three critical issues were discovered:

### 1. Missing `admin_permission_callback` Method

Multiple controllers were calling `admin_permission_callback()` but the method **did not exist** in BaseController:

```php
// TemplateController.php line 43
'permission_callback' => [$this, 'admin_permission_callback'], // ❌ Method doesn't exist

// MLSController.php (26 routes)
'permission_callback' => [$this, 'admin_permission_callback'], // ❌ Method doesn't exist

// QueueController.php
'permission_callback' => [$this, 'admin_permission_callback'], // ❌ Method doesn't exist
```

**Error in logs:**
```
Exception: call_user_func(): Argument #1 ($callback) must be a valid callback,
class MADealRoom\REST\Controllers\TemplateController does not have a method "admin_permission_callback"
```

This caused WordPress REST API initialization to **fail silently**, resulting in all routes returning 403.

### 2. Signature Conflicts

Two controllers had their own `admin_permission_callback()` methods with **incompatible signatures**:

**AnalyticsController.php (line 331):**
```php
public function admin_permission_callback(): bool {
    return current_user_can('manage_options');
}
```

**MLSController.php (line 1373):**
```php
public function admin_permission_callback(): bool {
    return current_user_can('manage_options');
}
```

When I added the method to BaseController with the proper signature:
```php
public function admin_permission_callback(WP_REST_Request $request)
```

It caused a **fatal error** due to incompatible method signatures:
```
PHP Fatal error: Declaration of MADealRoom\REST\Controllers\AnalyticsController::admin_permission_callback(): bool
must be compatible with MADealRoom\REST\Controllers\BaseController::admin_permission_callback(WP_REST_Request $request)
```

## Solution

### Fix 1: Added Missing Method to BaseController

Added the `admin_permission_callback()` method to BaseController (line 350):

```php
/**
 * Admin-only permission callback
 * Requires user to be authenticated and have administrator capabilities
 *
 * @param WP_REST_Request $request Request object
 * @return bool|WP_Error
 */
public function admin_permission_callback(WP_REST_Request $request) {
    // First do standard auth check
    $auth_check = $this->permission_callback($request);
    if (is_wp_error($auth_check)) {
        return $auth_check;
    }

    // Check if user is administrator
    if (!$this->user_can('manage_options')) {
        return new WP_Error(
            'rest_forbidden',
            __('You must be an administrator to access this resource.', 'ma-deal-room'),
            ['status' => 403]
        );
    }

    return true;
}
```

### Fix 2: Removed Conflicting Overrides

Removed the incompatible `admin_permission_callback()` methods from:

1. **AnalyticsController.php** (removed lines 326-333)
2. **MLSController.php** (removed lines 1368-1375)

Now all controllers inherit the properly-typed method from BaseController.

## Files Modified

1. `/ma-deal-room/src/REST/Controllers/BaseController.php` - Added admin_permission_callback method
2. `/ma-deal-room/src/REST/Controllers/AnalyticsController.php` - Removed override
3. `/ma-deal-room/src/REST/Controllers/MLSController.php` - Removed override

## Verification

After applying the fixes:

✅ **REST API Root Accessible:**
```bash
curl http://localhost:8080/wp-json/ma-deal-room/v1/
# Returns full JSON with all registered routes
```

✅ **All Routes Registered:**
- Transactions endpoints: `GET /transactions` ✅
- Analytics endpoints: `GET /analytics/transactions` ✅
- Templates endpoints: `GET /templates` ✅
- MLS endpoints: `GET /mls/config` ✅
- Queue endpoints: `GET /queue/stats` ✅
- All other endpoints working ✅

✅ **No Fatal Errors:**
```bash
docker logs ma-dealroom-wp | grep -i "fatal\|error"
# No critical errors related to permission callbacks
```

✅ **Dev Site Working:**
- Dashboard loads data
- Transactions visible
- Analytics displaying
- All features functional

## Deployment Impact

This is a **critical fix** that must be applied to both dev and live sites. Without this fix:

- ❌ All REST API endpoints return 403
- ❌ Dashboard shows no data
- ❌ Transactions list empty
- ❌ Analytics not working
- ❌ Templates not accessible
- ❌ Users cannot perform any actions

With this fix:

- ✅ All REST API endpoints functional
- ✅ Dashboard displays data correctly
- ✅ All features working as expected
- ✅ MLS configuration also working

## Updated Plugin ZIP

**File:** `/home/snova/projects/dealroom/ma-deal-room-v1.0.0.zip`
**Size:** 51.07 MB
**Files:** 4,700
**Status:** ✅ Ready for deployment

**Includes ALL fixes:**
1. ✅ MLS configuration fixes (BridgeClient, MLSController, MLSClientFactory)
2. ✅ MLS database migrations (021, 022, 023)
3. ✅ Admin permission callback fix (BaseController)
4. ✅ Removed conflicting overrides (AnalyticsController, MLSController)

## Deployment Instructions

### For Dev Site (Already Applied)
✅ Dev site is working - no action needed

### For Live Site

1. **Download the updated ZIP:**
   ```bash
   scp snova@server:/home/snova/projects/dealroom/ma-deal-room-v1.0.0.zip ~/Downloads/
   ```

2. **Upload to WordPress:**
   - Go to **Plugins > Add New**
   - Click **Upload Plugin**
   - Select `ma-deal-room-v1.0.0.zip`
   - Click **Replace current with uploaded**

3. **Activate to run migrations:**
   - Go to **Plugins > Installed Plugins**
   - **Deactivate** MA Deal Room
   - **Activate** MA Deal Room
   - This runs all pending database migrations

4. **Verify:**
   - Visit admin dashboard - should load data
   - Check transactions list - should show items
   - Check browser console - no 403 errors
   - Test MLS configuration - should work

## Testing Checklist

After deployment, verify these work:

- [ ] Dashboard loads without errors
- [ ] Transactions list displays items
- [ ] Analytics charts show data
- [ ] Templates page loads
- [ ] Task definitions accessible
- [ ] Documents page works
- [ ] MLS configuration form opens
- [ ] No 403 errors in browser console
- [ ] No fatal errors in WordPress debug log

## Why This Happened

The original codebase assumed `admin_permission_callback()` existed but it was never implemented in BaseController. Some controllers created their own local versions, but when I added the proper implementation to BaseController, the signature mismatch caused fatal errors.

This is a **critical architectural issue** that prevented the entire plugin from functioning.

---

**Fixed:** 2025-11-03 03:25 UTC
**Status:** ✅ Dev site verified working
**Next:** Deploy to live site

## Support

If issues persist after deployment:

1. Check WordPress debug log for fatal errors
2. Check browser console for 403 errors
3. Verify plugin is activated
4. Verify migrations ran successfully:
   ```sql
   SELECT * FROM wp_ma_deal_migrations ORDER BY id DESC LIMIT 10;
   ```
