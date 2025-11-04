# Database Backup System

## Overview

MA Deal Room includes an automated database backup system that creates compressed backups of all plugin data, implements intelligent rotation policies, and provides easy restoration capabilities.

## Features

- **Automated Daily Backups**: Scheduled to run at 3 AM server time
- **Compression**: All backups are gzip compressed to save storage space
- **Smart Rotation**: Keeps 7 daily, 4 weekly, and 12 monthly backups
- **WP-CLI Integration**: Full command-line control
- **Secure Storage**: Backups stored outside web root with .htaccess protection
- **Easy Restoration**: Simple one-command restore process
- **Cloud Storage Ready**: Prepared for S3/cloud storage integration

## Backup Location

Backups are stored in:
```
wp-content/backups/ma-deal-room/
```

This directory is:
- Outside the web root for security
- Protected by .htaccess (Apache)
- Contains index.php to prevent directory listing
- Automatically created if it doesn't exist

## Backup Types

### Daily Backups
- Run automatically at 3 AM server time
- Keep the last 7 daily backups
- Filename pattern: `ma-deal-room-backup_daily_YYYY-MM-DD_HH-MM-SS.sql.gz`

### Weekly Backups
- Keep the last 4 weekly backups
- Filename pattern: `ma-deal-room-backup_weekly_YYYY-MM-DD_HH-MM-SS.sql.gz`

### Monthly Backups
- Keep the last 12 monthly backups
- Filename pattern: `ma-deal-room-backup_monthly_YYYY-MM-DD_HH-MM-SS.sql.gz`

### Manual Backups
- Created on-demand
- Never automatically deleted
- Filename pattern: `ma-deal-room-backup_manual_YYYY-MM-DD_HH-MM-SS.sql.gz`

## What Gets Backed Up

The backup system backs up all MA Deal Room database tables:

- `ma_deal_transactions` - Transaction data
- `ma_deal_tasks` - Task information
- `ma_deal_parties` - Party/contact details
- `ma_deal_accounts` - Account information
- `ma_deal_templates` - Transaction templates
- `ma_deal_reminders` - Reminder settings
- `ma_deal_vendor_requests` - Vendor coordination
- `ma_deal_documents` - Document metadata
- `ma_deal_events` - Audit/event log
- `ma_deal_notifications` - Notification records
- `ma_deal_task_definitions` - Task library
- `ma_deal_template_tasks` - Template task mappings
- `ma_deal_users` - Custom user system
- `ma_deal_user_roles` - User roles
- `ma_deal_user_sessions` - Active sessions
- `ma_deal_user_invitations` - Pending invitations
- `ma_deal_password_resets` - Password reset tokens
- `ma_deal_email_verifications` - Email verification tokens
- `ma_deal_two_factor_auth` - 2FA settings

**Note:** WordPress core tables and tables from other plugins are not included in the backup.

## WP-CLI Commands

### Create a Backup

```bash
# Create a manual backup
wp ma-deal backup

# Create a daily backup
wp ma-deal backup --type=daily

# Create a weekly backup
wp ma-deal backup --type=weekly

# Create a monthly backup
wp ma-deal backup --type=monthly
```

### List All Backups

```bash
# List in table format
wp ma-deal backup list

# List in JSON format
wp ma-deal backup list --format=json

# List in CSV format
wp ma-deal backup list --format=csv

# Just count backups
wp ma-deal backup list --format=count
```

### Restore from Backup

```bash
# List backups first to find the file you want
wp ma-deal backup list

# Restore from a backup (will prompt for confirmation)
wp ma-deal backup restore ma-deal-room-backup_daily_2025-11-01_03-00-00.sql.gz

# Restore without confirmation prompt
wp ma-deal backup restore ma-deal-room-backup_daily_2025-11-01_03-00-00.sql.gz --yes
```

**⚠️ WARNING:** Restoring a backup will overwrite all current MA Deal Room data!

### Delete a Backup

```bash
# Delete a specific backup
wp ma-deal backup delete ma-deal-room-backup_manual_2025-10-15_14-30-00.sql.gz

# Delete without confirmation
wp ma-deal backup delete ma-deal-room-backup_manual_2025-10-15_14-30-00.sql.gz --yes
```

### View Backup Statistics

```bash
wp ma-deal backup stats
```

