<?php
/**
 * Debug Email Template Rendering
 */

require_once __DIR__ . '/wp-load.php';

use MADealRoom\Services\EmailTemplateService;

echo "========================================\n";
echo "Debug Email Template Rendering\n";
echo "========================================\n\n";

$template_service = new EmailTemplateService();

// Test rendering a simple template
echo "Testing template rendering...\n\n";

try {
    echo "1. Checking if template exists: ";
    $exists = $template_service->templateExists('welcome');
    echo $exists ? "YES\n" : "NO\n";

    if (!$exists) {
        echo "ERROR: Template 'welcome' does not exist!\n";
        exit(1);
    }

    echo "\n2. Rendering welcome template...\n";
    $html = $template_service->render('welcome', [
        'user_name' => 'Test User',
        'user_email' => 'test@example.com',
        'login_url' => 'https://app.madealroom.com/login',
        'unsubscribe_url' => 'https://app.madealroom.com/unsubscribe',
    ]);

    echo "✓ Template rendered successfully\n";
    echo "HTML length: " . strlen($html) . " bytes\n\n";

    echo "3. Sending test email with wp_mail...\n";

    // Enable wp_mail debugging
    $phpmailer_error = '';
    add_action('wp_mail_failed', function($error) use (&$phpmailer_error) {
        $phpmailer_error = $error->get_error_message();
    });

    $result = wp_mail(
        'test@example.com',
        'Welcome to MA Deal Room',
        $html,
        ['Content-Type: text/html; charset=UTF-8']
    );

    echo "wp_mail result: " . ($result ? 'SUCCESS' : 'FAILED') . "\n";

    if (!$result) {
        echo "Error: " . ($phpmailer_error ? $phpmailer_error : 'Unknown error') . "\n";

        // Check PHPMailer logs
        global $phpmailer;
        if (isset($phpmailer) && is_object($phpmailer)) {
            echo "PHPMailer ErrorInfo: " . $phpmailer->ErrorInfo . "\n";
        }
    } else {
        echo "✓ Email sent to MailHog!\n";
        echo "Check http://localhost:8025\n";
    }

} catch (\Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
    echo "Trace: " . $e->getTraceAsString() . "\n";
}

echo "\n========================================\n";
