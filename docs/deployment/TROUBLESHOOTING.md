# Troubleshooting Guide

This guide provides solutions to common issues encountered during deployment and operation of the MA Deal Room plugin.

## Table of Contents

1. [Installation Issues](#installation-issues)
2. [Database Issues](#database-issues)
3. [Plugin Activation Issues](#plugin-activation-issues)
4. [Frontend Issues](#frontend-issues)
5. [API Issues](#api-issues)
6. [Authentication Issues](#authentication-issues)
7. [Email/SMS Issues](#email-sms-issues)
8. [Performance Issues](#performance-issues)
9. [SSL/HTTPS Issues](#ssl-https-issues)
10. [Backup Issues](#backup-issues)
11. [Cron Job Issues](#cron-job-issues)
12. [Monitoring Issues](#monitoring-issues)

---

## Installation Issues

### Issue: Composer Install Fails

**Symptoms:**
```
Your requirements could not be resolved to an installable set of packages.
```

**Solutions:**

1. Check PHP version:
```bash
php --version
# Should be 8.0 or higher
```

2. Update Composer:
```bash
composer self-update
```

3. Clear Composer cache:
```bash
composer clear-cache
composer install --no-cache
```

4. Install missing PHP extensions:
```bash
sudo apt install php8.1-xml php8.1-mbstring php8.1-curl
```

### Issue: npm Install Fails

**Symptoms:**
```
npm ERR! code ERESOLVE
npm ERR! ERESOLVE unable to resolve dependency tree
```

**Solutions:**

1. Clear npm cache:
```bash
npm cache clean --force
rm -rf node_modules package-lock.json
npm install
```

2. Use correct Node.js version:
```bash
node --version  # Should be 18 or 20 LTS
nvm install 20
nvm use 20
npm install
```

3. Try legacy peer deps:
```bash
npm install --legacy-peer-deps
```

### Issue: Permission Denied Errors

**Symptoms:**
```
EACCES: permission denied, mkdir '/var/www/html/...'
```

**Solutions:**

```bash
# Fix ownership
sudo chown -R www-data:www-data /var/www/html/wp-content/plugins/ma-deal-room

# Fix permissions
sudo find /var/www/html/wp-content/plugins/ma-deal-room -type d -exec chmod 755 {} \;
sudo find /var/www/html/wp-content/plugins/ma-deal-room -type f -exec chmod 644 {} \;

# Run commands as www-data user
sudo -u www-data composer install
```

---

## Database Issues

### Issue: Connection Refused

**Symptoms:**
```
Error establishing a database connection
```

**Solutions:**

1. Verify MySQL is running:
```bash
sudo systemctl status mysql
sudo systemctl start mysql
```

2. Test database connection:
```bash
mysql -u dealroom_user -p -h localhost ma_dealroom
```

3. Check credentials in .env file:
```bash
cat .env | grep DB_
```

4. Verify user has permissions:
```sql
SHOW GRANTS FOR 'dealroom_user'@'localhost';
```

### Issue: Migration Fails

**Symptoms:**
```
Error running migration: Table already exists
```

**Solutions:**

1. Check current migration status:
```bash
wp ma-deal migrate:status
```

2. Rollback last migration:
```bash
wp ma-deal migrate:rollback
```

3. Force re-run specific migration:
```bash
wp db query "DELETE FROM wp_ma_migrations WHERE migration = '011_create_user_system';"
wp ma-deal migrate
```

4. Check for errors:
```bash
tail -f /var/log/nginx/error.log
```

### Issue: Slow Database Queries

**Symptoms:**
- Pages loading slowly
- High database CPU usage

**Solutions:**

1. Check slow query log:
```bash
sudo tail -f /var/log/mysql/mysql-slow.log
```

2. Optimize tables:
```bash
wp db optimize
```

3. Add missing indexes:
```sql
-- Check for missing indexes
SHOW INDEX FROM wp_ma_transactions;

-- Example: Add index if missing
ALTER TABLE wp_ma_tasks ADD INDEX idx_transaction_id (transaction_id);
```

4. Increase buffer pool:
```ini
# /etc/mysql/mysql.conf.d/mysqld.cnf
[mysqld]
innodb_buffer_pool_size = 2G
```

---

## Plugin Activation Issues

### Issue: Plugin Won't Activate

**Symptoms:**
```
The plugin does not have a valid header.
```

**Solutions:**

1. Check plugin file exists:
```bash
ls -la /var/www/html/wp-content/plugins/ma-deal-room/ma-deal-room.php
```

2. Check PHP syntax:
```bash
php -l /var/www/html/wp-content/plugins/ma-deal-room/ma-deal-room.php
```

3. Check for fatal errors:
```bash
tail -f /var/log/nginx/error.log
wp plugin activate ma-deal-room
```

4. Check dependencies:
```bash
cd /var/www/html/wp-content/plugins/ma-deal-room
composer install --no-dev
```

### Issue: White Screen on Activation

**Symptoms:**
- Blank white screen
- Site becomes inaccessible

**Solutions:**

1. Enable WordPress debug:
```php
// wp-config.php
define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true);
define('WP_DEBUG_DISPLAY', false);
```

2. Check debug log:
```bash
tail -f /var/www/html/wp-content/debug.log
```

3. Deactivate via database:
```bash
wp plugin deactivate ma-deal-room
```

4. Or via database directly:
```sql
SELECT * FROM wp_options WHERE option_name = 'active_plugins';
-- Remove 'ma-deal-room/ma-deal-room.php' from the array
```

---

## Frontend Issues

### Issue: Frontend Not Loading

**Symptoms:**
- Blank dashboard page
- React app not rendering

**Solutions:**

1. Check if assets were built:
```bash
ls -la /var/www/html/wp-content/plugins/ma-deal-room/assets/admin/dist/
```

2. Rebuild frontend:
```bash
cd /var/www/html/wp-content/plugins/ma-deal-room/assets/admin
npm run build
```

3. Clear browser cache and hard reload (Ctrl+Shift+R)

4. Check console for errors (F12 → Console tab)

5. Verify assets are being enqueued:
```bash
curl -I https://yourdomain.com/wp-content/plugins/ma-deal-room/assets/admin/dist/index.js
```

### Issue: "Module not found" Errors

**Symptoms:**
```
Error: Cannot find module '@/components/...'
```

**Solutions:**

1. Clean and rebuild:
```bash
rm -rf node_modules package-lock.json dist/
npm install
npm run build
```

2. Check TypeScript configuration:
```bash
cat tsconfig.json
# Verify paths configuration
```

### Issue: Frontend Build Fails

**Symptoms:**
```
ERROR in ./src/main.tsx
Module not found
```

**Solutions:**

1. Check Node.js version:
```bash
node --version  # Should be 18+ or 20 LTS
```

2. Install dependencies:
```bash
npm install
```

3. Check for TypeScript errors:
```bash
npm run type-check
```

4. Clear cache:
```bash
rm -rf node_modules/.vite
npm run build
```

---

## API Issues

### Issue: 404 on API Endpoints

**Symptoms:**
```
{
  "code": "rest_no_route",
  "message": "No route was found matching the URL and request method"
}
```

**Solutions:**

1. Flush rewrite rules:
```bash
wp rewrite flush
```

2. Check permalink structure:
```bash
wp rewrite structure '/%postname%/'
```

3. Verify plugin is activated:
```bash
wp plugin list | grep ma-deal-room
```

4. Check Nginx configuration allows WordPress routing:
```nginx
# Should have:
try_files $uri $uri/ /index.php?$args;
```

### Issue: 401 Unauthorized on Protected Endpoints

**Symptoms:**
```
{
  "code": "jwt_auth_invalid_token",
  "message": "Invalid token"
}
```

**Solutions:**

1. Check JWT secrets in .env:
```bash
cat .env | grep JWT_SECRET_KEY
```

2. Verify token is being sent:
```bash
# Check Authorization header in request
curl -H "Authorization: Bearer YOUR_TOKEN" https://yourdomain.com/wp-json/ma-deal/v1/transactions
```

3. Clear localStorage in browser (F12 → Application → Local Storage)

4. Re-login to get new token

### Issue: 500 Internal Server Error

**Symptoms:**
```
{
  "code": "internal_server_error",
  "message": "An error occurred"
}
```

**Solutions:**

1. Check PHP error log:
```bash
tail -f /var/log/nginx/error.log
tail -f /var/www/html/wp-content/debug.log
```

2. Enable WordPress debug:
```php
define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true);
```

3. Check PHP-FPM error log:
```bash
tail -f /var/log/php8.1-fpm.log
```

4. Increase PHP memory limit:
```ini
memory_limit = 512M
```

---

## Authentication Issues

### Issue: Cannot Log In

**Symptoms:**
- Login form submits but doesn't log in
- "Invalid credentials" error

**Solutions:**

1. Verify user exists:
```bash
wp user list
```

2. Reset password:
```bash
wp user update admin --user_pass=newpassword
```

3. Check AuthService:
```bash
php -l /var/www/html/wp-content/plugins/ma-deal-room/src/Services/AuthService.php
```

4. Check JWT secrets are set:
```bash
grep JWT_SECRET_KEY .env
```

### Issue: Session Expires Too Quickly

**Symptoms:**
- Keep getting logged out
- "Session expired" errors

**Solutions:**

1. Check JWT expiration settings in .env:
```bash
JWT_ACCESS_TOKEN_EXPIRATION=900     # 15 minutes
JWT_REFRESH_TOKEN_EXPIRATION=604800 # 7 days
```

2. Increase expiration time:
```bash
# In .env
JWT_ACCESS_TOKEN_EXPIRATION=3600    # 1 hour
JWT_REFRESH_TOKEN_EXPIRATION=1209600 # 14 days
```

3. Clear browser cache and cookies

### Issue: Two-Factor Authentication Not Working

**Symptoms:**
- QR code not appearing
- Verification codes not working

**Solutions:**

1. Verify Sentry installed:
```bash
composer show | grep sentry
```

2. Check time synchronization:
```bash
sudo timedatectl
# Ensure clock is synchronized
```

3. Re-generate backup codes:
```bash
wp ma-deal 2fa:regenerate-codes --user_id=1
```

---

## Email/SMS Issues

### Issue: Emails Not Sending

**Symptoms:**
- Emails not received
- No errors in logs

**Solutions:**

1. Check SendGrid API key:
```bash
grep SENDGRID_API_KEY .env
```

2. Test SendGrid connectivity:
```bash
curl -i --request POST \
  --url https://api.sendgrid.com/v3/mail/send \
  --header "Authorization: Bearer YOUR_API_KEY" \
  --header 'Content-Type: application/json' \
  --data '{"personalizations":[{"to":[{"email":"test@example.com"}]}],"from":{"email":"noreply@yourdomain.com"},"subject":"Test","content":[{"type":"text/plain","value":"Test"}]}'
```

3. Check spam folder

4. Verify sender domain is verified in SendGrid

5. Check EmailService logs:
```bash
grep "EmailService" /var/www/html/wp-content/debug.log
```

### Issue: SMS Not Sending

**Symptoms:**
- SMS not received
- Twilio errors

**Solutions:**

1. Check Twilio credentials:
```bash
grep TWILIO /env
```

2. Verify phone number format:
```
# Must be E.164 format: +1234567890
```

3. Check Twilio account balance:
```bash
# Log in to Twilio Console
# Check balance and account status
```

4. Test Twilio API:
```bash
curl -X POST https://api.twilio.com/2010-04-01/Accounts/YOUR_ACCOUNT_SID/Messages.json \
  --data-urlencode "From=+1234567890" \
  --data-urlencode "To=+0987654321" \
  --data-urlencode "Body=Test message" \
  -u YOUR_ACCOUNT_SID:YOUR_AUTH_TOKEN
```

---

## Performance Issues

### Issue: Slow Page Load Times

**Symptoms:**
- Pages take > 3 seconds to load
- High server CPU/memory usage

**Solutions:**

1. Enable OpCache:
```bash
php -i | grep opcache.enable
# Should show: opcache.enable => On => On
```

2. Enable Redis object caching:
```bash
redis-cli KEYS wp:*
# Should show cached keys
```

3. Optimize database:
```bash
wp db optimize
```

4. Check slow queries:
```bash
wp db query "SHOW PROCESSLIST;"
```

5. Enable Nginx caching (if not already enabled)

6. Use a CDN (CloudFlare, etc.)

### Issue: High Memory Usage

**Symptoms:**
- PHP fatal error: Allowed memory size exhausted
- Server running out of memory

**Solutions:**

1. Increase PHP memory limit:
```ini
# /etc/php/8.1/fpm/php.ini
memory_limit = 512M
```

2. Increase WordPress memory limit:
```php
// wp-config.php
define('WP_MEMORY_LIMIT', '512M');
```

3. Check for memory leaks:
```bash
# Enable memory profiling
wp profile
```

4. Optimize queries:
```bash
# Check for N+1 queries
# Use eager loading where possible
```

---

## SSL/HTTPS Issues

### Issue: Redirect Loop

**Symptoms:**
- Page keeps redirecting
- "Too many redirects" error

**Solutions:**

1. Check if behind load balancer:
```php
// wp-config.php
if (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https') {
    $_SERVER['HTTPS'] = 'on';
}
```

2. Verify ENVIRONMENT variable:
```bash
grep ENVIRONMENT .env
# Should be 'production' for HTTPS enforcement
```

3. Check Nginx configuration:
```bash
sudo nginx -t
```

4. Temporarily disable HTTPS middleware:
```bash
# Comment out in Plugin.php
// $this->container->get('https_middleware')->init();
```

### Issue: Mixed Content Warnings

**Symptoms:**
- Console shows "Mixed Content" errors
- Some resources not loading over HTTPS

**Solutions:**

1. Update database URLs:
```bash
wp search-replace 'http://yourdomain.com' 'https://yourdomain.com' --all-tables --precise
```

2. Check for hardcoded URLs:
```bash
grep -r "http://yourdomain.com" /var/www/html/wp-content/plugins/ma-deal-room/
```

3. Update CSP to allow HTTPS only:
```php
// Already handled by SecurityHeadersMiddleware
```

### Issue: SSL Certificate Invalid

**Symptoms:**
- "Your connection is not private" error
- Certificate expired warnings

**Solutions:**

1. Check certificate expiration:
```bash
sudo certbot certificates
```

2. Renew certificate:
```bash
sudo certbot renew
```

3. Test auto-renewal:
```bash
sudo certbot renew --dry-run
```

4. Verify certificate chain:
```bash
openssl s_client -connect yourdomain.com:443 -showcerts
```

---

## Backup Issues

### Issue: Backup Fails

**Symptoms:**
```
Error creating backup: Permission denied
```

**Solutions:**

1. Check backup directory permissions:
```bash
sudo chown -R www-data:www-data /var/www/html/wp-content/backups
sudo chmod 750 /var/www/html/wp-content/backups
```

2. Check disk space:
```bash
df -h
```

3. Check MySQL user permissions:
```sql
SHOW GRANTS FOR 'dealroom_user'@'localhost';
-- Should have SELECT privilege
```

4. Test backup manually:
```bash
sudo -u www-data wp ma-deal backup
```

### Issue: Restore Fails

**Symptoms:**
```
Error restoring backup: File not found
```

**Solutions:**

1. List available backups:
```bash
sudo -u www-data wp ma-deal backup list
```

2. Check backup file exists:
```bash
ls -la /var/www/html/wp-content/backups/
```

3. Verify file is not corrupted:
```bash
gunzip -t backup-20251101-030000.sql.gz
```

4. Test restore in staging first

---

## Cron Job Issues

### Issue: Cron Jobs Not Running

**Symptoms:**
- Reminders not being sent
- Backups not running
- Queue not processing

**Solutions:**

1. Check cron is enabled:
```bash
sudo systemctl status cron
```

2. List cron jobs:
```bash
sudo crontab -l -u www-data
```

3. Check cron logs:
```bash
grep CRON /var/log/syslog
```

4. Verify WP-CLI works:
```bash
sudo -u www-data wp --version
```

5. Test cron job manually:
```bash
sudo -u www-data wp ma-deal reminders:send
```

6. Check file paths in cron are correct:
```bash
# Cron should use full paths
cd /var/www/html && wp ma-deal reminders:send
```

---

## Monitoring Issues

### Issue: Sentry Not Receiving Errors

**Symptoms:**
- No errors appearing in Sentry dashboard
- Sentry integration not working

**Solutions:**

1. Check SENTRY_DSN is set:
```bash
grep SENTRY_DSN .env
```

2. Verify environment:
```bash
grep ENVIRONMENT .env
# Sentry is disabled in 'development' mode
```

3. Test Sentry manually:
```php
// Create test file
<?php
require 'vendor/autoload.php';

\MADealRoom\Services\MonitoringService::init();
\MADealRoom\Services\MonitoringService::capture_message('Test from production');
\MADealRoom\Services\MonitoringService::flush();
```

4. Check network connectivity:
```bash
curl -I https://sentry.io
```

5. Check PHP error logs for Sentry errors:
```bash
tail -f /var/log/php8.1-fpm.log | grep -i sentry
```

---

## General Debugging

### Enable Verbose Logging

```php
// wp-config.php
define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true);
define('WP_DEBUG_DISPLAY', false);
define('SCRIPT_DEBUG', true);
@ini_set('display_errors', 0);
```

### Check System Resources

```bash
# CPU and memory usage
htop

# Disk usage
df -h

# Check running processes
ps aux | grep php
ps aux | grep nginx
ps aux | grep mysql

# Check network connections
netstat -tuln
```

### Test Components Individually

```bash
# Test database connection
wp db check

# Test Redis connection
redis-cli ping

# Test PHP configuration
php -i | grep -i "memory_limit"

# Test Nginx configuration
sudo nginx -t

# Test PHP-FPM
sudo systemctl status php8.1-fpm
```

---

## Getting Help

If you're still experiencing issues after trying these solutions:

1. **Check Logs:**
   - `/var/log/nginx/error.log`
   - `/var/log/php8.1-fpm.log`
   - `/var/www/html/wp-content/debug.log`
   - Browser console (F12)

2. **Gather Information:**
   - WordPress version: `wp core version`
   - PHP version: `php --version`
   - MySQL version: `mysql --version`
   - Plugin version: `wp plugin list | grep ma-deal-room`
   - System info: `uname -a`

3. **Create Support Ticket:**
   - Include error messages
   - Include steps to reproduce
   - Include relevant logs
   - Include system information

4. **Resources:**
   - GitHub Issues: https://github.com/yourusername/ma-deal-room/issues
   - Documentation: `/docs`
   - WordPress Support: https://wordpress.org/support/

---

## Quick Reference Commands

```bash
# Restart services
sudo systemctl restart nginx
sudo systemctl restart php8.1-fpm
sudo systemctl restart mysql
sudo systemctl restart redis-server

# Clear caches
redis-cli FLUSHDB
wp cache flush
wp transient delete --all

# Check logs
tail -f /var/log/nginx/error.log
tail -f /var/www/html/wp-content/debug.log

# Database operations
wp db check
wp db optimize
wp db repair

# Plugin operations
wp plugin list
wp plugin activate ma-deal-room
wp plugin deactivate ma-deal-room

# Run tests
wp ma-deal test:system
wp ma-deal test:email
wp ma-deal test:sms
```
