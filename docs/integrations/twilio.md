# Twilio SMS Integration Guide

## Overview

MA Deal Room integrates with Twilio for reliable SMS notifications and reminders. The plugin includes a comprehensive SMS service with automatic opt-in/opt-out handling, delivery status tracking, and webhook support for real-time status updates.

## Features

✅ **SMS Notifications**
- Task reminders and assignments
- Transaction status updates
- Verification codes (future)
- Custom SMS messages

✅ **Opt-Out/Opt-In Management**
- Automatic STOP/START keyword handling
- Compliance with US regulations (TCPA, CTIA)
- Per-user SMS preferences

✅ **Delivery Tracking**
- Real-time delivery status via webhooks
- SMS logs with masked phone numbers
- Error handling and retry logic

✅ **Security**
- Twilio request signature validation
- Phone number format validation (E.164)
- Secure credential storage

## Prerequisites

- PHP 7.4 or higher
- Composer (for installing Twilio SDK)
- Twilio account with SMS-enabled phone number
- Active Twilio subscription (pay-as-you-go or plan)

## Installation

### 1. Install Twilio SDK

The Twilio SDK should already be installed via Composer:

```bash
cd ma-deal-room
composer require twilio/sdk
```

**Installed Version:** twilio/sdk ^8.8

### 2. Get Twilio Credentials

#### Sign Up for Twilio

1. Create account at https://www.twilio.com/try-twilio
2. Verify your email and phone number
3. Navigate to Console: https://console.twilio.com/

#### Get Account SID and Auth Token

1. Find on Console Dashboard: https://console.twilio.com/
2. **Account SID:** Starts with "AC" (e.g., `ACxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx`)
3. **Auth Token:** Click "Show" to reveal (keep secret!)

#### Get a Phone Number

1. Navigate to Phone Numbers: https://console.twilio.com/us1/develop/phone-numbers/manage/search
2. Click "Buy a number"
3. Select country (US) and capabilities (SMS, MMS)
4. Search for available numbers
5. Purchase number (typically $1-2/month)
6. Copy phone number in E.164 format (e.g., `+12025551234`)

### 3. Configure Environment Variables

Add Twilio credentials to your environment:

**For Docker/Production:**

Update `.env` file:
```bash
TWILIO_ACCOUNT_SID=ACxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx
TWILIO_AUTH_TOKEN=your_auth_token_here
TWILIO_FROM_NUMBER=+12025551234
```

**For WordPress:**

Update `wp-config.php`:
```php
define('TWILIO_ACCOUNT_SID', 'ACxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx');
define('TWILIO_AUTH_TOKEN', 'your_auth_token_here');
define('TWILIO_FROM_NUMBER', '+12025551234');
```

**Note:** Both `TWILIO_*` and `MA_DEAL_TWILIO_*` variable names are supported.

### 4. Verify Configuration

Check WordPress error log for confirmation:

```
[Twilio] SMS service initialized successfully
```

If not configured:
```
[Twilio] SMS service not configured - missing credentials
```

## Usage Examples

### Basic SMS Sending

```php
use MADealRoom\Services\SMSService;

$sms_service = new SMSService();

// Check if service is enabled
if ($sms_service->is_enabled()) {
    // Send SMS
    $result = $sms_service->send_sms(
        '+12025551234',  // Recipient (E.164 format)
        'Your task is due tomorrow. Please complete ASAP.'
    );

    if ($result === true) {
        // Success
        echo "SMS sent successfully";
    } else {
        // Failed - $result is error array
        echo "Failed: " . $result['error'];
    }
}
```

### Task Reminder SMS

```php
$result = $sms_service->send_task_reminder(
    '+12025551234',
    'Review purchase agreement',
    'tomorrow at 3 PM'
);
```

**Template:** `"MA Deal Room: {{task_title}} is due {{due_date}}. Please complete ASAP."`

**Example Output:** `"MA Deal Room: Review purchase agreement is due tomorrow at 3 PM. Please complete ASAP."`

### Task Assignment SMS

```php
$result = $sms_service->send_task_assignment(
    '+12025551234',
    'Upload inspection report'
);
```

**Template:** `"MA Deal Room: You've been assigned \"{{task_title}}\". Login to view details."`

