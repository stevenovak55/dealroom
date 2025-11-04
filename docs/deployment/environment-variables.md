# Environment Variables Configuration Guide

## Overview

MA Deal Room uses environment variables to securely manage sensitive configuration such as JWT secrets, API keys, and database credentials. This approach follows security best practices by keeping secrets out of version control.

## Configuration Methods

The plugin supports multiple methods for loading environment variables:

1. **`.env` file** (recommended for local development)
2. **System environment variables** (recommended for production)
3. **WordPress options** (automatic fallback)

## Quick Start

### Development Setup

1. Copy the example environment file:
   ```bash
   cp .env.example .env
   ```

2. Generate secure JWT secrets:
   ```bash
   # Generate access token secret
   openssl rand -base64 64

   # Generate refresh token secret
   openssl rand -base64 64
   ```

3. Update the `.env` file with your generated secrets and other configuration.

4. The plugin will automatically load the `.env` file on initialization.

### Production Setup

For production environments, set environment variables directly on your server rather than using a `.env` file:

**Apache (.htaccess or VirtualHost):**
```apache
SetEnv JWT_SECRET_KEY "your-64-char-secret-here"
SetEnv JWT_REFRESH_SECRET_KEY "your-64-char-secret-here"
SetEnv SENDGRID_API_KEY "your-sendgrid-key"
```

**Nginx (in your server block):**
```nginx
fastcgi_param JWT_SECRET_KEY "your-64-char-secret-here";
fastcgi_param JWT_REFRESH_SECRET_KEY "your-64-char-secret-here";
fastcgi_param SENDGRID_API_KEY "your-sendgrid-key";
```

**Docker Compose:**
```yaml
environment:
  - JWT_SECRET_KEY=your-64-char-secret-here
  - JWT_REFRESH_SECRET_KEY=your-64-char-secret-here
  - SENDGRID_API_KEY=your-sendgrid-key
```

## Required Environment Variables

### JWT Authentication (Required)

| Variable | Description | Default | Required |
|----------|-------------|---------|----------|
| `JWT_SECRET_KEY` | Secret key for access tokens (min 64 chars) | Auto-generated | Yes |
| `JWT_REFRESH_SECRET_KEY` | Secret key for refresh tokens (min 64 chars) | Auto-generated | Yes |
| `JWT_ACCESS_TOKEN_EXPIRATION` | Access token lifetime in seconds | 900 (15 min) | No |
| `JWT_REFRESH_TOKEN_EXPIRATION` | Refresh token lifetime in seconds | 604800 (7 days) | No |

**Alternative naming:**
- `MA_DEAL_JWT_ACCESS_SECRET` (alias for JWT_SECRET_KEY)
- `MA_DEAL_JWT_REFRESH_SECRET` (alias for JWT_REFRESH_SECRET_KEY)

### Database Configuration

| Variable | Description | Default | Required |
|----------|-------------|---------|----------|
| `DB_HOST` | Database host | localhost | Yes |
| `DB_NAME` | Database name | - | Yes |
| `DB_USER` | Database user | - | Yes |
| `DB_PASSWORD` | Database password | - | Yes |

### Redis Configuration (Optional)

| Variable | Description | Default | Required |
|----------|-------------|---------|----------|
| `REDIS_HOST` | Redis server host | localhost | No |
| `REDIS_PORT` | Redis server port | 6379 | No |

### Email Service Configuration (Optional)

#### SendGrid (Production Email Delivery)

| Variable | Description | Default | Required |
|----------|-------------|---------|----------|
| `SENDGRID_API_KEY` | SendGrid API key | - | No |
| `MA_DEAL_SENDGRID_API_KEY` | Alternative naming | - | No |

Get your SendGrid API key: https://app.sendgrid.com/settings/api_keys

#### SMTP Configuration (Alternative)

| Variable | Description | Default | Required |
|----------|-------------|---------|----------|
| `MA_DEAL_SMTP_HOST` | SMTP server host | - | No |
| `MA_DEAL_SMTP_PORT` | SMTP server port | 587 | No |
| `MA_DEAL_SMTP_USER` | SMTP username | - | No |
| `MA_DEAL_SMTP_PASS` | SMTP password | - | No |
| `MA_DEAL_SMTP_FROM_EMAIL` | From email address | - | No |
| `MA_DEAL_SMTP_FROM_NAME` | From name | MA Deal Room | No |

### SMS Service Configuration (Optional)

#### Twilio (SMS Notifications)

| Variable | Description | Default | Required |
|----------|-------------|---------|----------|
| `TWILIO_ACCOUNT_SID` | Twilio account SID | - | No |
| `TWILIO_AUTH_TOKEN` | Twilio auth token | - | No |
| `TWILIO_FROM_NUMBER` | Twilio phone number | - | No |

**Alternative naming:**
- `MA_DEAL_TWILIO_SID` (alias for TWILIO_ACCOUNT_SID)
- `MA_DEAL_TWILIO_TOKEN` (alias for TWILIO_AUTH_TOKEN)
- `MA_DEAL_TWILIO_FROM_NUMBER` (same as above)

Get your Twilio credentials: https://console.twilio.com/

### Application Environment

| Variable | Description | Default | Required |
|----------|-------------|---------|----------|
| `ENVIRONMENT` | Application environment (development/staging/production) | development | No |
| `MA_DEAL_ENV` | Alternative naming | development | No |

