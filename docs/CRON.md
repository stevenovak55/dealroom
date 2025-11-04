# MA Deal Room - Cron Job Configuration

**Last Updated**: 2025-10-30

This document explains how to configure automated background jobs for the MA Deal Room plugin using WP-CLI and system cron.

---

## Overview

The MA Deal Room plugin requires periodic background processing for:
- **Reminder Queue Processing**: Send scheduled email/SMS reminders
- **Failed Reminder Retry**: Retry failed reminders with exponential backoff
- **Queue Status Monitoring**: Track reminder delivery performance

All background jobs are executed via **WP-CLI commands** that can be triggered by system cron.

---

## Available WP-CLI Commands

### 1. Process Pending Reminders
```bash
wp ma-deal reminders:send [--limit=<number>]
```

**Purpose**: Process and send pending reminders that are due

**Options**:
- `--limit=<number>` - Maximum reminders to process (default: 100)

**Example**:
```bash
wp ma-deal reminders:send --limit=50
```

**Output**:
```
Processing up to 50 pending reminders...
Processed: 47 | Sent: 45 | Failed: 2
Success: Reminder processing completed.
```

---

### 2. Retry Failed Reminders
```bash
wp ma-deal reminders:retry
```

**Purpose**: Retry failed reminders using exponential backoff

**Backoff Schedule**:
- Retry 0: Immediate (0 minutes)
- Retry 1: 2 minutes after failure
- Retry 2: 4 minutes after failure
- Retry 3: 8 minutes after failure (final attempt)

**Example**:
```bash
wp ma-deal reminders:retry
```

**Output**:
```
Retrying failed reminders with exponential backoff...
Retried: 5 | Succeeded: 3 | Failed: 2
Warning: 2 reminder(s) failed retry and will be attempted again with longer backoff.
Success: Retry processing completed.
```

---

### 3. Check Queue Status
```bash
wp ma-deal reminders:status
```

**Purpose**: Display current reminder queue statistics

**Example**:
```bash
wp ma-deal reminders:status
```

**Output**:
```
=== Reminder Queue Status ===

Pending        : 127
Sent           : 1543
Failed         : 8
Cancelled      : 12

Warning: 15 overdue reminders need processing!

24 reminders scheduled for next 24 hours
Success: Status check completed.
```

---

## Cron Job Setup

### Recommended Schedule

| Command | Frequency | Cron Expression | Purpose |
|---------|-----------|----------------|---------|
| `reminders:send` | Every 5 minutes | `*/5 * * * *` | Process due reminders |
| `reminders:retry` | Every 10 minutes | `*/10 * * * *` | Retry failed sends |
| `reminders:status` | Every hour | `0 * * * *` | Monitor queue health |

---

### Linux/Unix Cron Configuration

**1. Edit crontab**:
```bash
crontab -e
```

**2. Add cron jobs**:
```cron
# MA Deal Room - Reminder Processing
*/5 * * * * /usr/local/bin/wp ma-deal reminders:send --path=/var/www/html >> /var/log/ma-deal-reminders.log 2>&1
*/10 * * * * /usr/local/bin/wp ma-deal reminders:retry --path=/var/www/html >> /var/log/ma-deal-reminders.log 2>&1
0 * * * * /usr/local/bin/wp ma-deal reminders:status --path=/var/www/html >> /var/log/ma-deal-status.log 2>&1
```

**Important Notes**:
- Replace `/usr/local/bin/wp` with your WP-CLI path (find with `which wp`)
- Replace `/var/www/html` with your WordPress installation path
- Ensure log directory `/var/log/` is writable by cron user

---

### WordPress Cron Alternative

If you cannot access system cron, you can use WordPress cron (less reliable):

**Add to `functions.php` or custom plugin**:
```php
<?php
// Schedule reminder processing
if (!wp_next_scheduled('ma_deal_process_reminders')) {
    wp_schedule_event(time(), 'every_five_minutes', 'ma_deal_process_reminders');
}

add_action('ma_deal_process_reminders', function() {
    if (defined('WP_CLI') && WP_CLI) {
        WP_CLI::runcommand('ma-deal reminders:send --limit=100');
    } else {
        // Fallback: Direct service call
        $plugin = \MADealRoom\Core\Plugin::instance();
        $reminder_service = $plugin->container()->get('reminder_service');
        $reminder_service->processQueue(100);
    }
});

// Register custom schedule
add_filter('cron_schedules', function($schedules) {
    $schedules['every_five_minutes'] = [
        'interval' => 300,
        'display' => __('Every 5 Minutes')
    ];
    return $schedules;
});
```