**Example Output:** `"MA Deal Room: You've been assigned \"Upload inspection report\". Login to view details."`

### Transaction Update SMS

```php
$result = $sms_service->send_transaction_update(
    '+12025551234',
    '123 Main Street, Boston',
    'Under Contract'
);
```

**Template:** `"MA Deal Room: {{transaction_address}} status updated to {{status}}."`

**Example Output:** `"MA Deal Room: 123 Main Street, Boston status updated to Under Contract."`

## Phone Number Format

All phone numbers MUST use E.164 international format:

**Format:** `+[country code][area code][number]`

**Examples:**
- ✅ `+12025551234` (US number)
- ✅ `+447700900123` (UK number)
- ❌ `(202) 555-1234` (Invalid - formatted)
- ❌ `202-555-1234` (Invalid - missing country code)
- ❌ `2025551234` (Invalid - missing +)

**Validation:**
```php
// SMSService automatically validates phone numbers
$result = $sms_service->send_sms('2025551234', 'Test');
// Returns: ['success' => false, 'error' => 'Invalid phone number format...']
```

## SMS Templates

Built-in templates (defined in SMSService):

| Template | Variables | Max Length |
|----------|-----------|------------|
| `task_reminder` | task_title, due_date | 160 chars |
| `task_assignment` | task_title | 160 chars |
| `transaction_update` | transaction_address, status | 160 chars |
| `verification_code` | code | 160 chars |
| `password_reset` | code, expiry | 160 chars |

**Note:** Messages are automatically truncated to 160 characters (single SMS). Messages >160 chars are split into multiple SMS (charged separately).

## Webhook Configuration

Webhooks enable real-time delivery status updates and opt-out handling.

### 1. Webhook URLs

Add these webhook URLs in Twilio Console:

**Status Callback URL** (for delivery updates):
```
https://yourdomain.com/wp-json/ma-deal-room/v1/twilio/webhook/status
```

**Incoming SMS URL** (for STOP/START keywords):
```
https://yourdomain.com/wp-json/ma-deal-room/v1/twilio/webhook/incoming
```

### 2. Configure in Twilio Console

#### For Messaging Service (Recommended):

1. Navigate to: https://console.twilio.com/us1/develop/sms/services
2. Click your Messaging Service (or create one)
3. Go to "Integration" tab
4. Set "Status Callback URL" to status webhook URL
5. Set "Inbound Request URL" to incoming webhook URL
6. HTTP Method: POST
7. Save

#### For Individual Phone Number:

1. Navigate to: https://console.twilio.com/us1/develop/phone-numbers/manage/incoming
2. Click your phone number
3. Scroll to "Messaging Configuration"
4. Set "A MESSAGE COMES IN" to incoming webhook URL
5. HTTP Method: POST
6. Set "Status Callbacks" to status webhook URL
7. Save

### 3. Test Webhook

Verify webhook is accessible:

```bash
curl https://yourdomain.com/wp-json/ma-deal-room/v1/twilio/webhook/test
```

**Expected Response:**
```json
{
  "success": true,
  "message": "Twilio webhook endpoint is accessible",
  "timestamp": "2025-11-01 14:30:00",
  "webhook_url": "https://yourdomain.com/wp-json/ma-deal-room/v1/twilio/webhook"
}
```

## Opt-Out/Opt-In Management

### Automatic Keyword Handling

Users can opt out by replying with keywords:
- **STOP** - Unsubscribe from all SMS
- **STOPALL** - Same as STOP
- **UNSUBSCRIBE** - Same as STOP
- **CANCEL** - Same as STOP
- **END** - Same as STOP
- **QUIT** - Same as STOP

Users can opt back in with:
- **START** - Re-subscribe to SMS
- **YES** - Same as START
- **UNSTOP** - Same as START

### Auto-Response Messages

**When user sends STOP:**
```
You have been unsubscribed from MA Deal Room SMS notifications. Reply START to re-subscribe.
```

**When user sends START:**
```
Welcome back! You have been re-subscribed to MA Deal Room SMS notifications. Reply STOP to unsubscribe.
```

### Manual Opt-Out Management

