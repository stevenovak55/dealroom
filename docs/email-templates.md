# Email Templates Documentation

## Overview

The MA Deal Room plugin includes a comprehensive set of professional HTML email templates for transactional emails, notifications, and user communications. All templates are mobile-responsive, email-client compatible, and use inline CSS for maximum compatibility.

## Architecture

### Components

1. **Base Template** (`base.php`)
   - Provides the overall email structure (header, footer, wrapper)
   - Includes branding, gradients, and consistent styling
   - Supports CTAs, preheaders, and unsubscribe links

2. **EmailTemplateService** (`src/Services/EmailTemplateService.php`)
   - Handles template rendering with variable replacement
   - Provides helper methods for formatting
   - Supports template caching and testing

3. **EmailService** (`src/Services/EmailService.php`)
   - Main service for sending emails
   - Integrates with EmailTemplateService
   - Handles email delivery with retry logic

## Available Templates

### 1. Welcome Email (`welcome.php`)

Sent when a new user joins the platform.

**Variables:**
- `$user_name` (string) - User's full name
- `$user_email` (string) - User's email address
- `$login_url` (string, optional) - URL to login page

**Usage:**
```php
$email_service = new EmailService();
$email_service->send_welcome_email(
    'user@example.com',
    'John Doe',
    'https://example.com/login'
);
```

---

### 2. Email Verification (`email-verification.php`)

Sent to verify a user's email address during registration.

**Variables:**
- `$user_name` (string) - User's full name
- `$user_email` (string) - User's email address
- `$verification_url` (string, required) - Email verification link
- `$expiry_hours` (int, default: 24) - Hours until link expires

**Usage:**
```php
$email_service->send_email_verification(
    'user@example.com',
    'John Doe',
    'https://example.com/verify?token=abc123',
    24 // expires in 24 hours
);
```

---

### 3. Password Reset (`password-reset.php`)

Sent when a user requests a password reset.

**Variables:**
- `$user_name` (string) - User's full name
- `$user_email` (string) - User's email address
- `$reset_url` (string, required) - Password reset link
- `$expiry_hours` (int, default: 1) - Hours until link expires
- `$ip_address` (string, optional) - IP address of requester

**Usage:**
```php
$email_service->send_password_reset(
    'user@example.com',
    'John Doe',
    'https://example.com/reset?token=xyz789',
    1 // expires in 1 hour
);
```

---

### 4. Task Assigned (`task-assigned.php`)

Sent when a task is assigned to a user.

**Variables:**
- `$task` (Task object) - Task details (title, description, due_at, status)
- `$transaction` (Transaction object) - Related transaction (id, property_address)
- `$assignee` (Party object) - Assignee details (contact_name, email)
- `$assigner_name` (string, optional) - Name of person who assigned the task

**Usage:**
```php
$email_service->sendTaskAssignedNotification($task, $transaction, $assignee);
```

---

### 5. Task Reminder (`task-reminder.php`)

Sent to remind users about upcoming task deadlines.

**Variables:**
- `$task` (Task object) - Task details
- `$transaction` (Transaction object) - Related transaction
- `$assignee` (Party object) - Assignee details
- `$days_until_due` (int) - Days until task is due (0 = today, 1 = tomorrow)

**Usage:**
```php
$template_service = new EmailTemplateService();
$html = $template_service->render('task-reminder', [
    'task' => $task,
    'transaction' => $transaction,
    'assignee' => $assignee,
    'days_until_due' => 1
]);
```

---

### 6. Task Status Updated (`task-status-updated.php`)

Sent when a task's status changes.

**Variables:**
- `$task` (Task object) - Task details
- `$transaction` (Transaction object) - Related transaction
- `$old_status` (string) - Previous status
- `$new_status` (string) - New status (pending, in_progress, completed, blocked)
- `$assignee` (Party object) - Assignee details
- `$updated_by` (string, optional) - Name of person who updated the status

**Usage:**
```php
$email_service->sendTaskStatusUpdatedNotification(
    $task,
    $transaction,
    'pending',
    'in_progress',
    $assignee
);
```

---

### 7. Transaction Created (`transaction-created.php`)

Sent when a new transaction is created.

**Variables:**
- `$transaction` (Transaction object) - Transaction details (property_address, property_type, closing_date, status)
- `$recipient_name` (string) - Recipient's name
- `$recipient_role` (string, optional) - Recipient's role in the transaction

**Usage:**
```php
$template_service->render('transaction-created', [
    'transaction' => $transaction,
    'recipient_name' => 'Jane Smith',
    'recipient_role' => 'buyer_agent'
]);
```

---

### 8. Document Uploaded (`document-uploaded.php`)

Sent when a new document is uploaded to a transaction.