Output example:
```
=== MA Deal Room Backup Statistics ===

Total Backups:     15
Total Size:        45.3 MB
Newest Backup:     2025-11-01 03:00:00
Oldest Backup:     2025-10-25 03:00:00
Backup Directory:  /var/www/html/wp-content/backups/ma-deal-room
```

### Clean Up Old Backups

```bash
# Run rotation policy manually
wp ma-deal backup cleanup
```

This removes old backups according to the rotation policy while keeping:
- 7 most recent daily backups
- 4 most recent weekly backups
- 12 most recent monthly backups
- All manual backups

## Automated Backup Schedule

### Cron Job

The plugin automatically schedules a daily backup using WordPress cron:

- **Hook:** `ma_deal_room_daily_backup`
- **Schedule:** Daily at 3 AM server time
- **Type:** Daily backup (subject to rotation)

### Checking Cron Schedule

```bash
# List all scheduled cron events
wp cron event list

# Filter for MA Deal Room backups
wp cron event list | grep ma_deal_room_daily_backup
```

### Manual Cron Execution

```bash
# Run the backup cron job now
wp cron event run ma_deal_room_daily_backup
```

## Backup Rotation Policy

The rotation system automatically removes old backups to manage storage:

### Retention Rules

| Backup Type | Retention Period | Cleanup Frequency |
|-------------|-----------------|-------------------|
| Daily       | Keep last 7     | After each backup |
| Weekly      | Keep last 4     | After each backup |
| Monthly     | Keep last 12    | After each backup |
| Manual      | Indefinite      | Never auto-deleted |

### How Rotation Works

1. After each backup is created, the rotation system runs
2. Backups are grouped by type (daily, weekly, monthly, manual)
3. Within each type, backups are sorted by age (newest first)
4. Backups beyond the retention limit are deleted
5. Manual backups are never automatically deleted

## Backup File Format

### Structure

Backup files are gzip-compressed SQL dumps containing:

1. **Header Comments**
   - Creation timestamp
   - List of tables included
   - SQL mode settings

2. **Table Definitions**
   - `DROP TABLE IF EXISTS` statements
   - `CREATE TABLE` statements

3. **Data Inserts**
   - `INSERT INTO` statements for all table data

### Compression

- Format: gzip (`.sql.gz`)
- Compression level: 9 (maximum)
- Typical compression ratio: 10:1 to 20:1
- Example: 50 MB uncompressed → 2.5-5 MB compressed

## Restoration Process

### What Happens During Restore

1. Backup file is decompressed (if `.gz`)
2. SQL content is read and parsed
3. Each table is dropped (if exists)
4. Tables are recreated with original structure
5. Data is inserted into tables
6. Decompressed temp file is deleted (if applicable)

### Important Notes

- ⚠️ **All current MA Deal Room data will be lost**
- WordPress core tables are not affected
- Other plugin data is not affected
- Only MA Deal Room tables are restored
- Transactions are not used (each statement executed individually)

### Before Restoring

Always create a backup of your current data before restoring:

```bash
# Create a pre-restore backup
wp ma-deal backup --type=manual

# Then restore
wp ma-deal backup restore old-backup.sql.gz
```

## Troubleshooting

### Backup Failed

**Check error log:**
```bash
tail -f wp-content/debug.log | grep "MA Deal Room"
```

**Common issues:**
- Insufficient disk space
- Permission denied on backup directory
- Database connection timeout for large tables
- PHP memory limit reached

**Solutions:**
- Free up disk space
- Check directory permissions (should be writable by web server)
- Increase `max_execution_time` in php.ini
- Increase `memory_limit` in php.ini or wp-config.php

### Backup Directory Not Created

```bash
# Check if wp-content is writable
ls -la wp-content/

# Manually create directory
mkdir -p wp-content/backups/ma-deal-room
chmod 755 wp-content/backups/ma-deal-room
chown www-data:www-data wp-content/backups/ma-deal-room
```

### Restore Failed

**Check the backup file:**
```bash
# Verify file exists
ls -lh wp-content/backups/ma-deal-room/

# Test gzip integrity
gzip -t wp-content/backups/ma-deal-room/backup-file.sql.gz

# View first few lines
zcat wp-content/backups/ma-deal-room/backup-file.sql.gz | head -20
```

