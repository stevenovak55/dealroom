# Cache Invalidation Issues - Troubleshooting Guide

**Document Version**: 1.0
**Date**: November 4, 2025
**Plugin Version**: 2.0.0

---

## Problem Overview

This document describes cache invalidation issues encountered during development where transactions would not appear immediately after creation or would remain visible after deletion. This guide will help diagnose and resolve similar issues in the future.

---

## Symptoms

### Symptom 1: New Records Not Appearing
- User creates a new transaction via dashboard
- Transaction is saved successfully (HTTP 200/201 response)
- User returns to dashboard list
- **New transaction does not appear in the list**
- Refreshing the page multiple times doesn't help
- Record eventually appears after 5+ minutes

### Symptom 2: Deleted Records Still Visible
- User deletes a transaction from dashboard
- Delete request succeeds (HTTP 200 response)
- User returns to dashboard list
- **Deleted transaction still appears in the list**
- Clicking the record shows "Transaction not found" error
- Record disappears from list after 5+ minutes

### Symptom 3: Updated Records Show Old Data
- User updates a transaction (e.g., changes price or status)
- Update request succeeds
- Dashboard still shows old values
- Updated values appear after several minutes

---

## Root Causes

This issue has **two distinct layers** that must both be addressed:

### Layer 1: Backend Cache (WordPress Transients)

**File**: `src/Repositories/BaseRepository.php`

**Problem**:
- The `invalidateCache()` method relies on the `$cache_service` object
- When `$cache_service` is `null` or unavailable, cache invalidation silently fails
- WordPress transients in the `wp_options` table remain stale
- Subsequent GET requests return cached data instead of fresh database queries

**Evidence**:
```
wp_ma_deal_transactions table entries: Found at least 102 cache entries
```

**Why This Happens**:
- Cache service might not be initialized during certain request contexts
- Dependency injection might fail in some scenarios
- Rate limiting or other middleware might interfere with cache service availability

### Layer 2: Frontend Cache (React Query)

**File**: `assets/admin/src/App.tsx`

**Problem**:
- React Query configured with `staleTime: 5 * 60 * 1000` (5 minutes)
- This tells React Query to consider data "fresh" for 5 minutes
- Even after successful mutations, React Query doesn't refetch data immediately
- UI shows stale data from memory cache until stale time expires

**Why This Happens**:
- Common React Query configuration for reducing unnecessary API calls
- Optimizes performance but creates UX issues when real-time updates are expected
- Mutation success doesn't automatically mark cache as stale

---

## Diagnostic Steps

### Step 1: Identify Which Layer Has the Issue

**Test Backend Cache**:
```bash
# Use curl to test API directly (bypasses frontend cache)
curl -X GET "http://localhost:8080/wp-json/ma-deal-room/v1/transactions" \
  -H "Authorization: Bearer YOUR_JWT_TOKEN"

# Create a new transaction
curl -X POST "http://localhost:8080/wp-json/ma-deal-room/v1/transactions" \
  -H "Authorization: Bearer YOUR_JWT_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{"property_address":"Test","property_city":"Boston","sale_price":100000,"template_id":1}'

# Immediately check if it appears
curl -X GET "http://localhost:8080/wp-json/ma-deal-room/v1/transactions" \
  -H "Authorization: Bearer YOUR_JWT_TOKEN"
```

**Result Analysis**:
- ✅ If new record appears immediately: **Backend cache is fine, issue is frontend**
- ❌ If new record doesn't appear: **Backend cache invalidation is failing**

**Test Frontend Cache**:
```bash
# Open browser DevTools → Network tab
# Create a new transaction in dashboard
# Check if API request is made to /transactions after creation
```

**Result Analysis**:
- ✅ If API request is made and returns new data: **Frontend is refetching correctly**
- ❌ If no API request is made: **React Query is serving stale cache**

### Step 2: Check WordPress Transients

**Query the Database**:
```sql
-- Count cached transaction entries
SELECT COUNT(*)
FROM wp_options
WHERE option_name LIKE '%_transient_ma_deal_transactions%';

-- View all cached transaction queries
SELECT option_name, SUBSTRING(option_value, 1, 100) as value_preview
FROM wp_options
WHERE option_name LIKE '%_transient_ma_deal_transactions%'
ORDER BY option_id DESC
LIMIT 20;
```

**What to Look For**:
- High count (100+) indicates extensive caching
- Old timestamps suggest cache isn't being cleared
- Presence of deleted record IDs in cache keys

### Step 3: Test Cache Invalidation Function

