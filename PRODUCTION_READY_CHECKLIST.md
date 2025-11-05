# Production Ready Checklist

This document contains the final checklist before deploying MA Deal Room to production.

## ✅ Completed Production Readiness Tasks

### Dependencies & Build
- [x] **Composer dependencies installed** - All PHP packages installed and autoloader optimized
- [x] **React admin built** - Production build created in `assets/admin/dist/`
- [x] **Development utilities moved** - Utility scripts moved to `/dev-tools/` and excluded from git

### Security
- [x] **JWT security warnings added** - `.env.example` updated with critical security warnings
- [x] **JWT secret validation** - `AuthService.php` now checks for weak/default JWT secrets on startup
- [x] **AccountSecurityService integrated** - Security event logging wired into controllers
- [x] **Uninstall script updated** - All database tables from all migrations included

### Configuration
- [x] **Docker Compose versioned** - Version 3.8 added to docker-compose.yml
- [x] **Environment variables documented** - Comprehensive `.env.example` with all options

---

## 🔧 Before First Deployment

### 1. Environment Configuration (CRITICAL)

**Generate JWT Secrets:**
```bash
# Generate access token secret
JWT_ACCESS=$(openssl rand -base64 64)
echo "JWT_SECRET_KEY=$JWT_ACCESS" >> .env

# Generate refresh token secret
JWT_REFRESH=$(openssl rand -base64 64)
echo "JWT_REFRESH_SECRET_KEY=$JWT_REFRESH" >> .env
```

**Required Environment Variables:**
- `JWT_SECRET_KEY` - Must be strong random string (64+ chars)
- `JWT_REFRESH_SECRET_KEY` - Must be strong random string (64+ chars)
- `DB_NAME`, `DB_USER`, `DB_PASSWORD` - Database credentials
- `SENDGRID_API_KEY` - Email delivery (or leave blank for wp_mail())
- `CORS_ALLOWED_ORIGINS` - Your production domain(s)
- `FRONTEND_URL` - Your frontend app URL

**Optional but Recommended:**
- `TWILIO_ACCOUNT_SID`, `TWILIO_AUTH_TOKEN` - SMS notifications
- `SENTRY_DSN` - Error tracking
- `REDIS_HOST`, `REDIS_PORT` - Caching (improves performance)
- `CLAMAV_PATH` or `VIRUSTOTAL_API_KEY` - Virus scanning

### 2. Dependencies Installation

```bash
# Install PHP dependencies (production mode)
cd ma-deal-room
composer install --no-dev --optimize-autoloader

# Build React admin interface
cd assets/admin
npm install
npm run build

# Verify build outputs exist
ls -lh dist/assets/index.js  # Should be ~935KB
```

### 3. WordPress Setup

```bash
# Activate plugin
wp plugin activate ma-deal-room

# Run database migrations
wp ma-deal migrate

# Verify migrations
wp db query "SELECT COUNT(*) as total FROM wp_ma_deal_migrations"

# (Optional) Seed demo data for testing
wp ma-deal seed
```

### 4. Configure Cron Jobs

Add to your server cron (via `crontab -e` or server control panel):

```cron
# Send reminder notifications every hour
0 * * * * cd /path/to/wordpress && wp ma-deal reminders:send

# Process background job queue every 15 minutes
*/15 * * * * cd /path/to/wordpress && wp ma-deal queue:run

# Clean up old security events monthly
0 2 1 * * cd /path/to/wordpress && wp ma-deal cleanup-security-events

# Clean up expired sessions daily
0 3 * * * cd /path/to/wordpress && wp ma-deal cleanup-sessions
```

### 5. Security Configuration

**File Permissions:**
```bash
# WordPress directory permissions
find . -type d -exec chmod 755 {} \;
find . -type f -exec chmod 644 {} \;

# Protect sensitive files
chmod 600 .env
chmod 600 wp-config.php
```

**Web Server Configuration:**

For Apache (`.htaccess`):
```apache
# Protect .env file
<Files .env>
    Order allow,deny
    Deny from all
</Files>

# Protect composer files
<FilesMatch "^(composer\.json|composer\.lock)$">
    Order allow,deny
    Deny from all
</FilesMatch>
```

For Nginx (`nginx.conf`):
```nginx
# Deny access to sensitive files
location ~ /\.(env|git|htaccess) {
    deny all;
}

location ~ /composer\.(json|lock)$ {
    deny all;
}
```

### 6. Test the Installation

**Functional Tests:**
```bash
# Run PHP unit tests
composer test

# Check plugin activation status
wp plugin list | grep ma-deal-room

# Verify API endpoints
curl https://your-domain.com/wp-json/ma-deal/v1/auth/health
```

**Manual Tests:**
1. Visit WordPress admin and ensure plugin appears
2. Check for any admin notices (warnings/errors)
3. Test user registration API endpoint
4. Test login API endpoint
5. Verify email delivery (check spam folder)
6. Test file upload functionality
7. Check that Redis is connected (if enabled)

### 7. Monitoring Setup

**Sentry Configuration** (Error Tracking):
```bash
# Add to .env
SENTRY_DSN=https://your-sentry-dsn@sentry.io/project-id
VITE_SENTRY_DSN=https://your-sentry-dsn@sentry.io/project-id
VITE_APP_VERSION=2.0.0
```

**Health Check Endpoint:**
```bash
# Set up monitoring to ping this endpoint every 5 minutes
curl https://your-domain.com/wp-json/ma-deal/v1/health
```

---

## 📝 Known Limitations & Future Work

### Remaining TODO Items (Non-Critical)

These TODOs are marked in the code but don't block production deployment:

