# Server Requirements

This document outlines the minimum and recommended server requirements for deploying the MA Deal Room WordPress plugin in a production environment.

## Minimum Requirements

### Operating System
- **Linux**: Ubuntu 20.04 LTS or higher (recommended)
- **Alternative**: CentOS 8+, Debian 10+, or any modern Linux distribution
- **Not Supported**: Windows Server, shared hosting with limited access

### Web Server
**Option 1: Nginx (Recommended)**
- Version: 1.18+
- Required modules: ngx_http_ssl_module, ngx_http_rewrite_module

**Option 2: Apache**
- Version: 2.4+
- Required modules: mod_rewrite, mod_ssl, mod_headers

### PHP
- **Minimum Version**: PHP 8.0
- **Recommended Version**: PHP 8.1 or 8.2
- **Memory Limit**: 256MB minimum, 512MB recommended
- **Max Execution Time**: 60 seconds minimum, 120 seconds recommended
- **Upload Max Filesize**: 10MB minimum, 50MB recommended
- **Post Max Size**: 10MB minimum, 50MB recommended

**Required PHP Extensions:**
```
- php-fpm (for Nginx)
- php-mysql or php-mysqli
- php-redis
- php-curl
- php-json
- php-xml
- php-mbstring
- php-zip
- php-gd
- php-intl
- php-bcmath
```

**Optional but Recommended:**
```
- php-opcache (performance)
- php-apcu (caching)
```

### Database
**MySQL**
- **Minimum Version**: MySQL 8.0
- **Recommended Version**: MySQL 8.0.30+
- **Alternative**: MariaDB 10.6+

**Database Configuration:**
- Character Set: utf8mb4
- Collation: utf8mb4_unicode_ci
- Max Connections: 150+ (depends on traffic)
- InnoDB Buffer Pool Size: 1GB minimum for dedicated server

### Redis
- **Version**: Redis 6.0+
- **Recommended**: Redis 7.0+
- **Memory**: 512MB minimum, 1GB+ recommended
- **Persistence**: RDB + AOF recommended for production

### WordPress
- **Minimum Version**: WordPress 6.0
- **Recommended Version**: WordPress 6.4+
- **Note**: Always use the latest stable version for security

## Recommended Production Server Specifications

### Small Deployment (< 50 concurrent users)
```
CPU: 2 cores (2.5 GHz+)
RAM: 4GB
Disk: 20GB SSD
Network: 100 Mbps
```

### Medium Deployment (50-200 concurrent users)
```
CPU: 4 cores (2.5 GHz+)
RAM: 8GB
Disk: 50GB SSD
Network: 1 Gbps
```

### Large Deployment (200+ concurrent users)
```
CPU: 8+ cores (3.0 GHz+)
RAM: 16GB+
Disk: 100GB+ SSD
Network: 1 Gbps+
Load Balancer: Recommended
```

## Software Dependencies

### Composer
- **Version**: 2.0+
- Required for PHP dependency management
- Installation: https://getcomposer.org/

### Node.js & npm
- **Node.js**: 18 LTS or 20 LTS
- **npm**: 9.0+ (comes with Node.js)
- Required for frontend build process

### WP-CLI
- **Version**: 2.8+
- Recommended for WordPress management and automated tasks
- Installation: https://wp-cli.org/

### Git
- **Version**: 2.30+
- Required for version control and deployment

## SSL/TLS Certificate

### Production Requirements
- Valid SSL certificate from trusted CA
- TLS 1.2 minimum, TLS 1.3 recommended
- Strong cipher suites only

### Certificate Options
1. **Let's Encrypt** (Free, recommended)
   - Auto-renewal support
   - Wildcard certificates available

2. **Commercial CA** (Paid)
   - Comodo, DigiCert, GlobalSign, etc.
   - Extended validation available

## Firewall Requirements

### Inbound Ports
```
22    - SSH (restrict to admin IPs)
80    - HTTP (redirect to HTTPS)
443   - HTTPS
3306  - MySQL (only from application server, not public)
6379  - Redis (only from application server, not public)
```

### Outbound Ports
```
80    - HTTP (for package downloads)
443   - HTTPS (for API calls, Sentry, SendGrid, Twilio)
25    - SMTP (if using direct email)
587   - SMTP/TLS (for email services)
```

## External Services

### Required Services

#### 1. SendGrid (Email Delivery)
- Account: Free tier available, paid recommended
- API Key with full access permissions
- Verified sender domain recommended

#### 2. Twilio (SMS Notifications)
- Account: Pay-as-you-go
- Account SID and Auth Token
- Phone number for sending SMS

#### 3. Sentry (Error Tracking)
- Account: Free tier available, paid recommended
- Project DSN for backend and frontend
- Release tracking enabled

### Optional Services

#### 1. CloudFlare
- CDN and DDoS protection
- DNS management
- Free tier available

#### 2. AWS S3 or Similar
- Backup storage
- File uploads storage
- Recommended for large deployments

## Disk Space Requirements

