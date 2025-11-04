# Production Deployment Checklist

Use this checklist to ensure all steps are completed correctly when deploying MA Deal Room to production.

**Deployment Date:** _________________
**Deployed By:** _________________
**Environment:** ☐ Staging | ☐ Production
**Version:** _________________

---

## Pre-Deployment

### Requirements Verification

- [ ] Read and understand `SERVER_REQUIREMENTS.md`
- [ ] Read and understand `PRODUCTION_DEPLOYMENT.md`
- [ ] Server meets minimum specifications
- [ ] All required credentials and API keys obtained
- [ ] Staging environment tested successfully
- [ ] Backup strategy defined and documented
- [ ] Rollback plan prepared

### Credentials Checklist

- [ ] Database credentials (host, name, user, password)
- [ ] SendGrid API key obtained and verified
- [ ] Twilio Account SID and Auth Token obtained
- [ ] Twilio phone number purchased and verified
- [ ] Sentry account created and DSN obtained
- [ ] SSH access to production server configured
- [ ] Domain name configured in DNS
- [ ] SSL certificate ready (Let's Encrypt or commercial)

### Pre-Deployment Backup

- [ ] Current database backed up (if upgrading)
- [ ] Current files backed up (if upgrading)
- [ ] Current `.env` file backed up
- [ ] Backup stored in safe off-site location
- [ ] Restore procedure tested in staging

---

## Server Preparation

### System Updates

- [ ] Operating system updated (`apt update && apt upgrade`)
- [ ] System rebooted if kernel updated
- [ ] All security patches applied

### Software Installation

- [ ] PHP 8.0+ installed
  - Version: _________________
- [ ] Required PHP extensions installed
  ```bash
  php -m | grep -E "mysql|redis|curl|json|xml|mbstring|zip|gd|intl|bcmath|opcache"
  ```
- [ ] MySQL 8.0+ installed
  - Version: _________________
- [ ] Redis 6.0+ installed
  - Version: _________________
- [ ] Nginx installed and configured
  - Version: _________________
- [ ] Composer installed
  - Version: _________________
- [ ] Node.js 18/20 LTS installed
  - Version: _________________
- [ ] WP-CLI installed
  - Version: _________________

### System Configuration

- [ ] PHP configuration optimized (`memory_limit`, `max_execution_time`, etc.)
- [ ] PHP-FPM configured and running
- [ ] OpCache enabled and configured
- [ ] MySQL configured (`innodb_buffer_pool_size`, character set)
- [ ] Redis configured (`maxmemory`, persistence)
- [ ] Firewall configured (UFW/iptables)
  - [ ] Port 22 (SSH) - restricted to admin IPs
  - [ ] Port 80 (HTTP) - open
  - [ ] Port 443 (HTTPS) - open
  - [ ] Port 3306 (MySQL) - NOT publicly accessible
  - [ ] Port 6379 (Redis) - NOT publicly accessible
- [ ] Fail2ban installed and configured
- [ ] Log rotation configured

---

## WordPress Installation

### WordPress Core

- [ ] WordPress downloaded (version: _________)
- [ ] Database created with utf8mb4 character set
- [ ] Database user created with appropriate permissions
- [ ] `wp-config.php` created and configured
- [ ] WordPress installed successfully
- [ ] Admin account created with strong password
- [ ] Site URL configured correctly (HTTPS)
- [ ] Permalink structure set (e.g., `/%postname%/`)

### WordPress Configuration

- [ ] `FORCE_SSL_ADMIN` enabled in `wp-config.php`
- [ ] `DISALLOW_FILE_EDIT` enabled
- [ ] `WP_DEBUG` disabled (production)
- [ ] Memory limits configured
- [ ] Auto-updates configured for security patches
- [ ] Post revisions limited
- [ ] Trash auto-delete configured
- [ ] XML-RPC disabled
- [ ] WordPress version hidden

---

## Plugin Installation

### Plugin Files

- [ ] Repository cloned/uploaded to plugins directory
- [ ] File ownership set to `www-data:www-data`
- [ ] File permissions set correctly (755 for directories, 644 for files)
- [ ] PHP dependencies installed (`composer install --no-dev --optimize-autoloader`)
- [ ] Frontend assets built (`npm run build`)
- [ ] `node_modules` removed from production
- [ ] Plugin activated successfully

### Environment Configuration

- [ ] `.env` file created from `.env.example`
- [ ] `ENVIRONMENT=production` set
- [ ] Database credentials configured
- [ ] Redis credentials configured
- [ ] JWT secrets generated and configured (64+ characters each)
- [ ] SendGrid API key configured
- [ ] Twilio credentials configured
- [ ] Sentry DSN configured (backend and frontend)
- [ ] Frontend URL configured
- [ ] CORS origins configured
- [ ] `.env` file permissions set to 440
- [ ] `.env` file ownership set to `www-data:www-data`

### Database Setup

- [ ] Migrations run successfully (`wp ma-deal migrate`)
- [ ] All tables created (verify with `SHOW TABLES LIKE 'wp_ma_%';`)
- [ ] Test data seeded (if applicable)
- [ ] Database indexes verified

---

## SSL/HTTPS Configuration

### Certificate Installation

- [ ] SSL certificate installed
  - Type: ☐ Let's Encrypt | ☐ Commercial
  - Expiration date: _________________
- [ ] Certificate chain complete
- [ ] Auto-renewal configured (if Let's Encrypt)
- [ ] Auto-renewal tested (`certbot renew --dry-run`)

### SSL Configuration

- [ ] Nginx configured for HTTPS
- [ ] HTTP to HTTPS redirect configured
- [ ] TLS 1.2+ only
- [ ] Strong cipher suites configured
- [ ] HSTS header configured (via plugin)
- [ ] SSL Labs test passed: https://www.ssllabs.com/ssltest/
  - Grade: _________________

### Security Headers

- [ ] Security headers middleware enabled
- [ ] CSP header configured
- [ ] X-Frame-Options header present
- [ ] X-Content-Type-Options header present
- [ ] Referrer-Policy header present
- [ ] Permissions-Policy header present
- [ ] SecurityHeaders.com test passed: https://securityheaders.com/
  - Grade: _________________

---

## Nginx Configuration

### Web Server Setup

- [ ] Nginx site configuration created
- [ ] Server name(s) configured correctly
- [ ] Root directory set to WordPress root
- [ ] PHP-FPM configured correctly
- [ ] WordPress rewrites configured
- [ ] File upload size configured (`client_max_body_size`)
- [ ] Static asset caching configured
- [ ] Gzip compression enabled
- [ ] Sensitive files blocked (`.env`, `.git`, etc.)
- [ ] Access and error logs configured
- [ ] Configuration syntax tested (`nginx -t`)
- [ ] Nginx reloaded successfully

---

## Cron Job Configuration

### System Cron

- [ ] WordPress cron disabled (`DISABLE_WP_CRON = true`)
- [ ] System cron jobs configured for `www-data` user
- [ ] WordPress cron scheduled (every 5 minutes)
- [ ] Reminders cron scheduled (hourly)
- [ ] Queue processing cron scheduled (hourly)
- [ ] Daily backup cron scheduled (3 AM)
- [ ] Notification cleanup cron scheduled (daily)
- [ ] Cron jobs tested manually
- [ ] Cron logs verified

---

## Monitoring Setup

### Sentry Configuration

- [ ] Sentry initialized in backend
- [ ] Sentry initialized in frontend
- [ ] Environment set correctly (production)
- [ ] Sample rates configured
- [ ] Before-send filters configured
- [ ] Test error sent to Sentry
- [ ] Error appears in Sentry dashboard

### Server Monitoring

- [ ] Uptime monitoring configured
  - Service: _________________
  - Checks: Homepage, API, Admin
- [ ] Server resource monitoring configured (optional)
  - CPU, memory, disk usage
- [ ] Log aggregation configured (optional)
- [ ] Performance monitoring configured (optional)

### Logging

- [ ] Nginx access log configured
- [ ] Nginx error log configured
- [ ] PHP error log configured
- [ ] WordPress debug log disabled (production)
- [ ] Log rotation configured
- [ ] Logs readable by administrators only

---

## Backup Configuration

### Backup System

- [ ] Backup service configured
- [ ] Backup directory created with correct permissions
- [ ] Daily backup cron configured
- [ ] Backup rotation configured (7 daily, 4 weekly, 12 monthly)
- [ ] Manual backup created successfully
- [ ] Backup file exists and is valid
- [ ] Backup file size reasonable
- [ ] Off-site backup configured (S3, etc.) - optional
- [ ] Restore procedure tested in staging

---

## Performance Optimization

### Caching

- [ ] OpCache enabled and verified
- [ ] Redis object caching working
  - Verified with: `redis-cli KEYS wp:*`
- [ ] Browser caching headers configured
- [ ] Static asset caching configured
- [ ] Nginx FastCGI cache configured (optional)

### Database Optimization

- [ ] Database tables optimized (`wp db optimize`)
- [ ] Slow query log enabled
- [ ] Database indexes verified
- [ ] Query caching enabled

### CDN Configuration (Optional)

- [ ] CDN configured (CloudFlare, etc.)
- [ ] DNS pointing to CDN
- [ ] CDN caching configured
- [ ] CDN purge tested

---

## Security Hardening

### File Permissions

- [ ] WordPress files owned by `www-data:www-data`
- [ ] Directories: 755
- [ ] Files: 644
- [ ] `wp-config.php`: 440
- [ ] `.env`: 440
- [ ] No files owned by root

### WordPress Security

- [ ] Strong admin password (16+ characters)
- [ ] Admin username not "admin"
- [ ] File editing disabled
- [ ] XML-RPC disabled
- [ ] WordPress version hidden
- [ ] Database table prefix changed (optional)
- [ ] Two-factor authentication enabled for admin

### System Security

- [ ] SSH key-based authentication enabled
- [ ] SSH password authentication disabled
- [ ] Root SSH login disabled
- [ ] Firewall configured and enabled
- [ ] Fail2ban installed and configured
- [ ] Automatic security updates enabled

---

## Functional Testing

### Core Features

- [ ] Homepage loads correctly
- [ ] WordPress admin accessible
- [ ] MA Deal Room dashboard loads
- [ ] User registration works
- [ ] User login works
- [ ] Email verification works
- [ ] Password reset works
- [ ] Two-factor authentication works
- [ ] Transaction creation works
- [ ] Task management works
- [ ] Document upload works
- [ ] Search functionality works
- [ ] Notifications display correctly

### Email/SMS Testing

- [ ] SendGrid connection successful
- [ ] Test email sent and received
- [ ] Email templates rendering correctly
- [ ] Twilio connection successful
- [ ] Test SMS sent and received
- [ ] SMS notifications working

### API Testing

- [ ] API endpoints responding
- [ ] Authentication working (JWT)
- [ ] CORS configured correctly
- [ ] Rate limiting working (if enabled)
- [ ] Error handling working
- [ ] Response times acceptable (< 500ms average)

---

## Performance Testing

### Page Load Times

- [ ] Homepage: < 2 seconds
  - Actual: _________ seconds
- [ ] Dashboard: < 3 seconds
  - Actual: _________ seconds
- [ ] API responses: < 500ms average
  - Actual: _________ ms

### Load Testing (Optional)

- [ ] Load testing performed
  - Tool used: _________________
  - Concurrent users tested: _________________
- [ ] Server handles expected load
- [ ] No errors under load
- [ ] Response times acceptable under load

---

## Security Testing

### SSL/HTTPS

- [ ] HTTPS enforced (HTTP redirects to HTTPS)
- [ ] No mixed content warnings
- [ ] HSTS header present
- [ ] SSL Labs test: A or A+
  - Actual grade: _________________
- [ ] Security headers present
- [ ] SecurityHeaders.com test: A or A+
  - Actual grade: _________________

### Vulnerability Scanning

- [ ] WordPress security scan completed
  - Tool used: _________________
- [ ] Plugin security scan completed
- [ ] No high/critical vulnerabilities found
- [ ] OWASP ZAP scan completed (optional)
- [ ] Penetration testing completed (optional)

---

## Documentation

### Deployment Documentation

- [ ] Server credentials documented (securely)
- [ ] API keys documented (securely)
- [ ] Deployment steps documented
- [ ] Configuration changes documented
- [ ] Known issues documented

### Operational Documentation

- [ ] Backup/restore procedures documented
- [ ] Monitoring procedures documented
- [ ] Incident response procedures documented
- [ ] Escalation contacts documented
- [ ] Maintenance windows documented

---

## Post-Deployment

### Verification

- [ ] All checklist items completed
- [ ] No errors in logs
- [ ] Monitoring confirms site is up
- [ ] Sentry receiving data
- [ ] Backups running successfully
- [ ] Performance benchmarks met
- [ ] Security tests passed

### Communication

- [ ] Stakeholders notified of deployment
- [ ] Users notified (if applicable)
- [ ] Support team briefed
- [ ] Documentation updated

### Monitoring Period

- [ ] 24-hour monitoring period planned
- [ ] On-call personnel assigned
- [ ] Escalation procedures in place
- [ ] Rollback plan ready if needed

---

## Sign-Off

| Role | Name | Signature | Date |
|------|------|-----------|------|
| **Developer** | | | |
| **DevOps/SysAdmin** | | | |
| **QA** | | | |
| **Project Manager** | | | |

---

## Notes

Use this space for any additional notes, issues encountered, or deviations from the standard deployment process:

_______________________________________________________________________________

_______________________________________________________________________________

_______________________________________________________________________________

_______________________________________________________________________________

_______________________________________________________________________________

---

## Rollback Information

**Rollback Required:** ☐ Yes | ☐ No

If rollback was required:

- **Reason:** ___________________________________________________________________
- **Time of Rollback:** ___________________________________________________________
- **Rollback Performed By:** ______________________________________________________
- **Database Restored:** ☐ Yes | ☐ No - Backup file: ____________________________
- **Files Restored:** ☐ Yes | ☐ No - Backup file: _______________________________
- **Rollback Successful:** ☐ Yes | ☐ No
- **Notes:** _____________________________________________________________________

_______________________________________________________________________________

---

## Next Steps

- [ ] Monitor system for 24-48 hours
- [ ] Schedule follow-up review meeting
- [ ] Document lessons learned
- [ ] Update deployment procedures if needed
- [ ] Plan next release

---

**Deployment Status:** ☐ Successful | ☐ Partial | ☐ Failed | ☐ Rolled Back

**Final Notes:**

_______________________________________________________________________________

_______________________________________________________________________________

_______________________________________________________________________________
