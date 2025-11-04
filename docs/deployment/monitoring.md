# Monitoring and Error Tracking

## Overview

MA Deal Room uses Sentry for error tracking, performance monitoring, and real-time alerts. This system provides comprehensive visibility into application health and user experience across both backend PHP and frontend React components.

## Features

- **Error Tracking**: Automatic capture of PHP errors, exceptions, and React errors
- **Performance Monitoring**: Track transaction creation, template application, and other critical operations
- **User Context**: Errors include user information for debugging
- **Environment Detection**: Automatically adjusts behavior based on dev/staging/prod
- **Breadcrumbs**: Track user actions leading up to errors
- **Session Replay**: See what users did before encountering errors (frontend only)
- **Release Tracking**: Tag errors by plugin version for better tracking

## Sentry Setup

### 1. Create a Sentry Account

1. Go to [sentry.io](https://sentry.io) and sign up
2. Create a new project for MA Deal Room
3. Select "PHP" as the platform (you can add React later)
4. Copy your DSN (Data Source Name)

### 2. Configure Environment Variables

Add your Sentry DSN to `.env`:

```bash
# Backend PHP monitoring
SENTRY_DSN=https://your-key@sentry.io/your-project-id
# or
MA_DEAL_SENTRY_DSN=https://your-key@sentry.io/your-project-id

# Frontend React monitoring (usually the same DSN)
VITE_SENTRY_DSN=https://your-key@sentry.io/your-project-id

# Application version for release tracking
VITE_APP_VERSION=1.0.0
```

### 3. Set Environment Type

Ensure your environment is properly configured:

```bash
# In .env
ENVIRONMENT=production  # or staging, or development
```

Or use WordPress constant in `wp-config.php`:

```php
define('WP_ENVIRONMENT_TYPE', 'production'); // or staging, or local
```

## Environment Behavior

### Development
- **Errors**: Not sent to Sentry (logged locally only)
- **Performance**: Not tracked
- **Console**: Full error details displayed

### Staging
- **Errors**: Sent to Sentry
- **Performance**: 100% of transactions tracked
- **Session Replay**: 10% of sessions

### Production
- **Errors**: Sent to Sentry
- **Performance**: 20% of transactions tracked (reduces overhead)
- **Session Replay**: 10% of sessions, 100% of error sessions

## What Gets Monitored

### Backend (PHP)

#### Error Types
- Fatal errors (E_ERROR, E_PARSE, E_CORE_ERROR)
- Warnings (E_WARNING, E_USER_WARNING)
- Exceptions (uncaught)
- Custom error captures

#### Performance Transactions
- **Transaction Creation**: `POST /wp-json/ma-deal/v1/transactions`
- **Template Application**: Template task instantiation
- **Task Generation**: Bulk task creation from templates

#### User Context
When errors occur, Sentry captures:
- WordPress user ID
- Username
- Email
- User roles
- WordPress version
- Plugin version
- PHP version
- Server software

### Frontend (React)

#### Error Types
- React component errors (via ErrorBoundary)
- Uncaught JavaScript exceptions
- Promise rejections
- Custom error captures

#### Performance Transactions
- Page navigation
- API requests
- Component rendering

#### Session Replay
Records user interactions before errors:
- Mouse movements (anonymized)
- Clicks
- Navigation
- Network requests
- Console logs

## Manual Error Capture

### Backend PHP

```php
use MADealRoom\Services\MonitoringService;

// Capture an exception
try {
    riskyOperation();
} catch (\Exception $e) {
    MonitoringService::capture_exception($e, [
        'context' => [
            'transaction_id' => $transaction_id,
            'operation' => 'create_transaction',
        ],
    ]);
    // Handle error...
}

// Capture a message
MonitoringService::capture_message(
    'Something unusual happened',
    'warning', // debug, info, warning, error, fatal
    ['user_action' => 'bulk_delete']
);

// Add breadcrumb
MonitoringService::add_breadcrumb(
    'User clicked submit button',
    'navigation',
    'info',
    ['form_id' => 'transaction_form']
);

// Start performance transaction
$span = MonitoringService::start_transaction('import.csv', 'task.import');
// ... do work ...
if ($span) {
    $span->finish();
}
```

### Frontend React

```typescript
import * as Sentry from '@sentry/react';

// Capture an exception
try {
  riskyOperation();
} catch (error) {
  Sentry.captureException(error, {
    tags: {
      component: 'TransactionForm',
    },
    contexts: {
      transaction: {
        id: transactionId,
        status: 'pending',
      },
    },
  });
}

// Capture a message
Sentry.captureMessage('User performed bulk action', {
  level: 'info',
  tags: {
    action: 'bulk_delete',
    count: selectedIds.length,
  },
});

// Add breadcrumb
Sentry.addBreadcrumb({
  category: 'navigation',
  message: 'User navigated to transaction detail',
  level: 'info',
  data: {
    transactionId: id,
  },
});

// Performance monitoring
const transaction = Sentry.startTransaction({
  op: 'api.request',
  name: 'POST /transactions',
});

try {
  const result = await api.post('/transactions', data);
  transaction.setStatus('ok');
} catch (error) {
  transaction.setStatus('internal_error');
  throw error;
} finally {
  transaction.finish();
}
```

## Setting Up Alerts

### Sentry Dashboard

1. Go to your Sentry project
2. Navigate to **Alerts**
3. Create a new alert rule

### Recommended Alerts

#### Critical Errors
- **Condition**: When any error occurs with level "fatal"
- **Action**: Send email to dev team + Slack notification
- **Frequency**: Immediately

#### High Error Rate
- **Condition**: When error count > 10 in 5 minutes
- **Action**: Send email to dev team
- **Frequency**: Once per hour

#### Performance Degradation
- **Condition**: When transaction duration > 5 seconds
- **Action**: Send email to dev team
- **Frequency**: Once per day

#### New Error Types
- **Condition**: When a new error fingerprint is detected
- **Action**: Send email to dev team
- **Frequency**: Immediately

## Viewing Errors

### Sentry Dashboard

Access your Sentry dashboard at `https://sentry.io/organizations/your-org/projects/`

### Key Features

#### Issues
View all errors grouped by:
- Error message
- Stack trace
- Affected users
- Frequency
- First/last seen

#### Performance
View transaction performance:
- Average duration
- P50, P75, P95, P99 percentiles
- Throughput
- Slow endpoints

#### Releases
Track errors by plugin version:
- Error count per release
- New errors introduced
- Resolved errors
- Adoption rate

#### Session Replay
Watch recordings of user sessions:
- See exact user actions before error
- Understand context
- Reproduce issues

## Filtering Errors

### Backend

The `MonitoringService::before_send_callback()` filters out:
- Known WordPress notices (undefined index, etc.)
- Development environment errors
- Low-priority warnings

To customize, edit `/ma-deal-room/src/Services/MonitoringService.php:358`:

```php
public static function before_send_callback(\Sentry\Event $event, ?\Sentry\EventHint $hint = null): ?\Sentry\Event {
    $message = $event->getMessage();

    // Add your custom filters
    $ignored_messages = [
        'Undefined index',
        'Undefined variable',
        // Add more patterns to ignore
    ];

    foreach ($ignored_messages as $ignored) {
        if ($message && strpos($message, $ignored) !== false) {
            return null; // Don't send this event
        }
    }

    return $event;
}
```

### Frontend

Edit `/ma-deal-room/assets/admin/src/main.tsx` to add filters:

```typescript
Sentry.init({
  dsn: sentryDsn,
  beforeSend(event, hint) {
    // Filter out specific errors
    if (event.message && event.message.includes('ResizeObserver')) {
      return null; // Don't send this event
    }
    return event;
  },
});
```

## Testing

### Test Error Capture (Development)

Since errors aren't sent in development by default, you can temporarily enable them:

**Backend**: Comment out the development check in `MonitoringService::init()`:

```php
// if (empty($dsn) || self::is_development()) {
if (empty($dsn)) {
    return;
}
```

**Frontend**: Remove the development check in `main.tsx`:

```typescript
// if (sentryDsn && environment !== 'development') {
if (sentryDsn) {
  Sentry.init({
    // ...
  });
}
```

### Trigger Test Errors

**Backend**:
```bash
# Via WP-CLI
wp eval "throw new Exception('Test Sentry integration');"
```

**Frontend**:
In your browser console:
```javascript
throw new Error('Test Sentry integration');
```

## Performance Optimization

### Sample Rates

Adjust sample rates in:

**Backend** (`MonitoringService::get_traces_sample_rate()`):
```php
$rates = [
    'development' => 0.0,  // Don't send traces in dev
    'staging' => 1.0,      // 100% in staging
    'production' => 0.2,   // 20% in production
];
```

**Frontend** (`main.tsx`):
```typescript
tracesSampleRate: environment === 'production' ? 0.2 : 1.0,
```

Lower sample rates reduce:
- Sentry quota usage
- Network overhead
- Performance impact

But provide less data for analysis.

## Source Maps

### PHP (Not Applicable)
PHP doesn't use source maps. Stack traces show actual file locations.

### React

To enable source maps for better error debugging:

1. **Upload source maps** after build:
```bash
npm install --save-dev @sentry/vite-plugin

# vite.config.ts
import { sentryVitePlugin } from '@sentry/vite-plugin';

export default {
  plugins: [
    sentryVitePlugin({
      org: "your-org",
      project: "ma-deal-room",
      authToken: process.env.SENTRY_AUTH_TOKEN,
    }),
  ],
};
```

2. **Build with source maps**:
```bash
SENTRY_AUTH_TOKEN=your-token npm run build
```

Source maps allow Sentry to show original code (TypeScript) instead of compiled/minified code.

## Security Best Practices

### ✅ Do
- Use environment variables for DSN (never commit)
- Filter sensitive data from errors
- Disable PII (Personally Identifiable Information) capture
- Use separate DSNs for dev/staging/prod if needed
- Regularly review captured errors
- Set up alerts for critical issues
- Test error capture in staging

### ❌ Don't
- Commit `.env` with real Sentry DSN
- Send errors containing passwords, API keys, tokens
- Send errors containing user payment information
- Enable error tracking in development (increases noise)
- Ignore error reports
- Set sample rates too low (miss important issues)

## Troubleshooting

### Errors Not Appearing in Sentry

**Check DSN**:
```bash
# Should show your DSN
grep SENTRY_DSN .env
```

**Check environment**:
```bash
# Should not be "development"
grep ENVIRONMENT .env
```

**Check PHP error log**:
```bash
tail -f wp-content/debug.log | grep Sentry
```

**Test manually**:
```php
// In a test script
require_once 'ma-deal-room/vendor/autoload.php';

\MADealRoom\Services\MonitoringService::init();
\MADealRoom\Services\MonitoringService::capture_message('Test', 'info');
\MADealRoom\Services\MonitoringService::flush();
```

### Frontend Errors Not Sent

**Check browser console**:
Look for Sentry initialization messages.

**Check environment variables**:
```javascript
// In browser console
console.log(import.meta.env.VITE_SENTRY_DSN);
console.log(import.meta.env.MODE);
```

**Check network tab**:
Look for POST requests to `sentry.io`.

### Performance Data Not Collected

**Check sample rates**:
Ensure traces sample rate > 0 for your environment.

**Check transactions**:
Ensure critical endpoints have performance monitoring code.

## Cost Management

Sentry pricing is based on:
- **Events**: Errors, messages
- **Transactions**: Performance traces
- **Session Replay**: Recordings

### Free Tier
- 5,000 errors/month
- 10,000 performance events/month
- 50 session replays/month

### Optimization Tips

1. **Lower sample rates** in production (20% instead of 100%)
2. **Filter noisy errors** (undefined index, etc.)
3. **Limit breadcrumbs** (max 50 per error)
4. **Disable session replay** if not needed
5. **Use separate projects** for dev/staging/prod

## Support

For Sentry-related issues:
1. Check the [Sentry documentation](https://docs.sentry.io/)
2. Review captured events in Sentry dashboard
3. Check local error logs (`wp-content/debug.log`)
4. Contact development team: dev@madealroom.com

## Version History

- **v1.0.0** (2025-11-01): Initial monitoring system implementation
  - Sentry PHP SDK v4.17.1
  - Sentry React SDK with Error Boundary
  - Performance monitoring for critical endpoints
  - Automatic error capture and reporting
  - Environment-aware behavior
  - User context tracking