**Common issues:**
- Corrupted backup file
- SQL syntax errors
- Table name conflicts
- Foreign key constraint failures

### Cron Not Running

```bash
# Check if cron is scheduled
wp cron event list | grep ma_deal

# Manually schedule if missing
wp cron event schedule ma_deal_room_daily_backup daily

# Test cron execution
wp cron event run ma_deal_room_daily_backup
```

## Security Best Practices

### ✅ Do

- Keep backup directory outside web root
- Use strong file permissions (755 for directories, 644 for files)
- Regularly test backup restoration
- Monitor backup success/failure
- Keep backups offsite (cloud storage)
- Encrypt backups before uploading to cloud
- Rotate backups regularly

### ❌ Don't

- Store backups in publicly accessible directories
- Use predictable backup filenames
- Keep only one backup copy
- Ignore backup failures
- Store backups on the same physical disk as database
- Allow world-writable permissions on backup directory

## Cloud Storage Integration

### Preparation

The system is ready for cloud storage integration. To enable:

1. Install AWS SDK for PHP:
   ```bash
   composer require aws/aws-sdk-php
   ```

2. Set environment variables:
   ```bash
   AWS_S3_BACKUP_BUCKET=your-backup-bucket
   AWS_ACCESS_KEY_ID=your-access-key
   AWS_SECRET_ACCESS_KEY=your-secret-key
   AWS_DEFAULT_REGION=us-east-1
   ```

3. The BackupService will automatically upload new backups to S3

### Future Enhancements

Planned cloud storage features:
- S3 bucket configuration
- Automatic upload after backup
- Download from S3 for restoration
- Lifecycle policies integration
- Encryption at rest
- Cross-region replication

## Monitoring and Alerts

### Backup Success/Failure Hooks

WordPress actions for monitoring:

```php
// Backup completed successfully
add_action('ma_deal_room_backup_completed', function($backup_info) {
    // Send success notification
    error_log('Backup completed: ' . $backup_info['filename']);
});

// Backup failed
add_action('ma_deal_room_backup_failed', function($error) {
    // Send failure alert
    error_log('Backup failed: ' . $error->get_error_message());
    // Optional: Send email to admin
});
```

### Email Notifications

To receive email notifications for backups:

```php
// In your theme's functions.php or custom plugin
add_action('ma_deal_room_backup_completed', function($backup_info) {
    wp_mail(
        get_option('admin_email'),
        'MA Deal Room - Backup Successful',
        sprintf(
            "Backup completed successfully:\n\nFile: %s\nSize: %s\nTables: %d\nTime: %ss",
            $backup_info['filename'],
            $backup_info['size_formatted'],
            $backup_info['tables_count'],
            $backup_info['execution_time']
        )
    );
});

add_action('ma_deal_room_backup_failed', function($error) {
    wp_mail(
        get_option('admin_email'),
        'MA Deal Room - Backup Failed',
        'Backup failed: ' . $error->get_error_message()
    );
});
```

## Performance Considerations

### Backup Duration

Typical backup times:
- Small database (< 100 MB): 1-5 seconds
- Medium database (100-500 MB): 5-30 seconds
- Large database (> 500 MB): 30+ seconds

### Resource Usage

- **Memory**: Approximately 2x the size of largest table
- **CPU**: Moderate during compression
- **Disk I/O**: High during backup creation
- **Network**: High if uploading to cloud storage

### Optimization Tips

1. **Schedule during low-traffic periods** (e.g., 3 AM)
2. **Increase PHP limits** for large databases:
   ```php
   ini_set('memory_limit', '512M');
   ini_set('max_execution_time', 300);
   ```
3. **Use compression** (already enabled by default)
4. **Consider incremental backups** for very large databases (future feature)

## Support

For backup-related issues:
1. Check the WordPress debug log (`wp-content/debug.log`)
2. Run `wp ma-deal backup stats` to verify configuration
3. Test with a manual backup: `wp ma-deal backup`
4. Review server disk space and permissions
5. Contact support: dev@madealroom.com

## Version History

- **v1.0.0** (2025-11-01): Initial backup system implementation
  - Automated daily backups at 3 AM
  - Gzip compression
  - Smart rotation (7 daily, 4 weekly, 12 monthly)
  - Full WP-CLI integration
  - Secure storage with .htaccess protection
  - Easy restoration
  - Cloud storage preparation