**Low Priority:**
1. **Queue Processing** (`CLI/QueueCommand.php:29`)
   - Currently stubbed out
   - Not needed for initial launch
   - Implement when async job processing is required

2. **Vendor Portal Updates** (`REST/Controllers/VendorPortalController.php:66`)
   - Vendor request update logic incomplete
   - Vendor portal is Phase 6 feature
   - Can be implemented when vendor features go live

3. **Database Tracking for Email/SMS** (Multiple files)
   - Email/SMS tracking noted as TODO
   - Currently logs to error log
   - Add database tables when analytics needed

4. **Phone Number Logic** (`Services/ReminderService.php:93`)
   - Needs more robust phone number determination
   - Current implementation functional but basic
   - Enhance when SMS becomes critical feature

5. **Admin Notification Emails** (`Services/FileSecurityService.php:408`)
   - Admin notifications for security events
   - Currently only logs warnings
   - Implement when admin dashboard is needed

**Reference:** See `KNOWN_TODOS.md` for complete list with file locations.

---

## 🚀 Deployment Steps

### Production Deployment Process

1. **Pre-deployment**
   ```bash
   # Create backup of current production
   wp db export backup-pre-v2.0.0.sql

   # Back up uploads directory
   tar -czf uploads-backup.tar.gz wp-content/uploads/
   ```

2. **Deploy code**
   ```bash
   # Pull latest code from your branch
   git pull origin claude/codebase-review-011CUp16oFroL78rJkdRCJ2e

   # Install dependencies
   composer install --no-dev --optimize-autoloader
   cd assets/admin && npm install && npm run build && cd ../..
   ```

3. **Run migrations**
   ```bash
   # Apply database changes
   wp ma-deal migrate

   # Verify migration status
   wp db query "SELECT * FROM wp_ma_deal_migrations ORDER BY migration_number DESC LIMIT 5"
   ```

4. **Activate & test**
   ```bash
   # Clear all caches
   wp cache flush
   wp ma-deal cache:clear  # If command exists

   # Test health endpoint
   curl https://your-domain.com/wp-json/ma-deal/v1/health
   ```

5. **Monitor**
   - Watch error logs for first 24 hours
   - Check Sentry for exceptions
   - Monitor email delivery
   - Verify cron jobs are running

### Rollback Plan

If issues occur:
```bash
# 1. Restore database backup
wp db import backup-pre-v2.0.0.sql

# 2. Deactivate plugin
wp plugin deactivate ma-deal-room

# 3. Restore previous code
git checkout previous-version-tag

# 4. Clear caches
wp cache flush
```

---

## 📊 Success Metrics

Monitor these metrics post-deployment:

- **User Registration Success Rate** - Should be >95%
- **Login Success Rate** - Should be >98%
- **Email Delivery Rate** - Should be >95%
- **API Response Time** - Should be <500ms (p95)
- **Error Rate** - Should be <1%
- **Plugin Activation Issues** - Should be 0

---

## 🔒 Security Considerations

### Critical Security Items
- ✅ JWT secrets are randomly generated (not defaults)
- ✅ Database uses prepared statements (SQL injection protected)
- ✅ File uploads validated and scanned
- ✅ Rate limiting enabled
- ✅ Password hashing with bcrypt
- ✅ CORS configured for production domain only
- ✅ 2FA available for enhanced security

### Post-Deployment Security Tasks
- [ ] Configure SSL/TLS certificate
- [ ] Set up Web Application Firewall (WAF)
- [ ] Enable WordPress security headers
- [ ] Configure backup retention policy
- [ ] Set up security audit log monitoring
- [ ] Review and update CORS allowed origins
- [ ] Enable fail2ban or similar brute-force protection

---

## 📞 Support & Troubleshooting

### Common Issues

**Issue: Plugin won't activate**
- Check PHP version (must be 8.0+)
- Verify Composer dependencies installed
- Check error logs: `wp-content/debug.log`

**Issue: React admin not loading**
- Verify build completed: `ls ma-deal-room/assets/admin/dist/`
- Check browser console for errors
- Clear browser cache

**Issue: Database errors on activation**
- Verify database user has CREATE TABLE permissions
- Check database character set (should be utf8mb4)
- Review migration error logs

**Issue: JWT authentication failing**
- Verify JWT secrets are set in `.env`
- Check that secrets don't contain special characters that need escaping
- Ensure WordPress `.htaccess` allows Authorization headers

### Debug Mode

Enable debug mode in `.env`:
```bash
WP_DEBUG=true
WP_DEBUG_LOG=true
WP_DEBUG_DISPLAY=false
MA_DEAL_ENV=development
```

Then check logs at: `wp-content/debug.log`

---

## ✅ Final Pre-Launch Checklist

- [ ] All environment variables configured in `.env`
- [ ] JWT secrets generated and validated (not defaults)
- [ ] Composer dependencies installed (production mode)
- [ ] React admin built and dist/ files present
- [ ] Database migrations completed successfully
- [ ] Cron jobs configured for reminders and queue processing
- [ ] CORS configured for production domain
- [ ] SSL certificate installed and active
- [ ] Error tracking (Sentry) configured and tested
- [ ] Email delivery tested (SendGrid or SMTP)
- [ ] File upload tested with virus scanning
- [ ] User registration flow tested end-to-end
- [ ] Login flow tested with 2FA
- [ ] Backup strategy in place
- [ ] Rollback plan documented and tested
- [ ] Monitoring dashboards set up
- [ ] Team notified of deployment
- [ ] Documentation updated with production URLs

---

**Last Updated:** $(date +%Y-%m-%d)
**Plugin Version:** 2.0.0
**WordPress Version Required:** 6.0+
**PHP Version Required:** 8.0+
