# SendGrid Integration Guide

## Overview

MA Deal Room integrates with SendGrid for reliable, scalable email delivery in production environments. The plugin automatically switches between SendGrid (production), MailHog (development), and WordPress default wp_mail() based on configuration and environment.

## Features

✅ **Automatic Delivery Mode Selection**
- SendGrid API when API key is configured
- MailHog SMTP for development/testing
- WordPress default wp_mail() as fallback

✅ **HTML Email Templates**
- Professional, responsive email templates
- Mobile-optimized layouts
- Email-client compatible (Gmail, Outlook, Apple Mail)

✅ **Error Handling & Fallback**
- Automatic fallback to wp_mail() if SendGrid fails
- Comprehensive error logging
- Retry logic with exponential backoff

✅ **Email Delivery Tracking**
- Logs all email attempts (sent/failed)
- Tracks delivery provider (SendGrid/wp_mail/MailHog)
- Records status codes and error messages

## Prerequisites

- PHP 7.4 or higher
- Composer (for installing SendGrid SDK)
- SendGrid account (free tier available)

## Installation

### 1. Install SendGrid SDK

The SendGrid SDK should already be installed via Composer:

```bash
cd ma-deal-room
composer require sendgrid/sendgrid
```

**Installed Version:** sendgrid/sendgrid ^8.1

**Dependencies:**
- sendgrid/php-http-client ^4.1
- starkbank/ecdsa ^0.0.5

### 2. Get SendGrid API Key