**Add Debug Logging**:
```php
// Temporarily add to BaseRepository->invalidateCache()
protected function invalidateCache(?int $id = null): bool {
    error_log("=== CACHE INVALIDATION CALLED ===");
    error_log("Table: " . $this->table);
    error_log("ID: " . ($id ?? 'null'));
    error_log("Cache service available: " . ($this->cache_service ? 'YES' : 'NO'));

    // ... existing code ...

    error_log("=== CACHE INVALIDATION COMPLETED ===");
    return true;
}
```

**Check Logs**:
```bash
tail -f /var/www/html/wp-content/debug.log | grep "CACHE INVALIDATION"
```

**What to Look For**:
- "Cache service available: NO" means fallback is needed
- Function not being called at all means hooks are missing

---

## Solutions

### Solution 1: Fix Backend Cache Invalidation (CRITICAL)

**File**: `src/Repositories/BaseRepository.php` (Lines 574-597)

**Add WordPress Transient Fallback**:
```php
protected function invalidateCache(?int $id = null): bool {
    // If cache service is available, use it
    if ($this->cache_service && $this->cache_enabled) {
        // Invalidate specific record cache
        if ($id !== null) {
            $cache_key = $this->getCacheKey('find', $id);
            $this->cache_service->delete($cache_key);
        }
        // Invalidate using tag (will clear all cached queries for this table)
        $this->cache_service->flushTag($this->table);
    }

    // FALLBACK: Always clear WordPress transients for this table as a safety net
    // This ensures cache invalidation works even if cache service is unavailable
    global $wpdb;
    $table_pattern = '%_transient_ma_deal_' . $this->table . '%';
    $wpdb->query($wpdb->prepare(
        "DELETE FROM {$wpdb->prefix}options WHERE option_name LIKE %s",
        $table_pattern
    ));

    return true;
}
```

**Why This Works**:
- Cache service methods run first (optimal path)
- Transient deletion runs as safety net (always executes)
- Covers both scenarios: cache service available OR unavailable
- Direct database query ensures cache is actually cleared

### Solution 2: Fix Frontend Cache Staleness (CRITICAL)

**File**: `assets/admin/src/App.tsx` (Lines 9-18)

**Change staleTime to 0**:
```typescript
const queryClient = new QueryClient({
  defaultOptions: {
    queries: {
      refetchOnWindowFocus: false,
      retry: 1,
      staleTime: 0, // Always consider data stale, refetch immediately after mutations
      gcTime: 5 * 60 * 1000, // Keep unused data in cache for 5 minutes (formerly cacheTime)
    },
  },
});
```

**Why This Works**:
- `staleTime: 0` tells React Query data is immediately stale
- After mutations (create/update/delete), queries marked as stale are refetched
- User sees fresh data immediately after actions
- `gcTime` still set to 5 minutes for performance (garbage collection of unused queries)

**After Making Changes**:
```bash
# Rebuild React app
cd ma-deal-room/assets/admin
npm run build

# Deploy to container (if using Docker)
docker cp dist/ container-name:/var/www/html/wp-content/plugins/ma-deal-room/assets/admin/
```

### Solution 3: Verify Cache Hooks Are Present

**Ensure these methods call invalidateCache()**:

**In TransactionRepository** (and all other repositories):
```php
public function create(array $data): ?Transaction {
    $transaction = parent::create($data);

    if ($transaction) {
        // CRITICAL: Invalidate cache after creation
        $this->invalidateCache();
    }

    return $transaction;
}

public function update(int $id, array $data): bool {
    $success = parent::update($id, $data);

    if ($success) {
        // CRITICAL: Invalidate cache after update
        $this->invalidateCache($id);
    }

    return $success;
}

public function delete(int $id): bool {
    $success = parent::delete($id);

    if ($success) {
        // CRITICAL: Invalidate cache after deletion
        $this->invalidateCache($id);
    }

    return $success;
}
```

---

## Manual Cache Clearing (Emergency Fix)

If cache issues occur in production and immediate fix is needed:

### Clear WordPress Transients

```bash
# Via WP-CLI
wp transient delete --all

# Via direct database query
docker exec container-name wp db query "DELETE FROM wp_options WHERE option_name LIKE '%_transient_ma_deal_%'"
```

### Clear React Query Cache

**Option 1: Hard Refresh**
- Press `Ctrl+Shift+R` (Windows/Linux) or `Cmd+Shift+R` (Mac)

**Option 2: Clear Browser Cache**
- Open DevTools → Application → Storage → Clear site data

**Option 3: Logout/Login**
- React Query cache is per-session
- New login creates fresh query client

---

## Prevention Best Practices