**Disable WordPress Cron (Recommended)**:

If using system cron, disable WordPress cron in `wp-config.php`:
```php
define('DISABLE_WP_CRON', true);
```

Then trigger WordPress cron via system cron:
```cron
*/5 * * * * curl https://your-site.com/wp-cron.php?doing_wp_cron >/dev/null 2>&1
```

---

## Docker Environment Setup

For Docker-based development (see `docker-compose.yml`):

**Add to `docker-compose.yml`**:
```yaml
services:
  wordpress:
    # ... existing config
    volumes:
      - ./cron/ma-deal-cron:/etc/cron.d/ma-deal-cron:ro

  cron:
    image: wordpress:cli
    volumes:
      - ./ma-deal-room:/var/www/html/wp-content/plugins/ma-deal-room
    command: sh -c "while true; do wp ma-deal reminders:send --path=/var/www/html; sleep 300; done"
    depends_on:
      - wordpress
```

**Create `cron/ma-deal-cron`**:
```cron
*/5 * * * * www-data cd /var/www/html && wp ma-deal reminders:send >> /var/log/cron.log 2>&1
*/10 * * * * www-data cd /var/www/html && wp ma-deal reminders:retry >> /var/log/cron.log 2>&1
```

---

## Monitoring & Alerts

### Check Cron Execution

**Verify cron is running**:
```bash
grep "ma-deal" /var/log/syslog
```

**Check reminder logs**:
```bash
tail -f /var/log/ma-deal-reminders.log
```

### Alert on Failures

**Email on high failure rate** (add to crontab):
```bash
0 */6 * * * FAILED=$(wp ma-deal reminders:status --path=/var/www/html | grep -oP 'Failed\s+:\s+\K\d+'); if [ "$FAILED" -gt 20 ]; then echo "High reminder failure rate: $FAILED" | mail -s "MA Deal Room Alert" admin@example.com; fi
```

### Database Query for Monitoring

**Find stuck reminders**:
```sql
SELECT COUNT(*) as stuck_reminders
FROM wp_ma_deal_reminders
WHERE status = 'pending'
  AND scheduled_at < NOW() - INTERVAL 1 HOUR;
```

**Find reminders with max retries exhausted**:
```sql
SELECT *
FROM wp_ma_deal_reminders
WHERE status = 'failed'
  AND retry_count >= max_retries
ORDER BY updated_at DESC
LIMIT 20;
```

---

## Performance Considerations

### Batch Size Tuning

**Low-traffic sites** (< 50 transactions/month):
```bash
wp ma-deal reminders:send --limit=50
```

**High-traffic sites** (> 500 transactions/month):
```bash
wp ma-deal reminders:send --limit=200
```

### Email Sending Limits

**Shared Hosting**:
- Many hosts limit emails to 100-300/hour
- Use `--limit` to stay within limits
- Consider SendGrid/Mailgun for higher volume

**SendGrid Integration** (future):
```php
// Future implementation
define('MA_DEAL_EMAIL_PROVIDER', 'sendgrid');
define('MA_DEAL_SENDGRID_API_KEY', 'your-api-key');
```

---

## Troubleshooting

### Reminders Not Sending

**Check cron is running**:
```bash
systemctl status cron  # Linux
launchctl list | grep cron  # macOS
```

**Verify WP-CLI path**:
```bash
which wp
wp --info
```

**Test command manually**:
```bash
wp ma-deal reminders:send --path=/var/www/html
```

### High Failure Rate

**Common causes**:
1. **Invalid recipient emails**: Check `recipient_email` field
2. **SMTP configuration**: Verify `wp_mail()` settings
3. **Rate limiting**: Reduce `--limit` or increase interval
4. **Missing task/transaction**: Check foreign key integrity

**Debug**:
```bash
# Enable WordPress debug mode
define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true);

# Check debug log
tail -f wp-content/debug.log
```

---

## Best Practices

1. **Use system cron** instead of WordPress cron for reliability
2. **Monitor logs regularly** to catch delivery issues early
3. **Set up alerts** for high failure rates
4. **Adjust batch size** based on email sending limits
5. **Rotate logs** to prevent disk space issues
6. **Test cron jobs** after deployment

---

## Future Enhancements

Planned for v0.3.0 (Q2 2026):
- SMS delivery via Twilio integration
- SendGrid/Mailgun provider support
- Advanced retry strategies (per-recipient backoff)
- Delivery analytics dashboard
- Rate limiting per account

---

**Maintained by**: MA Deal Room Product Team  
**Questions**: File an issue in the GitHub repository