1. Sign up for SendGrid at https://signup.sendgrid.com
2. Navigate to Settings > API Keys: https://app.sendgrid.com/settings/api_keys
3. Click "Create API Key"
4. Name it "MA Deal Room Production" (or similar)
5. Select "Full Access" or "Restricted Access" with at least "Mail Send" permission
6. Copy the API key (you won't be able to see it again!)

### 3. Configure Environment Variables

Add the SendGrid API key to your environment:

**For Docker/Production:**

Update `.env` file:
```bash
SENDGRID_API_KEY=SG.xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx
WP_ENV=production
```

**For WordPress:**

Update `wp-config.php` (recommended for production):
```php
define('SENDGRID_API_KEY', 'SG.xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx');
define('WP_ENV', 'production');
```

**Note:** Both `SENDGRID_API_KEY` and `MA_DEAL_SENDGRID_API_KEY` are supported.

### 4. Verify Configuration

The plugin automatically detects the SendGrid API key on initialization. Check the WordPress error log for confirmation:

```
[Email Config] SendGrid API key detected - using SendGrid for email delivery
```

## Email Delivery Modes

The plugin automatically selects the email delivery mode based on configuration:

### Mode 1: SendGrid API (Production)

**When:** `SENDGRID_API_KEY` is set

**How it works:**
1. EmailService detects API key in constructor
2. Initializes SendGrid client
3. Routes all emails through `sendWithSendGrid()` method
4. Uses SendGrid API v3 for delivery
5. Falls back to wp_mail() on error

**Logging:**
```
[SendGrid] Email sent to user@example.com: Subject (Status: 202)
[Email Log] To: user@example.com | Status: sent | Provider: sendgrid | Code: 202
```

### Mode 2: MailHog SMTP (Development)

**When:** `SENDGRID_API_KEY` is NOT set AND environment is development
- `WP_ENV` = development, dev, or local
- OR `WP_DEBUG` = true
- OR `WP_ENVIRONMENT_TYPE` = local

**How it works:**
1. Plugin detects development environment
2. Configures PHPMailer to use MailHog SMTP
3. All emails sent to MailHog for testing
4. Access MailHog web UI at http://localhost:8025

**Logging:**
```
[Email Config] Development environment detected - using MailHog: avn-mailhog:1025
```

### Mode 3: WordPress wp_mail() (Fallback)

**When:** `SENDGRID_API_KEY` is NOT set AND environment is production

**How it works:**
1. Uses WordPress default mail function
2. Retry logic with exponential backoff (3 attempts)
3. May use server's default SMTP or sendmail

**Logging:**
```
[Email Config] Production environment - using default wp_mail configuration
[wp_mail] Email sent to user@example.com: Subject (attempt 1)
```

## Usage Examples

### Basic Email Sending

The EmailService handles delivery mode selection automatically:

```php
use MADealRoom\Services\EmailService;

$email_service = new EmailService();

// Send welcome email (automatically uses SendGrid if configured)
$email_service->send_welcome_email(
    'user@example.com',
    'John Doe',
    'https://example.com/login'
);
```

### Email Verification

```php
$email_service->send_email_verification(
    'user@example.com',
    'Jane Smith',
    'https://example.com/verify?token=abc123',
    24 // expires in 24 hours
);
```

### Password Reset

```php
$email_service->send_password_reset(
    'user@example.com',
    'John Doe',
    'https://example.com/reset?token=xyz789',
    1 // expires in 1 hour
);
```

### Task Assignment Notification

```php
$email_service->sendTaskAssignedNotification(
    $task,
    $transaction,
    $assignee
);
```

### Check Current Delivery Mode

```php
$mode = $email_service->getDeliveryMode();
// Returns: 'sendgrid', 'wp_mail', or 'mailhog'
```

## SendGrid Domain Authentication (Recommended)

For better deliverability and to avoid spam filters, authenticate your sending domain with SendGrid:

### 1. Navigate to Domain Authentication

https://app.sendgrid.com/settings/sender_auth/domain/create

### 2. Enter Your Domain

Enter the domain you'll send emails from (e.g., `madealroom.com`)

### 3. Configure DNS Records

SendGrid will provide DNS records to add to your domain:

```
Type: CNAME
Host: em1234.yourdomain.com
Value: u1234567.wl123.sendgrid.net

Type: CNAME
Host: s1._domainkey.yourdomain.com
Value: s1.domainkey.u1234567.wl123.sendgrid.net

Type: CNAME
Host: s2._domainkey.yourdomain.com
Value: s2.domainkey.u1234567.wl123.sendgrid.net
```

### 4. Verify DNS

After adding DNS records (can take up to 48 hours to propagate), verify them in SendGrid dashboard.

### 5. Update From Address

Once verified, emails will be sent from `noreply@yourdomain.com` automatically.

## Monitoring & Analytics

### SendGrid Dashboard

Monitor email delivery in the SendGrid dashboard:
- **Activity Feed:** https://app.sendgrid.com/email_activity
- **Statistics:** https://app.sendgrid.com/statistics
- **Suppressions:** https://app.sendgrid.com/suppressions

### Metrics to Monitor

- **Deliverability Rate:** Should be >95%
- **Bounce Rate:** Should be <5%
- **Spam Report Rate:** Should be <0.1%
- **Open Rate:** Varies by email type (20-40% typical)

### Email Logs

All email delivery attempts are logged to WordPress error log:

```
[Email Log] To: user@example.com | Subject: Welcome | Status: sent | Provider: sendgrid | Code: 202
```

**Future:** Email logs will be stored in database table for analytics and reporting.

## Error Handling

### SendGrid API Errors

Common error codes:

| Code | Meaning | Solution |
|------|---------|----------|
| 401 | Unauthorized | Check API key is correct |
| 403 | Forbidden | API key lacks permissions |
| 413 | Payload Too Large | Reduce email size/attachments |
| 429 | Rate Limit Exceeded | Reduce send frequency |
| 500 | Internal Server Error | Retry or contact SendGrid support |

### Fallback Behavior

If SendGrid fails, the plugin automatically falls back to wp_mail():

```php
try {
    // Attempt SendGrid delivery
    $response = $this->sendgrid_client->send($email);
} catch (\Exception $e) {
    error_log('[SendGrid] Exception: ' . $e->getMessage());
    error_log('[SendGrid] Falling back to wp_mail');
    return $this->sendWithWPMail($to, $subject, $body, $headers);
}
```

### Debug Mode

Enable WordPress debug logging to see detailed email delivery logs:

**wp-config.php:**
```php
define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true);
define('WP_DEBUG_DISPLAY', false);
```

**Check logs:**
```bash
tail -f wp-content/debug.log
```

## Testing

### Development Testing (MailHog)

In development, all emails go to MailHog:

1. Send test email from your application
2. Open MailHog: http://localhost:8025
3. View email in MailHog web UI
4. Verify HTML rendering and content

### SendGrid Testing (Staging)

Test SendGrid integration in staging environment:

```bash
# Set SendGrid API key
export SENDGRID_API_KEY="SG.test_key_xxxxxxxxxxxx"
export WP_ENV="staging"

# Send test email via WordPress CLI or application
wp eval "ma_deal_room_test_sendgrid_email('your-email@example.com');"
```

**Test function** (add to `functions.php` for testing):
```php
function ma_deal_room_test_sendgrid_email($to) {
    $email_service = new \MADealRoom\Services\EmailService();
    $result = $email_service->send_welcome_email(
        $to,
        'Test User',
        admin_url('admin.php?page=ma-deal-room')
    );

    error_log('SendGrid test result: ' . ($result ? 'SUCCESS' : 'FAILED'));
    error_log('Delivery mode: ' . $email_service->getDeliveryMode());

    return $result;
}
```

### Production Testing

Before going live:

1. ✅ Verify SendGrid API key is configured
2. ✅ Test welcome email delivery
3. ✅ Test password reset email
4. ✅ Test task notification emails
5. ✅ Check SendGrid Activity Feed for deliveries
6. ✅ Monitor bounce/spam rates
7. ✅ Verify domain authentication is active

## Troubleshooting

### Emails Not Sending

**Problem:** Emails not being delivered

**Solutions:**
1. Check SendGrid API key is correctly set:
   ```bash
   wp eval "echo getenv('SENDGRID_API_KEY');"
   ```

2. Check WordPress error log:
   ```bash
   tail -f wp-content/debug.log | grep -i sendgrid
   ```

3. Verify SendGrid account is active (not suspended)

4. Check SendGrid Activity Feed for blocks/suppressions

### SendGrid Returns 401 Unauthorized

**Problem:** API key is invalid or expired

**Solutions:**
1. Generate new API key in SendGrid dashboard
2. Update environment variable
3. Restart WordPress/PHP-FPM

### Emails Going to Spam

**Problem:** High spam complaint rate

**Solutions:**
1. Set up domain authentication (see above)
2. Add proper SPF/DKIM records
3. Use clear, descriptive subject lines
4. Include unsubscribe link in emails
5. Avoid spam trigger words
6. Warm up sending IP (for high volume)

### Rate Limit Exceeded (429)

**Problem:** Sending too many emails too quickly

**Solutions:**
1. Implement email queueing (future feature)
2. Reduce send frequency
3. Upgrade SendGrid plan for higher limits
4. Implement batch sending with delays

## Best Practices

### Email Deliverability

1. **Authenticate Your Domain:** Set up SPF, DKIM, and DMARC records
2. **Monitor Metrics:** Watch bounce and spam rates closely
3. **Clean Your List:** Remove invalid/bounced email addresses
4. **Avoid Spam Triggers:** Test subject lines, avoid ALL CAPS
5. **Include Unsubscribe:** Add unsubscribe link to all marketing emails

### Security

1. **Protect API Key:** Never commit API keys to version control
2. **Use Environment Variables:** Store API key in .env or wp-config.php
3. **Restrict Permissions:** Use API key with minimal required permissions
4. **Rotate Keys:** Change API keys periodically
5. **Monitor Usage:** Watch for unusual sending patterns

### Performance

1. **Use Async Sending:** Queue bulk emails for background processing
2. **Batch Operations:** Group similar emails together
3. **Cache Templates:** Enable template caching in EmailTemplateService
4. **Monitor API Calls:** Track SendGrid API usage and rate limits

## Cost Considerations

### SendGrid Pricing (as of 2025)

- **Free Tier:** 100 emails/day
- **Essentials:** $19.95/month for 50,000 emails
- **Pro:** $89.95/month for 100,000 emails
- **Premier:** Custom pricing for high volume

**Recommendation:** Start with Free tier for testing, upgrade to Essentials for production.

## Support & Resources

- **SendGrid Documentation:** https://docs.sendgrid.com/
- **API Reference:** https://docs.sendgrid.com/api-reference
- **Status Page:** https://status.sendgrid.com/
- **Support:** https://support.sendgrid.com/

---

**Last Updated:** 2025-11-01
**Plugin Version:** 1.0.0
**SendGrid SDK Version:** 8.1.2
