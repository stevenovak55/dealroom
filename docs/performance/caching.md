# MA Deal Room - Caching Guide

**Version:** 1.0.0
**Last Updated:** 2025-11-01
**Author:** AI Agent (Claude Code)

## Table of Contents

1. [Overview](#overview)
2. [Cache Backends](#cache-backends)
3. [Getting Started](#getting-started)
4. [Basic Usage](#basic-usage)
5. [Advanced Features](#advanced-features)
6. [Cache Key Naming Conventions](#cache-key-naming-conventions)
7. [TTL Recommendations](#ttl-recommendations)
8. [Cache Tagging](#cache-tagging)
9. [Cache Warming](#cache-warming)
10. [Statistics & Monitoring](#statistics--monitoring)
11. [Best Practices](#best-practices)
12. [Troubleshooting](#troubleshooting)

---

## Overview

The MA Deal Room plugin uses a **CacheService** that provides a unified caching interface with automatic fallback from Redis to WordPress transients. This ensures your application remains performant even when Redis is unavailable.

### Key Features

- ✅ **Redis Support** - High-performance caching for production
- ✅ **WordPress Transients Fallback** - Automatic fallback when Redis unavailable
- ✅ **Cache Tagging** - Group related cache entries for bulk invalidation
- ✅ **TTL Management** - Flexible time-to-live configuration
- ✅ **Statistics Tracking** - Monitor cache hit/miss rates
- ✅ **PSR-16 Like Interface** - Simple, intuitive API
- ✅ **Automatic Serialization** - Handles complex data types automatically

---

## Cache Backends

### Redis (Preferred)

**Pros:**
- Ultra-fast in-memory storage
- Supports advanced features (tagging, atomic operations)
- Scales horizontally
- Persistent across PHP requests
- Low latency (sub-millisecond)

**Cons:**
- Requires Redis server running
- Additional infrastructure to manage

**When to Use:** Production environments, high-traffic sites

### WordPress Transients (Fallback)

**Pros:**
- No additional infrastructure required
- Works out-of-the-box
- Integrates with WordPress database

**Cons:**
- Slower than Redis (database queries)
- Limited tagging support
- Can bloat wp_options table

**When to Use:** Development, low-traffic sites, when Redis unavailable

---

## Getting Started

### Configuration

**1. Environment Variables (.env)**

```bash
# Redis Configuration
REDIS_HOST=redis          # Docker service name or IP
REDIS_PORT=6379           # Default Redis port
```

**2. Docker Setup**

The Redis service is pre-configured in `docker-compose.yml`:

```yaml
services:
  redis:
    image: redis:7-alpine
    container_name: ma-dealroom-redis
    ports:
      - "6380:6379"
    volumes:
      - redis_data:/data
```

**3. Start Services**

```bash
docker-compose up -d
```

### Verify Installation

```bash
# Check if Redis is running
docker ps | grep redis

# Test Redis connection
docker exec -it ma-dealroom-redis redis-cli ping
# Expected output: PONG
```

---

## Basic Usage

### Get Instance

```php
<?php
// Method 1: Via Service Container (recommended)
$cache = $container->get('cache_service');

// Method 2: Direct instantiation
$cache = new \MADealRoom\Services\CacheService();
```

### Basic Operations

#### Set a Value

```php
// Set with default TTL (1 hour)
$cache->set('user_123_profile', $user_data);

// Set with custom TTL (5 minutes = 300 seconds)
$cache->set('api_response_latest', $data, 300);

// Set with 24 hour TTL
$cache->set('daily_stats', $stats, 86400);
```

#### Get a Value

```php
// Get value (returns null if not found)
$user_data = $cache->get('user_123_profile');

// Get with default value
$user_data = $cache->get('user_123_profile', [
    'name' => 'Guest',
    'role' => 'visitor'
]);
```

#### Check if Key Exists

```php
if ($cache->has('user_123_profile')) {
    // Key exists in cache
}
```

#### Delete a Value

```php
$cache->delete('user_123_profile');
```

#### Remember Pattern (Get or Set)

```php
// Get from cache, or compute and store if not found
$expensive_data = $cache->remember('expensive_query_result', function() {
    // This only runs if cache miss
    return perform_expensive_database_query();
}, 3600); // Cache for 1 hour
```

---

## Advanced Features

### Cache Tagging

Group related cache entries for bulk invalidation.

```php
// Set a value and tag it
$cache->set('user_123_transactions', $transactions);
$cache->tag('user_123_transactions', 'transactions');
$cache->tag('user_123_transactions', ['user_123', 'financial_data']);

// Later, flush all entries with a specific tag
$cache->flushTag('transactions'); // Clears all transaction caches
$cache->flushTag('user_123');     // Clears all user 123 caches
```

**Note:** Tagging only works with Redis. Transients fallback ignores tags.

### Flush All Cache

```php
// Clear ALL cache entries
$cache->flush();
```

⚠️ **Warning:** This clears the entire cache. Use sparingly!

### Get Statistics

```php
$stats = $cache->getStats();

/*
Array
(
    [hits] => 150
    [misses] => 25
    [sets] => 30
    [deletes] => 5
    [hit_rate] => 85.71%
    [backend] => Redis
    [redis_version] => 7.2.0
    [redis_memory] => 1.2M
    [redis_keys] => 45
)
*/

echo "Cache Hit Rate: " . $stats['hit_rate'];
echo "Backend: " . $stats['backend'];
```

### Check Backend

```php
if ($cache->isRedisAvailable()) {
    echo 'Using Redis for caching';
} else {
    echo 'Using WordPress transients for caching';
}
```

---

## Cache Key Naming Conventions

Use descriptive, hierarchical key names:

### Format

```
{entity}_{id}_{data_type}
{entity}_{id}_{action}_{params}
{scope}_{entity}_{filter}
```

### Examples

```php
// User data
'user_123_profile'
'user_123_transactions'
'user_123_settings'
'user_123_permissions'

// Transaction data
'transaction_456_details'
'transaction_456_tasks'
'transaction_456_parties'
'transaction_456_documents'

// Template data
'template_789_yaml'
'template_789_tasks_residential'
'template_789_tasks_commercial'

// Query results
'query_transactions_active_page_1'
'query_tasks_due_today'
'query_users_by_role_buyer'

// API responses
'api_sendgrid_stats_2025_11_01'
'api_twilio_balance'
```

### Key Length

- Keep keys under 250 characters
- Use abbreviations for long entity names if needed
- Avoid special characters except `_` and `-`

---

## TTL Recommendations

| Data Type | Recommended TTL | Reasoning |
|-----------|----------------|-----------|
| User profile | 1 hour (3600s) | Changes infrequently |
| User permissions | 5 minutes (300s) | Security-sensitive |
| Transaction list | 5 minutes (300s) | Updated frequently |
| Transaction details | 15 minutes (900s) | Moderate update frequency |
| YAML templates | 24 hours (86400s) | Rarely change |
| Parsed task lists | 1 hour (3600s) | Static once generated |
| API responses | 5-15 minutes | External data |
| Daily statistics | 24 hours (86400s) | Updated once per day |
| Session data | Session lifetime | User-specific |
| Search results | 5 minutes (300s) | Dynamic content |

### TTL Guidelines

- **Shorter TTL (1-5 min):** Frequently changing data, real-time requirements
- **Medium TTL (15-60 min):** Balance between freshness and performance
- **Longer TTL (1-24 hours):** Rarely changing, expensive to compute
- **No TTL:** Never expires (use with extreme caution)

---

## Cache Warming

Pre-populate cache with frequently accessed data.

### On Application Startup

```php
// In Plugin.php or initialization hook
add_action('init', function() use ($container) {
    $cache = $container->get('cache_service');

    // Warm up common queries
    warm_up_cache($cache);
});

function warm_up_cache($cache) {
    // Pre-cache active transactions
    $transactions = get_active_transactions();
    $cache->set('query_transactions_active', $transactions, 3600);

    // Pre-cache user roles
    $roles = get_all_user_roles();
    $cache->set('query_user_roles', $roles, 86400);

    // Pre-cache templates
    $templates = get_all_templates();
    foreach ($templates as $template) {
        $parsed = parse_template_yaml($template->id);
        $cache->set("template_{$template->id}_yaml", $parsed, 86400);
    }
}
```

### On Template Update

```php
// When template is updated, warm cache immediately
function on_template_updated($template_id) {
    $cache = $container->get('cache_service');

    // Parse and cache immediately
    $parsed_yaml = parse_template_yaml($template_id);
    $cache->set("template_{$template_id}_yaml", $parsed_yaml, 86400);

    // Generate and cache task lists for each property type
    foreach (['residential', 'commercial', 'land'] as $type) {
        $tasks = generate_tasks($template_id, $type);
        $cache->set("template_{$template_id}_tasks_{$type}", $tasks, 3600);
    }
}
```

---

## Statistics & Monitoring

### Real-Time Statistics

```php
// Get statistics periodically
$stats = $cache->getStats();

// Log to monitoring service
error_log(json_encode([
    'timestamp' => time(),
    'cache_hit_rate' => $stats['hit_rate'],
    'cache_backend' => $stats['backend'],
    'cache_keys' => $stats['redis_keys'] ?? 'N/A'
]));
```

### Add to Admin Dashboard

```php
// Display cache stats in WordPress admin
add_action('admin_menu', function() {
    add_submenu_page(
        'ma-deal-room',
        'Cache Statistics',
        'Cache Stats',
        'manage_options',
        'ma-deal-cache-stats',
        'render_cache_stats_page'
    );
});

function render_cache_stats_page() {
    $container = ma_deal_room_container();
    $cache = $container->get('cache_service');
    $stats = $cache->getStats();

    ?>
    <div class="wrap">
        <h1>Cache Statistics</h1>
        <table class="widefat">
            <tr><th>Backend</th><td><?php echo esc_html($stats['backend']); ?></td></tr>
            <tr><th>Hit Rate</th><td><?php echo esc_html($stats['hit_rate']); ?></td></tr>
            <tr><th>Hits</th><td><?php echo esc_html($stats['hits']); ?></td></tr>
            <tr><th>Misses</th><td><?php echo esc_html($stats['misses']); ?></td></tr>
            <?php if ($cache->isRedisAvailable()): ?>
            <tr><th>Redis Version</th><td><?php echo esc_html($stats['redis_version']); ?></td></tr>
            <tr><th>Memory Used</th><td><?php echo esc_html($stats['redis_memory']); ?></td></tr>
            <tr><th>Total Keys</th><td><?php echo esc_html($stats['redis_keys']); ?></td></tr>
            <?php endif; ?>
        </table>

        <p>
            <a href="<?php echo admin_url('admin.php?page=ma-deal-cache-stats&action=flush'); ?>"
               class="button button-secondary"
               onclick="return confirm('Are you sure you want to flush the entire cache?')">
                Flush Cache
            </a>
        </p>
    </div>
    <?php
}
```

---

## Best Practices

### 1. Cache Invalidation Strategy

**When to Invalidate:**

```php
// On data update
function update_transaction($transaction_id, $data) {
    // Update database
    $repository->update($transaction_id, $data);

    // Invalidate related caches
    $cache->delete("transaction_{$transaction_id}_details");
    $cache->delete("transaction_{$transaction_id}_tasks");
    $cache->flushTag("transaction_{$transaction_id}");
}

// On data deletion
function delete_transaction($transaction_id) {
    // Delete from database
    $repository->delete($transaction_id);

    // Invalidate all related caches
    $cache->flushTag("transaction_{$transaction_id}");
}
```

### 2. Don't Cache Everything

**DO Cache:**
- ✅ Expensive database queries
- ✅ External API responses
- ✅ Parsed templates
- ✅ Computed statistics
- ✅ Search results

**DON'T Cache:**
- ❌ User-specific data (unless tagged by user)
- ❌ Real-time data (current time, live feeds)
- ❌ Security-sensitive data (passwords, tokens)
- ❌ Data that changes frequently (< 1 second)
- ❌ Very large objects (> 1 MB)

### 3. Use Remember Pattern

```php
// Good: Simple and clean
$data = $cache->remember('expensive_query', function() {
    return perform_query();
}, 3600);

// Avoid: Manual cache checks
$data = $cache->get('expensive_query');
if ($data === null) {
    $data = perform_query();
    $cache->set('expensive_query', $data, 3600);
}
```

### 4. Namespace Your Keys

```php
// Good: Clear namespace
'template_123_yaml'
'user_456_profile'
'query_transactions_active'

// Bad: Ambiguous
'data_123'
'temp_456'
'results'
```

### 5. Monitor Cache Performance

```php
// Log cache performance periodically
if (defined('WP_DEBUG') && WP_DEBUG) {
    add_action('shutdown', function() use ($cache) {
        $stats = $cache->getStats();
        if ($stats['hit_rate'] < 50) {
            error_log('Cache hit rate is low: ' . $stats['hit_rate']);
        }
    });
}
```

---

## Troubleshooting

### Issue: Cache Not Working

**Symptoms:** No performance improvement, always using transients

**Solutions:**

1. **Check Redis is running:**
   ```bash
   docker ps | grep redis
   ```

2. **Check Redis extension loaded:**
   ```bash
   php -m | grep redis
   ```

3. **Check environment variables:**
   ```bash
   echo $REDIS_HOST
   echo $REDIS_PORT
   ```

4. **Check Redis connectivity:**
   ```bash
   docker exec -it ma-dealroom-redis redis-cli ping
   ```

5. **Check logs for errors:**
   ```bash
   tail -f wp-content/debug.log | grep "MA Deal Room"
   ```

### Issue: Cache Returns Stale Data

**Symptoms:** Updates not reflected, old data showing

**Solutions:**

1. **Check TTL is appropriate:**
   ```php
   // Too long TTL causes stale data
   $cache->set('data', $value, 86400); // 24 hours may be too long
   ```

2. **Ensure invalidation on update:**
   ```php
   function update_data($id, $new_data) {
       save_to_database($id, $new_data);
       $cache->delete("data_{$id}"); // Don't forget this!
   }
   ```

3. **Flush specific tag:**
   ```php
   $cache->flushTag('user_123');
   ```

### Issue: High Memory Usage

**Symptoms:** Redis using too much RAM

**Solutions:**

1. **Check current memory usage:**
   ```php
   $stats = $cache->getStats();
   echo $stats['redis_memory'];
   ```

2. **Reduce TTL for large objects:**
   ```php
   // Instead of 24 hours
   $cache->set('large_data', $data, 86400);

   // Use 1 hour
   $cache->set('large_data', $data, 3600);
   ```

3. **Flush unused caches:**
   ```php
   $cache->flush();
   ```

4. **Monitor key count:**
   ```bash
   docker exec ma-dealroom-redis redis-cli dbsize
   ```

### Issue: Cache Miss Rate Too High

**Symptoms:** Hit rate below 50%

**Solutions:**

1. **Check cache warming:**
   - Are common queries pre-cached?

2. **Review TTL values:**
   - Are they too short?

3. **Check key naming:**
   - Are keys consistent?

4. **Monitor which keys are missed:**
   ```php
   // Add logging in CacheService
   if ($value === false) {
       error_log("Cache miss: {$key}");
       $this->stats['misses']++;
   }
   ```

---

## Examples

### Example 1: Cache Transaction List

```php
function get_active_transactions() {
    $cache = $container->get('cache_service');

    return $cache->remember('query_transactions_active', function() {
        $repository = $container->get('transaction_repository');
        return $repository->findBy(['status' => 'active'], 100);
    }, 300); // Cache for 5 minutes
}
```

### Example 2: Cache Template Parsing

```php
function parse_template($template_id) {
    $cache = $container->get('cache_service');

    return $cache->remember("template_{$template_id}_yaml", function() use ($template_id) {
        $yaml_content = load_template_file($template_id);
        $parsed = Yaml::parse($yaml_content);
        return process_template_logic($parsed);
    }, 86400); // Cache for 24 hours
}
```

### Example 3: Cache with Tagging

```php
function cache_user_data($user_id) {
    $cache = $container->get('cache_service');

    $profile = get_user_profile($user_id);
    $cache->set("user_{$user_id}_profile", $profile, 3600);
    $cache->tag("user_{$user_id}_profile", ['users', "user_{$user_id}"]);

    $transactions = get_user_transactions($user_id);
    $cache->set("user_{$user_id}_transactions", $transactions, 300);
    $cache->tag("user_{$user_id}_transactions", ['transactions', "user_{$user_id}"]);
}

// Later, when user data changes
function on_user_updated($user_id) {
    $cache = $container->get('cache_service');
    $cache->flushTag("user_{$user_id}"); // Clears all user caches
}
```

---

## Performance Impact

### Expected Performance Gains

| Operation | Without Cache | With Redis | Improvement |
|-----------|---------------|------------|-------------|
| Template parsing | 50-100ms | 0.5-1ms | 50-100x faster |
| Query 100 transactions | 20-50ms | 0.5ms | 40-100x faster |
| API response | 200-500ms | 1ms | 200-500x faster |
| User profile load | 10-20ms | 0.2ms | 50-100x faster |

### Cache Hit Rate Goals

- **Excellent:** 90%+ hit rate
- **Good:** 70-90% hit rate
- **Needs Improvement:** 50-70% hit rate
- **Poor:** < 50% hit rate

---

## Conclusion

The CacheService provides a powerful, flexible caching solution with automatic fallback. By following best practices and monitoring performance, you can significantly improve your application's speed and scalability.

### Key Takeaways

1. ✅ Use Redis for production, transients for fallback
2. ✅ Set appropriate TTLs for each data type
3. ✅ Use cache tagging for bulk invalidation
4. ✅ Implement cache warming for common queries
5. ✅ Monitor cache statistics regularly
6. ✅ Invalidate cache on data updates
7. ✅ Use the remember pattern for clean code

---

## Resources

- [Redis Documentation](https://redis.io/documentation)
- [WordPress Transients API](https://developer.wordpress.org/apis/handbook/transients/)
- [PSR-16 Simple Cache](https://www.php-fig.org/psr/psr-16/)

---

**Questions or Issues?**
Contact the development team or file an issue in the project repository.