### Base Installation
```
WordPress Core: ~50MB
MA Deal Room Plugin: ~30MB
Node Modules (build): ~500MB (not needed in production)
PHP Dependencies: ~20MB
Total Base: ~100MB (production)
```

### Database
```
Empty Database: ~1MB
Per Transaction: ~50KB
Per User: ~10KB
Estimated for 1000 transactions: ~50MB
```

### Backups
```
Daily backup: ~(database size + uploads)
Recommended retention: 7 daily, 4 weekly, 12 monthly
Plan for 2-3x database size for backup storage
```

### Logs
```
WordPress logs: Variable
Nginx/Apache logs: ~1MB per day (depends on traffic)
Application logs: ~500KB per day
Plan for 1GB+ for logs
```

### Total Recommended
```
Minimum: 20GB
Recommended: 50GB+
Enterprise: 100GB+
```

## Performance Requirements

### Page Load Time
- Homepage: < 2 seconds
- Dashboard: < 3 seconds
- API responses: < 500ms (average)

### Database Performance
- Query execution: < 100ms (90th percentile)
- Connection pool: 10-50 connections

### Caching
- Object caching: Redis (required)
- Page caching: Optional (Nginx FastCGI, Varnish)
- Browser caching: Enabled (handled by plugin)

## Monitoring & Logging

### Required
- PHP error logs
- Web server access/error logs
- Database slow query log
- Application error tracking (Sentry)

### Recommended
- Server monitoring (CPU, RAM, disk)
- Uptime monitoring
- Performance monitoring (New Relic, DataDog, etc.)
- Log aggregation (ELK Stack, Splunk, etc.)

## Backup Requirements

### What to Backup
1. WordPress files (wp-content/)
2. MA Deal Room plugin files
3. Database (full)
4. Environment configuration (.env)
5. SSL certificates
6. Web server configuration

### Backup Frequency
- **Database**: Daily (3 AM server time)
- **Files**: Weekly or on deployments
- **Configuration**: On changes

### Backup Retention
- Daily: 7 backups
- Weekly: 4 backups
- Monthly: 12 backups

### Backup Storage
- Local: Not recommended for production
- Remote: S3, Google Cloud Storage, or similar
- Off-site: Required for disaster recovery

## Security Requirements

### SSL/TLS
- HTTPS enforced (automatic redirect)
- HSTS enabled
- TLS 1.2+ only
- Strong cipher suites

### Firewall
- UFW (Ubuntu) or firewalld (CentOS)
- Fail2ban for SSH protection
- Rate limiting enabled

### System Updates
- Security patches: Weekly or as released
- System updates: Monthly
- WordPress/plugin updates: As released (after testing)

### File Permissions
```
Directories: 755
Files: 644
wp-config.php: 440 or 400
.env: 440 or 400
```

### User Permissions
- WordPress files: www-data:www-data (or web server user)
- No files owned by root
- Separate deployment user (optional)

## Development vs Production Differences

### Development
```
Environment: ENVIRONMENT=development
Debug: WP_DEBUG=true
SSL: Not enforced
Caching: Minimal
Monitoring: Disabled
```

### Staging
```
Environment: ENVIRONMENT=staging
Debug: WP_DEBUG=true (with log only)
SSL: Enforced
Caching: Enabled
Monitoring: Enabled (full)
```

### Production
```
Environment: ENVIRONMENT=production
Debug: WP_DEBUG=false
SSL: Enforced + HSTS
Caching: Aggressive
Monitoring: Enabled (full)
```

## Compliance Requirements

### Data Protection
- GDPR compliance (if serving EU users)
- Data encryption at rest (database)
- Data encryption in transit (SSL/TLS)
- Regular security audits

### Backup & Disaster Recovery
- RPO (Recovery Point Objective): 24 hours
- RTO (Recovery Time Objective): 4 hours
- Tested restore procedures

## Checklist

Before deploying to production, verify:

- [ ] Server meets minimum specifications
- [ ] PHP 8.0+ installed with all required extensions
- [ ] MySQL 8.0+ installed and configured
- [ ] Redis 6.0+ installed and running
- [ ] WordPress 6.0+ installed
- [ ] SSL certificate installed and valid
- [ ] Firewall configured correctly
- [ ] All external service accounts created (SendGrid, Twilio, Sentry)
- [ ] Backup system configured and tested
- [ ] Monitoring tools installed
- [ ] Security hardening completed
- [ ] Performance benchmarks met
- [ ] All environment variables configured
- [ ] Cron jobs scheduled
- [ ] Log rotation configured

## Support & Resources

### Documentation
- WordPress: https://wordpress.org/support/
- PHP: https://www.php.net/docs.php
- MySQL: https://dev.mysql.com/doc/
- Redis: https://redis.io/documentation
- Nginx: https://nginx.org/en/docs/

### Community
- WordPress Support Forums
- Stack Overflow
- GitHub Issues

### Professional Support
- WordPress hosting providers
- Managed WordPress services
- DevOps consultants