```php
$sms_service = new SMSService();

// Check if phone number is opted out
if ($sms_service->is_opted_out('+12025551234')) {
    echo "User has opted out of SMS";
}

// Manually opt out a user
$sms_service->opt_out('+12025551234');

// Manually opt in a user
$sms_service->opt_in('+12025551234');
```

## Delivery Status Tracking

SMS delivery statuses (via webhook):

| Status | Meaning |
|--------|---------|
| `queued` | Message queued for sending |
| `sending` | Message is being sent |
| `sent` | Message sent to carrier |
| `delivered` | Message delivered to recipient |
| `failed` | Message failed to send |
| `undelivered` | Message sent but not delivered |

**Webhook Processing:**

When Twilio sends delivery status update, it's automatically logged:

```
[Twilio Webhook] Message SM1234567890abcdef status: delivered
```

## Error Handling

### Common Errors

| Error Code | Error Message | Solution |
|------------|---------------|----------|
| 21608 | Unverified number (trial account) | Verify recipient or upgrade account |
| 21211 | Invalid 'To' phone number | Use E.164 format (+12025551234) |
| 21614 | Number is not a mobile number | Use mobile number, not landline |
| 21610 | Message filtered (STOP received) | User opted out - remove from list |
| 20003 | Authentication error | Check Account SID and Auth Token |
| 21606 | From number not owned | Verify TWILIO_FROM_NUMBER |

### Handling Errors in Code

```php
$result = $sms_service->send_sms('+12025551234', 'Test message');

if ($result !== true) {
    // Error occurred
    $error_message = $result['error'];
    $error_code = $result['code'];

    error_log("SMS failed: {$error_message} (Code: {$error_code})");

    // Handle specific errors
    if ($error_code == 21608) {
        // Unverified number on trial account
        echo "Please verify this number in Twilio Console";
    } elseif ($error_code == 21610) {
        // User opted out
        echo "User has unsubscribed from SMS";
    }
}
```

## SMS Logs

All SMS attempts are logged to WordPress error log:

```
[SMS Log] To: +120****34 | Message: MA Deal Room: Task is due tomorrow | Status: sent | SID: SM1234567890abcdef
```

**Privacy:** Phone numbers are automatically masked in logs (shows first 4 digits + last 2).

**Future:** SMS logs will be stored in database table for analytics and reporting.

## Testing

### Development Testing (Trial Account)

Twilio trial accounts have limitations:
- Can only send SMS to verified numbers
- Messages prefixed with "Sent from your Twilio trial account"
- Limited to specific countries
- No inbound SMS on trial

**To verify numbers (trial only):**
1. Go to: https://console.twilio.com/us1/develop/phone-numbers/manage/verified
2. Click "Add a new number"
3. Enter number and verify via SMS

### Test SMS Sending

```php
// Test function (add to functions.php for testing)
function ma_deal_room_test_twilio_sms($to) {
    $sms_service = new \MADealRoom\Services\SMSService();

    if (!$sms_service->is_enabled()) {
        error_log('Twilio not configured');
        return false;
    }

    $result = $sms_service->send_sms(
        $to,
        'MA Deal Room: This is a test SMS message.'
    );

    error_log('Twilio test result: ' . ($result === true ? 'SUCCESS' : 'FAILED'));

    if ($result !== true) {
        error_log('Twilio error: ' . $result['error']);
    }

    return $result;
}

// Run test
ma_deal_room_test_twilio_sms('+12025551234');
```

### Production Testing

Before going live:

1. ✅ Verify Twilio credentials are configured
2. ✅ Test task reminder SMS
3. ✅ Test task assignment SMS
4. ✅ Test transaction update SMS
5. ✅ Configure webhooks in Twilio Console
6. ✅ Test STOP keyword (opt-out)
7. ✅ Test START keyword (opt-in)
8. ✅ Verify webhook signature validation
9. ✅ Monitor Twilio Console for delivery rates
10. ✅ Check SMS logs in WordPress error log

## Best Practices

### Compliance

1. **Get Consent:** Only send SMS to users who opted in
2. **Identify Yourself:** Include business name in messages
3. **Provide Opt-Out:** Allow users to reply STOP
4. **Honor Opt-Outs:** Immediately stop sending to opted-out numbers
5. **Keep Records:** Log all SMS and opt-out requests