### 1. Always Use Fallback Cache Clearing

Never rely solely on cache service objects:
```php
// ❌ BAD - Only works if cache_service is available
if ($this->cache_service) {
    $this->cache_service->flushTag($this->table);
}

// ✅ GOOD - Always has fallback
if ($this->cache_service) {
    $this->cache_service->flushTag($this->table);
}
// Fallback: Direct transient deletion
global $wpdb;
$wpdb->query($wpdb->prepare("DELETE FROM {$wpdb->prefix}options WHERE option_name LIKE %s", $pattern));
```

### 2. Use Aggressive React Query Configuration for Admin Dashboards

For real-time admin interfaces:
```typescript
// ✅ GOOD - Immediate updates
staleTime: 0

// ❌ BAD - Delays user feedback
staleTime: 5 * 60 * 1000
```

For public-facing content:
```typescript
// ✅ GOOD - Reduces API calls
staleTime: 60 * 1000 // 1 minute
```

### 3. Add Cache Invalidation Logging (Development)

```php
// In BaseRepository->invalidateCache()
if (WP_DEBUG) {
    error_log(sprintf(
        "[CACHE] Cleared cache for table: %s, ID: %s",
        $this->table,
        $id ?? 'ALL'
    ));
}
```

### 4. Test Cache Invalidation After Every Mutation

**Automated Test Example**:
```php
public function testCacheInvalidationAfterCreate() {
    // Create initial record
    $repo = new TransactionRepository();
    $transaction = $repo->create(['property_address' => 'Test']);

    // Verify cache is empty for this query
    $cache_key = $repo->getCacheKey('find', $transaction->id);
    $cached = wp_cache_get($cache_key);
    $this->assertFalse($cached, 'Cache should be empty after create');

    // Fetch record (populates cache)
    $found = $repo->find($transaction->id);
    $cached = wp_cache_get($cache_key);
    $this->assertNotFalse($cached, 'Cache should be populated after fetch');

    // Update record
    $repo->update($transaction->id, ['property_address' => 'Updated']);

    // Verify cache is cleared
    $cached = wp_cache_get($cache_key);
    $this->assertFalse($cached, 'Cache should be cleared after update');
}
```

### 5. Monitor Transient Growth

Add to plugin health check:
```php
function ma_deal_room_check_cache_health() {
    global $wpdb;
    $count = $wpdb->get_var(
        "SELECT COUNT(*) FROM {$wpdb->prefix}options
         WHERE option_name LIKE '%_transient_ma_deal_%'"
    );

    if ($count > 500) {
        trigger_error("High transient count: {$count}. Cache may not be clearing properly.", E_USER_WARNING);
    }
}
```

---

## Related Files

### Core Files Modified
- `src/Repositories/BaseRepository.php` - Added fallback cache clearing
- `assets/admin/src/App.tsx` - Changed React Query staleTime to 0
- `src/Models/Transaction.php` - Added missing properties (prevents data loss)
- `src/REST/Controllers/TransactionController.php` - Convert empty strings to NULL

### Files to Check When Debugging
- `wp-content/debug.log` - PHP errors and cache invalidation logs
- Browser DevTools → Network tab - React Query refetch behavior
- Browser DevTools → Application → Storage - React Query cache state
- Database `wp_options` table - WordPress transients

---

## Success Indicators

After implementing the fixes, you should observe:

✅ **Immediate Visibility**:
- Create transaction → Immediately visible in dashboard list
- No page refresh needed

✅ **Immediate Removal**:
- Delete transaction → Immediately disappears from dashboard list
- No "ghost" records shown

✅ **Immediate Updates**:
- Update transaction → Changes reflect immediately
- No stale data displayed

✅ **Low Transient Count**:
```sql
-- Should return < 100 for normal usage
SELECT COUNT(*) FROM wp_options WHERE option_name LIKE '%_transient_ma_deal_%';
```

✅ **React Query Behavior**:
- DevTools Network tab shows API refetch immediately after mutations
- No 5-minute delay before new requests

---

## Version History

### Version 1.0 (November 4, 2025)
- Initial documentation
- Covers cache invalidation issues discovered in V2.0.0 development
- Includes both backend (WordPress transients) and frontend (React Query) solutions

---

## Additional Resources

- React Query Documentation: https://tanstack.com/query/latest/docs/react/guides/caching
- WordPress Transients API: https://developer.wordpress.org/apis/transients/
- Repository Pattern Best Practices: https://designpatternsphp.readthedocs.io/en/latest/More/Repository/README.html

---

**Note**: This guide is based on real issues encountered during development. The solutions have been tested and verified working in production.
