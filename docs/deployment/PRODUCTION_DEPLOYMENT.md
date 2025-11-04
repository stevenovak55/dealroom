# Production Deployment Guide

This guide provides step-by-step instructions for deploying the MA Deal Room WordPress plugin to a production environment.

## Table of Contents

1. [Pre-Deployment](#pre-deployment)
2. [Server Preparation](#server-preparation)
3. [WordPress Installation](#wordpress-installation)
4. [Plugin Installation](#plugin-installation)
5. [Environment Configuration](#environment-configuration)
6. [Database Setup](#database-setup)
7. [Frontend Build](#frontend-build)
8. [SSL Certificate Installation](#ssl-certificate-installation)
9. [Cron Job Configuration](#cron-job-configuration)
10. [Monitoring Setup](#monitoring-setup)
11. [Backup Verification](#backup-verification)
12. [Performance Optimization](#performance-optimization)
13. [Security Hardening](#security-hardening)
14. [Post-Deployment](#post-deployment)
15. [Rollback Procedures](#rollback-procedures)

## Pre-Deployment

### 1. Review Requirements

Read and verify all requirements in [`SERVER_REQUIREMENTS.md`](./SERVER_REQUIREMENTS.md).

### 2. Gather Credentials

Collect all necessary credentials and API keys:

- [ ] Database credentials (host, name, user, password)
- [ ] SendGrid API key
- [ ] Twilio Account SID and Auth Token
- [ ] Twilio phone number
- [ ] Sentry DSN (backend and frontend)
- [ ] Server SSH access
- [ ] Domain name and DNS access
- [ ] SSL certificate (or Let's Encrypt setup)

### 3. Backup Current System

If upgrading existing installation:

```bash
# Backup database
wp db export backup-$(date +%Y%m%d-%H%M%S).sql

# Backup files
tar -czf wordpress-backup-$(date +%Y%m%d-%H%M%S).tar.gz wp-content/

# Backup .env file
cp .env .env.backup
```

### 4. Test in Staging

**Always test deployment in staging environment first!**

- [ ] Deploy to staging server
- [ ] Run all tests
- [ ] Verify functionality
- [ ] Check performance
- [ ] Review security headers
- [ ] Test email/SMS notifications

## Server Preparation

### 1. Update System

```bash
# Ubuntu/Debian
sudo apt update
sudo apt upgrade -y

# CentOS/RHEL
sudo yum update -y
```

### 2. Install Required Software

#### Install PHP 8.1
```bash
# Ubuntu 20.04/22.04
sudo add-apt-repository ppa:ondrej/php -y
sudo apt update
sudo apt install -y php8.1-fpm php8.1-mysql php8.1-redis php8.1-curl \
    php8.1-json php8.1-xml php8.1-mbstring php8.1-zip php8.1-gd \
    php8.1-intl php8.1-bcmath php8.1-opcache
```

#### Install MySQL 8.0
```bash
# Ubuntu
sudo apt install -y mysql-server-8.0

# Secure installation
sudo mysql_secure_installation
```

#### Install Redis
```bash
# Ubuntu
sudo apt install -y redis-server

# Start and enable
sudo systemctl start redis-server
sudo systemctl enable redis-server
```

#### Install Nginx
```bash
# Ubuntu
sudo apt install -y nginx

# Start and enable
sudo systemctl start nginx
sudo systemctl enable nginx
```

#### Install Composer
```bash
curl -sS https://getcomposer.org/installer | php
sudo mv composer.phar /usr/local/bin/composer
composer --version
```

#### Install Node.js (for build)
```bash
# Install Node.js 20 LTS
curl -fsSL https://deb.nodesource.com/setup_20.x | sudo -E bash -
sudo apt install -y nodejs

# Verify
node --version
npm --version
```

#### Install WP-CLI
```bash
curl -O https://raw.githubusercontent.com/wp-cli/builds/gh-pages/phar/wp-cli.phar
chmod +x wp-cli.phar
sudo mv wp-cli.phar /usr/local/bin/wp
wp --version
```

### 3. Configure Firewall

```bash
# Install UFW (if not installed)
sudo apt install -y ufw

# Configure firewall
sudo ufw default deny incoming
sudo ufw default allow outgoing
sudo ufw allow ssh
sudo ufw allow 80/tcp
sudo ufw allow 443/tcp
sudo ufw enable

# Verify
sudo ufw status
```

### 4. Configure PHP

Edit PHP configuration (`/etc/php/8.1/fpm/php.ini`):

```ini
memory_limit = 512M
max_execution_time = 120
upload_max_filesize = 50M
post_max_size = 50M
max_input_vars = 5000
date.timezone = America/New_York

# Enable OpCache
opcache.enable=1
opcache.memory_consumption=256
opcache.max_accelerated_files=10000
opcache.revalidate_freq=2
```

Restart PHP-FPM:
```bash
sudo systemctl restart php8.1-fpm
```

### 5. Configure MySQL

Edit MySQL configuration (`/etc/mysql/mysql.conf.d/mysqld.cnf`):

```ini
[mysqld]
# Performance
innodb_buffer_pool_size = 1G
innodb_log_file_size = 256M
innodb_flush_log_at_trx_commit = 2
innodb_flush_method = O_DIRECT

# Character set
character-set-server = utf8mb4
collation-server = utf8mb4_unicode_ci

# Connections
max_connections = 150
```

Restart MySQL:
```bash
sudo systemctl restart mysql
```

### 6. Configure Redis

Edit Redis configuration (`/etc/redis/redis.conf`):

```conf
maxmemory 1gb
maxmemory-policy allkeys-lru
save 900 1
save 300 10
save 60 10000
appendonly yes
```

Restart Redis:
```bash
sudo systemctl restart redis-server
```

## WordPress Installation

### 1. Create Directory

```bash
# Create web root
sudo mkdir -p /var/www/html
sudo chown -R www-data:www-data /var/www/html
cd /var/www/html
```

### 2. Download WordPress

```bash
# Download latest WordPress
sudo -u www-data wp core download

# Or specific version
sudo -u www-data wp core download --version=6.4
```

### 3. Create Database

```bash
# Login to MySQL
sudo mysql

# Create database and user
CREATE DATABASE ma_dealroom CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'dealroom_user'@'localhost' IDENTIFIED BY 'STRONG_PASSWORD_HERE';
GRANT ALL PRIVILEGES ON ma_dealroom.* TO 'dealroom_user'@'localhost';
FLUSH PRIVILEGES;
EXIT;
```

### 4. Configure WordPress

```bash
# Create wp-config.php
sudo -u www-data wp config create \
    --dbname=ma_dealroom \
    --dbuser=dealroom_user \
    --dbpass='STRONG_PASSWORD_HERE' \
    --dbhost=localhost \
    --dbcharset=utf8mb4

# Install WordPress
sudo -u www-data wp core install \
    --url=https://yourdomain.com \
    --title="MA Deal Room" \
    --admin_user=admin \
    --admin_password='STRONG_ADMIN_PASSWORD' \
    --admin_email=admin@yourdomain.com
```

### 5. Configure WordPress for Production

Edit `wp-config.php`:

```php
<?php
// Force HTTPS
define('FORCE_SSL_ADMIN', true);

// If behind a proxy/load balancer
if (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https') {
    $_SERVER['HTTPS'] = 'on';
}

// Disable file editing
define('DISALLOW_FILE_EDIT', true);

// Production settings
define('WP_DEBUG', false);
define('WP_DEBUG_LOG', false);
define('WP_DEBUG_DISPLAY', false);
@ini_set('display_errors', 0);

// Memory limits
define('WP_MEMORY_LIMIT', '256M');
define('WP_MAX_MEMORY_LIMIT', '512M');

// Auto-updates
define('WP_AUTO_UPDATE_CORE', 'minor');

// Database optimization
define('WP_POST_REVISIONS', 10);
define('AUTOSAVE_INTERVAL', 300);
define('EMPTY_TRASH_DAYS', 30);
```

## Plugin Installation

### 1. Clone Repository

```bash
cd /var/www/html/wp-content/plugins
sudo git clone https://github.com/yourusername/ma-deal-room.git
sudo chown -R www-data:www-data ma-deal-room
cd ma-deal-room
```

### 2. Install PHP Dependencies

```bash
sudo -u www-data composer install --no-dev --optimize-autoloader
```

### 3. Build Frontend

```bash
cd assets/admin
npm install
npm run build
cd ../..
```

### 4. Set Permissions

```bash
sudo chown -R www-data:www-data /var/www/html/wp-content/plugins/ma-deal-room
sudo find /var/www/html/wp-content/plugins/ma-deal-room -type d -exec chmod 755 {} \;
sudo find /var/www/html/wp-content/plugins/ma-deal-room -type f -exec chmod 644 {} \;
```

### 5. Activate Plugin

```bash
cd /var/www/html
sudo -u www-data wp plugin activate ma-deal-room
```

## Environment Configuration

### 1. Create .env File

```bash
cd /var/www/html/wp-content/plugins/ma-deal-room
sudo -u www-data cp .env.example .env
sudo -u www-data nano .env
```

### 2. Configure Environment Variables

Edit `.env` file:

```bash
# ==============================================
# Application Environment
# ==============================================
ENVIRONMENT=production
MA_DEAL_ENV=production

# ==============================================
# Database Configuration
# ==============================================
DB_HOST=localhost
DB_NAME=ma_dealroom
DB_USER=dealroom_user
DB_PASSWORD=STRONG_PASSWORD_HERE

# ==============================================
# Redis Configuration
# ==============================================
REDIS_HOST=127.0.0.1
REDIS_PORT=6379

# ==============================================
# JWT Authentication
# ==============================================
# Generate with: openssl rand -base64 64
JWT_SECRET_KEY=PASTE_GENERATED_KEY_HERE
JWT_REFRESH_SECRET_KEY=PASTE_GENERATED_KEY_HERE

# ==============================================
# Email Service (SendGrid)
# ==============================================
SENDGRID_API_KEY=SG.XXXXXXXXXXXXXXXXXXXXXX
MA_DEAL_SENDGRID_API_KEY=SG.XXXXXXXXXXXXXXXXXXXXXX

# ==============================================
# SMS Service (Twilio)
# ==============================================
TWILIO_ACCOUNT_SID=ACXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXX
TWILIO_AUTH_TOKEN=XXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXX
TWILIO_FROM_NUMBER=+1234567890

# ==============================================
# Monitoring (Sentry)
# ==============================================
SENTRY_DSN=https://xxxxx@xxxxx.ingest.sentry.io/xxxxx
MA_DEAL_SENTRY_DSN=https://xxxxx@xxxxx.ingest.sentry.io/xxxxx
VITE_SENTRY_DSN=https://xxxxx@xxxxx.ingest.sentry.io/xxxxx

# ==============================================
# Frontend Configuration
# ==============================================
FRONTEND_URL=https://yourdomain.com
CORS_ALLOWED_ORIGINS=https://yourdomain.com
VITE_APP_VERSION=1.0.0
```

### 3. Generate JWT Secrets

```bash
# Generate JWT access secret
openssl rand -base64 64

# Generate JWT refresh secret
openssl rand -base64 64
```

Copy the generated values into `.env` file.

### 4. Secure .env File

```bash
sudo chmod 440 .env
sudo chown www-data:www-data .env
```

## Database Setup

### 1. Run Migrations

```bash
cd /var/www/html
sudo -u www-data wp ma-deal migrate
```

### 2. Verify Tables

```bash
sudo -u www-data wp db query "SHOW TABLES LIKE 'wp_ma_%';"
```

You should see all MA Deal Room tables created.

### 3. Seed Data (Optional)

```bash
# For demo/testing only
sudo -u www-data wp ma-deal seed
```

## Frontend Build

### 1. Build Production Assets

```bash
cd /var/www/html/wp-content/plugins/ma-deal-room/assets/admin
npm run build
```

### 2. Verify Build

```bash
ls -la dist/
# Should see: assets/, index.html, etc.
```

### 3. Clean Up

```bash
# Remove node_modules (not needed in production)
rm -rf node_modules
```

## SSL Certificate Installation

### Option 1: Let's Encrypt (Recommended)

```bash
# Install Certbot
sudo apt install -y certbot python3-certbot-nginx

# Generate certificate
sudo certbot --nginx -d yourdomain.com -d www.yourdomain.com

# Verify auto-renewal
sudo certbot renew --dry-run
```

### Option 2: Commercial SSL

1. Generate CSR:
```bash
sudo openssl req -new -newkey rsa:2048 -nodes \
    -keyout /etc/ssl/private/yourdomain.key \
    -out /etc/ssl/certs/yourdomain.csr
```

2. Submit CSR to CA and obtain certificate

3. Install certificate in Nginx configuration

### Configure Nginx

Create `/etc/nginx/sites-available/ma-dealroom`:

```nginx
# Redirect HTTP to HTTPS
server {
    listen 80;
    listen [::]:80;
    server_name yourdomain.com www.yourdomain.com;
    return 301 https://yourdomain.com$request_uri;
}

# HTTPS Server
server {
    listen 443 ssl http2;
    listen [::]:443 ssl http2;
    server_name yourdomain.com www.yourdomain.com;

    root /var/www/html;
    index index.php index.html;

    # SSL Configuration
    ssl_certificate /etc/letsencrypt/live/yourdomain.com/fullchain.pem;
    ssl_certificate_key /etc/letsencrypt/live/yourdomain.com/privkey.pem;
    ssl_protocols TLSv1.2 TLSv1.3;
    ssl_ciphers HIGH:!aNULL:!MD5;
    ssl_prefer_server_ciphers on;

    # Security Headers (additional to plugin headers)
    add_header X-Content-Type-Options "nosniff" always;
    add_header X-Frame-Options "DENY" always;

    # Logging
    access_log /var/log/nginx/ma-dealroom-access.log;
    error_log /var/log/nginx/ma-dealroom-error.log;

    # WordPress Configuration
    location / {
        try_files $uri $uri/ /index.php?$args;
    }

    location ~ \.php$ {
        include snippets/fastcgi-php.conf;
        fastcgi_pass unix:/var/run/php/php8.1-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
        include fastcgi_params;
    }

    # Deny access to sensitive files
    location ~ /\.ht {
        deny all;
    }

    location ~ /\.env {
        deny all;
    }

    # Cache static assets
    location ~* \.(js|css|png|jpg|jpeg|gif|ico|svg|woff|woff2|ttf|eot)$ {
        expires 1y;
        add_header Cache-Control "public, immutable";
    }

    # Upload size
    client_max_body_size 50M;
}
```

Enable site and restart Nginx:

```bash
sudo ln -s /etc/nginx/sites-available/ma-dealroom /etc/nginx/sites-enabled/
sudo nginx -t
sudo systemctl reload nginx
```

## Cron Job Configuration

### 1. Verify WP-Cron Status

```bash
sudo -u www-data wp cron event list
```

### 2. Set Up System Cron (Recommended)

Disable WordPress cron in `wp-config.php`:

```php
define('DISABLE_WP_CRON', true);
```

Add to crontab:

```bash
sudo crontab -e -u www-data

# Add these lines:
# Run WordPress cron every 5 minutes
*/5 * * * * cd /var/www/html && wp cron event run --due-now > /dev/null 2>&1

# Process reminders hourly
0 * * * * cd /var/www/html && wp ma-deal reminders:send > /dev/null 2>&1

# Process queue hourly
0 * * * * cd /var/www/html && wp ma-deal queue:run > /dev/null 2>&1

# Daily backup at 3 AM
0 3 * * * cd /var/www/html && wp ma-deal backup > /var/log/ma-deal-backup.log 2>&1

# Cleanup old notifications daily at 2 AM
0 2 * * * cd /var/www/html && wp db query "DELETE FROM wp_ma_notifications WHERE is_read = 1 AND created_at < DATE_SUB(NOW(), INTERVAL 30 DAY);" > /dev/null 2>&1
```

### 3. Verify Cron Jobs

```bash
sudo crontab -l -u www-data
```

## Monitoring Setup

### 1. Configure Sentry

Sentry is automatically initialized if DSN is set in `.env` file.

Verify configuration:
```bash
cd /var/www/html
sudo -u www-data wp ma-deal test:sentry
```

### 2. Set Up Server Monitoring

Install monitoring tools:

```bash
# Install htop for process monitoring
sudo apt install -y htop

# Install monitoring agent (example: New Relic)
# Follow provider-specific instructions
```

### 3. Set Up Log Rotation

Create `/etc/logrotate.d/ma-dealroom`:

```
/var/log/nginx/ma-dealroom-*.log {
    daily
    missingok
    rotate 14
    compress
    delaycompress
    notifempty
    create 0640 www-data adm
    sharedscripts
    postrotate
        [ -f /var/run/nginx.pid ] && kill -USR1 `cat /var/run/nginx.pid`
    endscript
}

/var/log/ma-deal-*.log {
    daily
    missingok
    rotate 14
    compress
    delaycompress
    notifempty
    create 0640 www-data www-data
}
```

### 4. Set Up Uptime Monitoring

Configure external uptime monitoring:

- UptimeRobot (free)
- Pingdom
- StatusCake
- AWS CloudWatch

Monitor:
- Homepage: https://yourdomain.com
- API health: https://yourdomain.com/wp-json/ma-deal/v1/health
- WordPress admin: https://yourdomain.com/wp-admin

## Backup Verification

### 1. Create Manual Backup

```bash
sudo -u www-data wp ma-deal backup
```

### 2. List Backups

```bash
sudo -u www-data wp ma-deal backup list
```

### 3. Test Restore (in staging!)

**WARNING: Only test restore in staging environment!**

```bash
# In staging environment only
sudo -u www-data wp ma-deal backup restore backup-20251101-030000.sql.gz
```

### 4. Configure Off-Site Backup (Optional)

Set up S3 backup (update BackupService.php for S3 support):

```bash
# Install AWS CLI
sudo apt install -y awscli

# Configure AWS credentials
aws configure
```

## Performance Optimization

### 1. Enable OpCache

Verify OpCache is enabled:

```bash
php -i | grep opcache
```

### 2. Configure Object Caching

WordPress should be using Redis for object caching. Verify:

```bash
redis-cli
> KEYS wp:*
```

### 3. Enable Nginx Caching (Optional)

Add to Nginx configuration:

```nginx
# FastCGI cache
fastcgi_cache_path /var/cache/nginx levels=1:2 keys_zone=WORDPRESS:100m inactive=60m;
fastcgi_cache_key "$scheme$request_method$host$request_uri";

# In server block
location ~ \.php$ {
    # ... existing PHP configuration ...

    fastcgi_cache WORDPRESS;
    fastcgi_cache_valid 200 60m;
    fastcgi_cache_bypass $skip_cache;
    fastcgi_no_cache $skip_cache;
    add_header X-Cache-Status $upstream_cache_status;
}
```

### 4. Optimize Database

```bash
# Optimize all tables
sudo -u www-data wp db optimize

# Clean up revisions
sudo -u www-data wp post delete $(wp post list --post_type='revision' --format=ids) --force

# Clean up transients
sudo -u www-data wp transient delete --all
```

### 5. Configure CDN (Optional)

- CloudFlare (recommended, free tier available)
- AWS CloudFront
- Fastly

## Security Hardening

### 1. File Permissions

```bash
# Set secure permissions
sudo find /var/www/html -type d -exec chmod 755 {} \;
sudo find /var/www/html -type f -exec chmod 644 {} \;
sudo chmod 440 /var/www/html/wp-config.php
sudo chmod 440 /var/www/html/wp-content/plugins/ma-deal-room/.env
```

### 2. Install Fail2Ban

```bash
sudo apt install -y fail2ban

# Create WordPress jail
sudo nano /etc/fail2ban/jail.d/wordpress.conf
```

Add:
```ini
[wordpress-hard]
enabled = true
port = http,https
filter = wordpress-hard
logpath = /var/log/nginx/ma-dealroom-access.log
maxretry = 3
bantime = 3600
```

### 3. Disable XML-RPC

Add to WordPress `.htaccess` or Nginx config:

```nginx
location = /xmlrpc.php {
    deny all;
    access_log off;
    log_not_found off;
}
```

### 4. Security Headers

Security headers are automatically added by the plugin's SecurityHeadersMiddleware.

Verify headers:
```bash
curl -I https://yourdomain.com
```

### 5. Hide WordPress Version

Add to `wp-config.php`:

```php
remove_action('wp_head', 'wp_generator');
```

## Post-Deployment

### 1. Verify Installation

Run through deployment checklist (see `DEPLOYMENT_CHECKLIST.md`).

### 2. Test Core Features

- [ ] User registration
- [ ] User login
- [ ] Email verification
- [ ] Password reset
- [ ] Transaction creation
- [ ] Task management
- [ ] Document upload
- [ ] Email notifications
- [ ] SMS notifications
- [ ] Search functionality

### 3. Performance Testing

```bash
# Test page load time
curl -w "@curl-format.txt" -o /dev/null -s https://yourdomain.com

# Test API response time
time curl https://yourdomain.com/wp-json/ma-deal/v1/transactions
```

Create `curl-format.txt`:
```
time_namelookup:  %{time_namelookup}\n
time_connect:  %{time_connect}\n
time_appconnect:  %{time_appconnect}\n
time_pretransfer:  %{time_pretransfer}\n
time_redirect:  %{time_redirect}\n
time_starttransfer:  %{time_starttransfer}\n
----------\n
time_total:  %{time_total}\n
```

### 4. Security Audit

- [ ] SSL Labs test: https://www.ssllabs.com/ssltest/
- [ ] SecurityHeaders.com: https://securityheaders.com/
- [ ] OWASP ZAP scan (or similar)
- [ ] WordPress security scan
- [ ] Verify firewall rules

### 5. Monitoring Verification

- [ ] Sentry receiving errors
- [ ] Uptime monitoring active
- [ ] Backup running successfully
- [ ] Cron jobs executing
- [ ] Logs rotating correctly

## Rollback Procedures

See [`scripts/rollback.sh`](../../scripts/rollback.sh) for automated rollback.

### Manual Rollback Steps

#### 1. Restore Database

```bash
# List backups
sudo -u www-data wp ma-deal backup list

# Restore specific backup
sudo -u www-data wp ma-deal backup restore backup-YYYYMMDD-HHMMSS.sql.gz
```

#### 2. Restore Files

```bash
# If you have file backup
cd /var/www/html/wp-content/plugins
sudo rm -rf ma-deal-room
sudo tar -xzf /path/to/ma-deal-room-backup.tar.gz
sudo chown -R www-data:www-data ma-deal-room
```

#### 3. Restore Configuration

```bash
# Restore .env file
sudo cp /path/to/.env.backup .env
sudo chmod 440 .env
sudo chown www-data:www-data .env

# Restore wp-config.php if needed
sudo cp /path/to/wp-config.php.backup /var/www/html/wp-config.php
```

#### 4. Clear Cache

```bash
# Clear object cache
redis-cli FLUSHDB

# Clear opcache
sudo systemctl reload php8.1-fpm
```

#### 5. Verify Rollback

- [ ] Check homepage loads
- [ ] Test login
- [ ] Verify database records
- [ ] Check error logs

## Troubleshooting

See [`TROUBLESHOOTING.md`](./TROUBLESHOOTING.md) for common issues and solutions.

## Support

For deployment support:

- GitHub Issues: https://github.com/yourusername/ma-deal-room/issues
- Documentation: [`/docs`](../)
- Email: support@yourdomain.com

## Additional Resources

- [`SERVER_REQUIREMENTS.md`](./SERVER_REQUIREMENTS.md) - Server requirements
- [`DEPLOYMENT_CHECKLIST.md`](./DEPLOYMENT_CHECKLIST.md) - Deployment checklist
- [`TROUBLESHOOTING.md`](./TROUBLESHOOTING.md) - Troubleshooting guide
- [`environment-variables.md`](./environment-variables.md) - Environment variables
- [`database-backups.md`](./database-backups.md) - Backup system
- [`monitoring.md`](./monitoring.md) - Monitoring setup
- [`ssl-configuration.md`](./ssl-configuration.md) - SSL/HTTPS configuration