**US Regulations:**
- TCPA (Telephone Consumer Protection Act)
- CTIA Messaging Principles and Best Practices

### Message Content

1. **Be Concise:** Keep messages under 160 characters
2. **Be Clear:** State purpose clearly
3. **Include CTA:** Tell users what to do next
4. **Avoid Spam:** Don't send promotional content without consent
5. **Time Appropriately:** Send during business hours (9 AM - 9 PM local time)

### Cost Optimization

1. **Use SMS Sparingly:** Reserve for important notifications only
2. **Consolidate Messages:** Batch updates when possible
3. **Set Frequency Caps:** Limit SMS per user per day
4. **Monitor Usage:** Track SMS volume and costs
5. **Consider Email First:** Use SMS only when email isn't sufficient

## Pricing

### Twilio SMS Pricing (US, as of 2025)

- **Outbound SMS:** ~$0.0075 per message
- **Inbound SMS:** ~$0.0075 per message
- **Phone Number:** ~$1.00-$2.00/month
- **Toll-Free Number:** ~$2.00/month

**Example Costs:**
- 100 SMS/month: ~$0.75 + $1.00 = $1.75/month
- 1,000 SMS/month: ~$7.50 + $1.00 = $8.50/month
- 10,000 SMS/month: ~$75.00 + $1.00 = $76.00/month

**Note:** Prices vary by country. Check https://www.twilio.com/sms/pricing

## Monitoring

### Twilio Console

Monitor SMS delivery in Twilio Console:
- **Logs:** https://console.twilio.com/us1/monitor/logs/sms
- **Usage:** https://console.twilio.com/us1/billing/usage
- **Insights:** https://console.twilio.com/us1/monitor/insights

### Metrics to Track

- **Delivery Rate:** Should be >95%
- **Failure Rate:** Should be <5%
- **Opt-Out Rate:** Should be <2%
- **Average Cost:** Track cost per SMS

### WordPress Logs

Check WordPress error log:
```bash
tail -f wp-content/debug.log | grep -i twilio
```

## Troubleshooting

### SMS Not Sending

**Problem:** SMS not being delivered

**Solutions:**
1. Verify Twilio credentials are correctly configured
2. Check phone number is in E.164 format
3. Verify phone number is verified (trial accounts)
4. Check Twilio Console logs for errors
5. Ensure account has sufficient balance

### "Unverified Number" Error (Trial Account)

**Problem:** Error 21608 - Number not verified

**Solutions:**
1. Verify number in Twilio Console
2. Upgrade to paid account (removes restriction)

### Webhook Not Receiving Updates

**Problem:** Delivery status not updating

**Solutions:**
1. Verify webhook URL is publicly accessible
2. Check webhook is configured in Twilio Console
3. Test webhook endpoint with curl
4. Check WordPress error log for webhook errors
5. Verify signature validation is working

### Invalid Signature Error

**Problem:** Webhook requests failing validation

**Solutions:**
1. Verify TWILIO_AUTH_TOKEN is correct
2. Check webhook URL matches exactly (including https://)
3. Ensure WordPress site is accessible publicly
4. Check for load balancer/proxy modifications to requests

## Security

### Protect Credentials

1. **Never Commit:** Don't commit credentials to version control
2. **Use Environment Variables:** Store in .env or wp-config.php
3. **Restrict Access:** Limit who has access to credentials
4. **Rotate Regularly:** Change Auth Token periodically
5. **Use Test Credentials:** Separate credentials for dev/production

### Webhook Security

1. **Validate Signatures:** Always validate Twilio request signatures
2. **Use HTTPS:** Never use plain HTTP for webhooks
3. **Monitor Logs:** Watch for suspicious webhook activity
4. **Rate Limit:** Implement rate limiting for webhook endpoints

## Support & Resources

- **Twilio Documentation:** https://www.twilio.com/docs/sms
- **API Reference:** https://www.twilio.com/docs/sms/api
- **Phone Number Lookup:** https://www.twilio.com/lookup
- **Status Page:** https://status.twilio.com/
- **Support:** https://support.twilio.com/

---

**Last Updated:** 2025-11-01
**Plugin Version:** 1.0.0
**Twilio SDK Version:** 8.8.5
