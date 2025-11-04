#!/usr/bin/env php
<?php
/**
 * Manual verification of password reset components
 */

require_once '/var/www/html/wp-load.php';

echo "=== MANUAL PASSWORD RESET VERIFICATION ===\n\n";

// 1. Check services exist
echo "1. Checking Services:\n";
$password_reset_service_exists = class_exists('\MADealRoom\Services\PasswordResetService');
$auth_service_exists = class_exists('\MADealRoom\Services\AuthService');
echo "   PasswordResetService: " . ($password_reset_service_exists ? "✓" : "✗") . "\n";
echo "   AuthService: " . ($auth_service_exists ? "✓" : "✗") . "\n\n";

// 2. Check repositories exist
echo "2. Checking Repositories:\n";
$pr_repo_exists = class_exists('\MADealRoom\Repositories\PasswordResetRepository');
$cu_repo_exists = class_exists('\MADealRoom\Repositories\CustomUserRepository');
echo "   PasswordResetRepository: " . ($pr_repo_exists ? "✓" : "✗") . "\n";
echo "   CustomUserRepository: " . ($cu_repo_exists ? "✓" : "✗") . "\n\n";

// 3. Direct database test
echo "3. Direct Database Test:\n";
global $wpdb;
$test_email = 'manual-test@example.com';

// Clean up
$wpdb->delete($wpdb->prefix . 'ma_deal_custom_users', ['email' => $test_email]);
$wpdb->delete($wpdb->prefix . 'ma_deal_password_resets', ['email' => $test_email]);

// Create user
$user_created = $wpdb->insert(
    $wpdb->prefix . 'ma_deal_custom_users',
    [
        'email' => $test_email,
        'password_hash' => password_hash('TestPass123!', PASSWORD_BCRYPT),
        'status' => 'active',
        'email_verified' => 1,
    ]
);

echo "   User created: " . ($user_created ? "✓" : "✗") . "\n";
$user_id = $wpdb->insert_id;

// Create token
$token = bin2hex(random_bytes(32));
$token_hash = hash('sha256', $token);

$token_created = $wpdb->insert(
    $wpdb->prefix . 'ma_deal_password_resets',
    [
        'email' => $test_email,
        'token_hash' => $token_hash,
        'user_type' => 'custom',
        'ip_address' => '127.0.0.1',
        'expires_at' => gmdate('Y-m-d H:i:s', time() + 3600),
    ]
);

echo "   Token created: " . ($token_created ? "✓" : "✗") . "\n";

// Verify token in database
$token_exists = $wpdb->get_var($wpdb->prepare(
    "SELECT COUNT(*) FROM {$wpdb->prefix}ma_deal_password_resets WHERE email = %s",
    $test_email
));

echo "   Token in DB: " . ($token_exists ? "✓" : "✗") . "\n\n";

// 4. Test service instantiation
echo "4. Testing Service Instantiation:\n";
try {
    $pr_service = new \MADealRoom\Services\PasswordResetService();
    echo "   PasswordResetService instantiated: ✓\n";

    // Try to use the service
    $validation = $pr_service->validate_reset_token($token);
    echo "   Token validation works: " . (is_wp_error($validation) ? "✗ " . $validation->get_error_message() : "✓") . "\n";

} catch (Exception $e) {
    echo "   ✗ Error: " . $e->getMessage() . "\n";
}

echo "\n";

// 5. Check for any PHP errors in the error log
echo "5. Checking for PHP Errors:\n";
$error_log = ini_get('error_log') ?: '/var/www/html/wp-content/debug.log';
if (file_exists($error_log)) {
    $recent_errors = shell_exec("tail -20 {$error_log} | grep -i 'password\\|reset' || echo 'No password-related errors'");
    echo "   Recent errors:\n";
    echo "   " . str_replace("\n", "\n   ", trim($recent_errors)) . "\n";
} else {
    echo "   Error log not found at: {$error_log}\n";
}

echo "\n";

// Cleanup
$wpdb->delete($wpdb->prefix . 'ma_deal_custom_users', ['email' => $test_email]);
$wpdb->delete($wpdb->prefix . 'ma_deal_password_resets', ['email' => $test_email]);

echo "=== VERIFICATION COMPLETE ===\n";
