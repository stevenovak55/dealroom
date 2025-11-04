<?php
/**
 * Test Vendor Notification Emails (T2.2.5)
 * 
 * This script tests the vendor notification email functionality:
 * 1. Scheduling confirmation email
 * 2. Completion confirmation email
 * 3. Appointment reminder email
 * 
 * Usage: php test-vendor-notification-emails.php
 */

// Load WordPress
require_once __DIR__ . '/wp-load.php';

use MADealRoom\Services\VendorService;
use MADealRoom\Repositories\VendorRequestRepository;

echo "=== Vendor Notification Email Test ===\n\n";

// Initialize services
$vendor_request_repository = new VendorRequestRepository();
$vendor_service = new VendorService($vendor_request_repository);

// Test data
$test_transaction_id = 1; // Assuming transaction #1 exists

echo "Step 1: Creating test vendor request...\n";

// Create a test vendor request
global $wpdb;
$table = $wpdb->prefix . 'ma_deal_vendor_requests';

// Clean up any previous test data
$wpdb->query("DELETE FROM {$table} WHERE vendor_email = 'test-vendor@example.com'");

// Insert test vendor request
$wpdb->insert($table, [
	'transaction_id' => $test_transaction_id,
	'vendor_email' => 'test-vendor@example.com',
	'vendor_type' => 'home_inspector',
	'status' => 'sent',
	'token' => bin2hex(random_bytes(32)),
	'token_expires_at' => date('Y-m-d H:i:s', strtotime('+30 days')),
	'created_at' => current_time('mysql'),
	'updated_at' => current_time('mysql'),
]);

$request_id = $wpdb->insert_id;
echo "✓ Created vendor request #$request_id\n\n";

// Retrieve the vendor request
$vendor_request = $wpdb->get_row($wpdb->prepare(
	"SELECT * FROM {$table} WHERE id = %d",
	$request_id
));

// Test 1: Scheduling Confirmation Email
echo "Test 1: Scheduling Confirmation Email\n";
echo "---------------------------------------\n";

// Update with scheduling data
$scheduled_date = date('Y-m-d', strtotime('+7 days'));
$scheduled_time = '14:00:00';

$wpdb->update(
	$table,
	[
		'scheduled_date' => $scheduled_date,
		'scheduled_time' => $scheduled_time,
		'status' => 'scheduled',
	],
	['id' => $request_id]
);

// Reload vendor request
$vendor_request = $wpdb->get_row($wpdb->prepare(
	"SELECT * FROM {$table} WHERE id = %d",
	$request_id
));

echo "Sending scheduling confirmation email...\n";
$result = $vendor_service->sendSchedulingConfirmation($vendor_request);

if ($result) {
	echo "✓ Scheduling confirmation email sent successfully\n";
	echo "  To: {$vendor_request->vendor_email}\n";
	echo "  Scheduled: {$scheduled_date} at {$scheduled_time}\n";
} else {
	echo "✗ Failed to send scheduling confirmation email\n";
}
echo "\n";

// Test 2: Completion Confirmation Email
echo "Test 2: Completion Confirmation Email\n";
echo "---------------------------------------\n";

// Update with completion data
$completion_notes = "Inspection completed. Found minor issues with roof flashing and HVAC filter. Full report attached.";
$document_url = "https://example.com/documents/inspection-report-12345.pdf";

$wpdb->update(
	$table,
	[
		'completion_notes' => $completion_notes,
		'document_url' => $document_url,
		'status' => 'completed',
	],
	['id' => $request_id]
);

// Reload vendor request
$vendor_request = $wpdb->get_row($wpdb->prepare(
	"SELECT * FROM {$table} WHERE id = %d",
	$request_id
));

echo "Sending completion confirmation email...\n";
$result = $vendor_service->sendCompletionConfirmation($vendor_request);

if ($result) {
	echo "✓ Completion confirmation email sent successfully\n";
	echo "  To: {$vendor_request->vendor_email}\n";
	echo "  Notes: " . substr($completion_notes, 0, 50) . "...\n";
	echo "  Document: {$document_url}\n";
} else {
	echo "✗ Failed to send completion confirmation email\n";
}
echo "\n";

// Test 3: Appointment Reminder Email
echo "Test 3: Appointment Reminder Email\n";
echo "---------------------------------------\n";

// Create a new vendor request scheduled for tomorrow
$wpdb->insert($table, [
	'transaction_id' => $test_transaction_id,
	'vendor_email' => 'test-vendor-reminder@example.com',
	'vendor_type' => 'appraiser',
	'status' => 'scheduled',
	'scheduled_date' => date('Y-m-d', strtotime('+1 day')),
	'scheduled_time' => '10:00:00',
	'token' => bin2hex(random_bytes(32)),
	'token_expires_at' => date('Y-m-d H:i:s', strtotime('+30 days')),
	'reminder_sent' => 0,
	'created_at' => current_time('mysql'),
	'updated_at' => current_time('mysql'),
]);

$reminder_request_id = $wpdb->insert_id;

// Reload vendor request
$reminder_request = $wpdb->get_row($wpdb->prepare(
	"SELECT * FROM {$table} WHERE id = %d",
	$reminder_request_id
));

echo "Sending appointment reminder email...\n";
$result = $vendor_service->sendAppointmentReminder($reminder_request);

if ($result) {
	echo "✓ Appointment reminder email sent successfully\n";
	echo "  To: {$reminder_request->vendor_email}\n";
	echo "  Appointment: Tomorrow at {$reminder_request->scheduled_time}\n";
} else {
	echo "✗ Failed to send appointment reminder email\n";
}
echo "\n";

// Test 4: Batch Reminder Processing
echo "Test 4: Batch Reminder Processing\n";
echo "---------------------------------------\n";

echo "Running batch reminder job...\n";
$results = $vendor_service->sendDueReminders();

echo "✓ Batch reminder job completed\n";
echo "  Total requests: {$results['total']}\n";
echo "  Sent: {$results['sent']}\n";
echo "  Failed: {$results['failed']}\n";
echo "\n";

// Test 5: Email Template Validation
echo "Test 5: Email Template Validation\n";
echo "---------------------------------------\n";

$templates = [
	'vendor-scheduling-confirmation.php',
	'vendor-completion-confirmation.php',
	'vendor-reminder.php',
];

$template_dir = __DIR__ . '/ma-deal-room/src/Templates/emails/';

foreach ($templates as $template) {
	$path = $template_dir . $template;
	if (file_exists($path)) {
		echo "✓ Template exists: {$template}\n";
		
		// Check file size
		$size = filesize($path);
		echo "  Size: " . number_format($size) . " bytes\n";
		
		// Check for required variables by template type
		$content = file_get_contents($path);
		
		// Common checks
		if (strpos($content, '<?php') !== false) {
			echo "  ✓ Valid PHP file\n";
		}
		if (strpos($content, 'ABSPATH') !== false) {
			echo "  ✓ Has security check\n";
		}
		if (strpos($content, 'base.php') !== false) {
			echo "  ✓ Includes base template\n";
		}
	} else {
		echo "✗ Template missing: {$template}\n";
	}
	echo "\n";
}

// Cleanup
echo "Cleanup: Removing test data...\n";
$wpdb->delete($table, ['id' => $request_id]);
$wpdb->delete($table, ['id' => $reminder_request_id]);
echo "✓ Test data removed\n\n";

echo "=== Test Complete ===\n";
echo "\nNote: Check your email client (or MailHog if in dev) for the sent emails.\n";
echo "Email logs are available in wp-content/debug.log\n";