**Variables:**
- `$document` (Document object) - Document details (title, file_name, description, document_type)
- `$transaction` (Transaction object) - Related transaction
- `$uploaded_by` (string, optional) - Name of uploader
- `$recipient_name` (string) - Recipient's name

**Usage:**
```php
$email_service->sendDocumentUploadedNotification($document, $transaction, [$email]);
```

---

### 9. Party Added (`party-added.php`)

Sent when a party is added to a transaction.

**Variables:**
- `$party` (Party object) - Party details (contact_name, email, role)
- `$transaction` (Transaction object) - Related transaction
- `$added_by` (string, optional) - Name of person who added the party
- `$welcome_message` (string, optional) - Custom welcome message

**Usage:**
```php
$email_service->sendPartyAddedNotification($party, $transaction);
```

---

### 10. Vendor Request (`vendor-request.php`)

Sent to request vendor services for a transaction.

**Variables:**
- `$vendor_name` (string) - Vendor's name
- `$vendor_email` (string) - Vendor's email
- `$transaction` (Transaction object) - Related transaction
- `$service_type` (string) - Type of service requested
- `$requester_name` (string, optional) - Name of requester
- `$requester_email` (string, optional) - Email of requester
- `$requester_phone` (string, optional) - Phone of requester
- `$message` (string, optional) - Additional details

**Usage:**
```php
$template_service->render('vendor-request', [
    'vendor_name' => 'ABC Inspection Co.',
    'vendor_email' => 'vendor@example.com',
    'transaction' => $transaction,
    'service_type' => 'Home Inspection',
    'requester_name' => 'Agent Name',
    'message' => 'Please contact me to schedule inspection.'
]);
```

---

### 11. Daily Digest (`daily-digest.php`)

Sent daily with a summary of pending tasks and upcoming deadlines.

**Variables:**
- `$user_name` (string) - User's name
- `$pending_tasks` (array) - Array of pending tasks
  - Each task: `['title' => string, 'transaction' => string, 'due_at' => string]`
- `$upcoming_deadlines` (array) - Array of upcoming deadlines (same structure)
- `$recent_updates` (array) - Array of recent updates
  - Each update: `['title' => string, 'description' => string]`

**Usage:**
```php
$template_service->render('daily-digest', [
    'user_name' => 'John Doe',
    'pending_tasks' => [
        ['title' => 'Review contract', 'transaction' => '123 Main St', 'due_at' => '2025-11-05'],
        ['title' => 'Upload W9', 'transaction' => '456 Oak Ave', 'due_at' => '2025-11-06']
    ],
    'upcoming_deadlines' => [...],
    'recent_updates' => [...]
]);
```

---

## Customization

### Creating a Custom Template

1. Create a new PHP file in `ma-deal-room/src/Templates/emails/`
2. Use the base template structure:

```php
<?php
/**
 * Custom Template
 * Variables: $variable1, $variable2
 */

$subject = 'Email Subject';
$preheader = 'Preview text';
$cta_url = 'https://example.com';
$cta_text = 'Button Text';

ob_start();
?>

<table border="0" cellpadding="0" cellspacing="0" width="100%">
    <!-- Your content here -->
</table>

<?php
$content = ob_get_clean();
include __DIR__ . '/base.php';
?>
```

3. Render with EmailTemplateService:

```php
$template_service = new EmailTemplateService();
$html = $template_service->render('custom-template', [
    'variable1' => 'value1',
    'variable2' => 'value2'
]);
```

### Modifying the Base Template

Edit `ma-deal-room/src/Templates/emails/base.php` to change:
- Header colors and branding
- Footer content
- Overall layout and styling

**Note:** Base template changes affect all emails.

### Email-Safe CSS Guidelines

1. **Use inline styles** - All CSS should be inline on elements
2. **Avoid CSS classes** - Email clients have poor class support
3. **Use tables for layout** - `<div>` support is inconsistent
4. **Stick to web-safe fonts** - Arial, Georgia, Times New Roman
5. **Use hex colors** - Avoid rgba(), CSS variables
6. **Avoid background images** - May be blocked by email clients

## Testing Templates

### Test in Development

```php
$template_service = new EmailTemplateService();

// Test rendering
$result = $template_service->test('welcome', [
    'user_name' => 'Test User',
    'user_email' => 'test@example.com',
    'login_url' => 'https://example.com/login'
]);

if ($result['success']) {
    echo "✓ Template rendered successfully\n";
    echo "  Render time: {$result['render_time_ms']}ms\n";
    echo "  Size: {$result['html_size_kb']}KB\n";
} else {
    echo "✗ Error: {$result['error']}\n";
}
```

### Test Email Delivery