### Security Configuration

| Variable | Description | Default | Required |
|----------|-------------|---------|----------|
| `EMAIL_VERIFICATION_EXPIRATION` | Email verification link expiry (seconds) | 86400 (24h) | No |
| `PASSWORD_RESET_EXPIRATION` | Password reset link expiry (seconds) | 3600 (1h) | No |
| `MAX_FAILED_LOGIN_ATTEMPTS` | Max failed login attempts before lockout | 5 | No |
| `ACCOUNT_LOCKOUT_DURATION` | Account lockout duration (seconds) | 1800 (30 min) | No |
| `MIN_PASSWORD_LENGTH` | Minimum password length | 8 | No |

### Rate Limiting

| Variable | Description | Default | Required |
|----------|-------------|---------|----------|
| `API_RATE_LIMIT_PUBLIC` | Max requests per minute (public endpoints) | 60 | No |
| `API_RATE_LIMIT_AUTHENTICATED` | Max requests per minute (authenticated) | 120 | No |
| `LOGIN_RATE_LIMIT` | Max login attempts per minute | 5 | No |

### Two-Factor Authentication

| Variable | Description | Default | Required |
|----------|-------------|---------|----------|
| `TOTP_ISSUER` | 2FA issuer name (shown in authenticator apps) | MA Deal Room | No |
| `BACKUP_CODES_COUNT` | Number of backup codes to generate | 10 | No |

### Frontend Configuration

| Variable | Description | Default | Required |
|----------|-------------|---------|----------|
| `FRONTEND_URL` | Frontend application URL (for CORS and email links) | - | No |
| `CORS_ALLOWED_ORIGINS` | Comma-separated list of allowed CORS origins | - | No |

### File Upload Configuration

| Variable | Description | Default | Required |
|----------|-------------|---------|----------|
| `MAX_UPLOAD_SIZE` | Maximum file upload size in MB | 10 | No |
| `ALLOWED_FILE_TYPES` | Comma-separated list of allowed file extensions | pdf,doc,docx,xls,xlsx,jpg,jpeg,png,gif | No |

## How Environment Variables are Loaded

The plugin loads environment variables in the following order (first found wins):

1. **System environment variables** (via `getenv()`)
2. **`.env` file** (loaded by phpdotenv library)
3. **WordPress options** (automatic fallback with auto-generation for JWT secrets)

This ensures maximum flexibility across different hosting environments.

## Security Best Practices

### ✅ DO:
- Use strong, randomly generated secrets (minimum 64 characters for JWT)
- Use different secrets for development, staging, and production
- Store production secrets in server environment variables
- Keep `.env` file out of version control (already in `.gitignore`)
- Rotate secrets regularly (at least annually)
- Use HTTPS in production to protect tokens in transit

### ❌ DON'T:
- Commit `.env` files to version control
- Share secrets via email, Slack, or other insecure channels
- Reuse secrets across environments
- Use weak or predictable secrets
- Store production secrets in `.env` files on shared hosting

## Troubleshooting

### JWT Secrets Not Working

If you're seeing JWT-related errors:

1. Verify secrets are set correctly:
   ```bash
   # In your .env file or server environment
   echo $JWT_SECRET_KEY
   ```

2. Check that secrets are at least 64 characters long

3. If using WordPress options fallback, clear and regenerate:
   ```php
   delete_option('ma_deal_jwt_access_secret');
   delete_option('ma_deal_jwt_refresh_secret');
   ```

### Email Not Sending

1. Check if SendGrid API key is configured:
   ```bash
   echo $SENDGRID_API_KEY
   ```

2. Verify key is valid on SendGrid dashboard

3. Check WordPress debug log for email errors:
   ```bash
   tail -f wp-content/debug.log
   ```

### SMS Not Sending

1. Verify all Twilio credentials are set:
   ```bash
   echo $TWILIO_ACCOUNT_SID
   echo $TWILIO_AUTH_TOKEN
   echo $TWILIO_FROM_NUMBER
   ```

2. Check Twilio console for account status and balance

3. Review error logs for Twilio API responses

## Development vs Production

### Development (.env file)
```bash
# Development settings - .env file
ENVIRONMENT=development
WP_DEBUG=true
JWT_SECRET_KEY=dev-secret-min-64-chars-change-in-production
JWT_REFRESH_SECRET_KEY=dev-secret-min-64-chars-change-in-production
```

### Production (Server environment variables)
```bash
# Production settings - server environment
ENVIRONMENT=production
JWT_SECRET_KEY=<strong-64-char-production-secret>
JWT_REFRESH_SECRET_KEY=<strong-64-char-production-secret>
SENDGRID_API_KEY=<actual-sendgrid-key>
TWILIO_ACCOUNT_SID=<actual-twilio-sid>
TWILIO_AUTH_TOKEN=<actual-twilio-token>
```

## Support

For additional help:
- Review `.env.example` for all available variables
- Check WordPress debug log: `wp-content/debug.log`
- Enable WP_DEBUG for detailed error messages
- Contact support: dev@madealroom.com

## Version History

- **v1.0.0** (2025-11-01): Initial environment variables implementation
  - JWT authentication secrets
  - SendGrid email configuration
  - Twilio SMS configuration
  - Redis caching configuration
  - Security and rate limiting settings