```php
// Send test email
$email_service = new EmailService();
$success = $email_service->send_welcome_email(
    'your-email@example.com',
    'Test User',
    admin_url('admin.php?page=ma-deal-room')
);

if ($success) {
    echo "✓ Test email sent\n";
} else {
    echo "✗ Email failed to send\n";
}
```

### Preview in Email Clients

Test your templates in multiple email clients:
- Gmail (Web, iOS, Android)
- Outlook (Desktop, Web)
- Apple Mail (macOS, iOS)
- Yahoo Mail
- Mobile email clients

Tools for testing:
- [Litmus](https://litmus.com/)
- [Email on Acid](https://www.emailonacid.com/)
- [Mailtrap](https://mailtrap.io/)

## Email Template Variables Reference

### Common Object Properties

**Transaction Object:**
- `$transaction->id` - Transaction ID
- `$transaction->property_address` - Property address
- `$transaction->property_city` - City
- `$transaction->property_state` - State
- `$transaction->property_zip` - ZIP code
- `$transaction->property_type` - Property type (sfh, condo, multifamily, etc.)
- `$transaction->status` - Transaction status
- `$transaction->closing_date` - Expected closing date

**Task Object:**
- `$task->id` - Task ID
- `$task->title` - Task title
- `$task->description` - Task description
- `$task->status` - Task status (pending, in_progress, completed, blocked)
- `$task->due_at` - Due date/time
- `$task->priority` - Priority level

**Party Object:**
- `$party->id` - Party ID
- `$party->contact_name` - Full name
- `$party->email` - Email address
- `$party->phone` - Phone number
- `$party->role` - Role in transaction (buyer, seller, buyer_agent, etc.)

**Document Object:**
- `$document->id` - Document ID
- `$document->title` - Document title
- `$document->file_name` - Original filename
- `$document->description` - Document description
- `$document->document_type` - Document type
- `$document->file_size` - File size (formatted)
- `$document->uploaded_at` - Upload timestamp

## Helper Methods

EmailTemplateService provides helper methods for formatting:

```php
// Escape HTML
EmailTemplateService::escape($text);

// Format currency
EmailTemplateService::formatCurrency(125000.00); // Returns "$125,000.00"

// Format dates
EmailTemplateService::formatDate('2025-11-01'); // Returns "November 1, 2025"
EmailTemplateService::formatDate($date, 'M j, Y'); // Custom format
```

## Troubleshooting

### Template Not Rendering

**Error:** `Email template "xyz" not found`

**Solution:**
1. Verify template file exists in `ma-deal-room/src/Templates/emails/`
2. Check filename matches template name (no `.php` in name parameter)
3. Ensure file permissions are correct

### Variables Not Replacing

**Issue:** Template shows `<?php echo $variable; ?>` instead of value

**Solution:**
1. Check you're using `esc_html()` for all output
2. Verify variables are being passed to render method
3. Ensure PHP tags are properly opened and closed

### Styling Issues in Email Clients

**Issue:** Email looks broken in Outlook/Gmail

**Solution:**
1. Use inline styles only
2. Use `<table>` for layout, not `<div>`
3. Test with Email on Acid or Litmus
4. Avoid CSS3 properties (gradients, animations)

## Best Practices

1. **Always escape output** - Use `esc_html()` for all variables
2. **Provide fallbacks** - Check if variables exist before using
3. **Keep it simple** - Complex layouts may break in email clients
4. **Test thoroughly** - Preview in multiple email clients
5. **Mobile-first** - Most emails are read on mobile devices
6. **Include alt text** - For all images (if used)
7. **Preheader text** - Always set for better inbox preview
8. **Unsubscribe link** - Include in all marketing/digest emails

## File Structure

```
ma-deal-room/
├── src/
│   ├── Services/
│   │   ├── EmailService.php           # Main email service
│   │   └── EmailTemplateService.php   # Template rendering
│   └── Templates/
│       └── emails/
│           ├── base.php              # Base layout
│           ├── welcome.php
│           ├── email-verification.php
│           ├── password-reset.php
│           ├── task-assigned.php
│           ├── task-reminder.php
│           ├── task-status-updated.php
│           ├── transaction-created.php
│           ├── document-uploaded.php
│           ├── party-added.php
│           ├── vendor-request.php
│           └── daily-digest.php
└── docs/
    └── email-templates.md            # This file
```

## Future Enhancements

Planned improvements:
- [ ] SendGrid template integration
- [ ] A/B testing support
- [ ] Email analytics tracking
- [ ] Template versioning
- [ ] Multi-language support
- [ ] Dark mode optimization
- [ ] Automatic CSS inlining (Emogrifier integration)

## Support

For questions or issues with email templates:
1. Check this documentation
2. Review template source code
3. Test with EmailTemplateService::test()
4. Check error logs for rendering issues

---

**Last Updated:** 2025-11-01
**Version:** 1.0.0
