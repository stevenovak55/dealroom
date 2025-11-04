<?php
/**
 * Test Vendor Email Sending
 *
 * This script tests the vendor invitation email functionality
 */

// Load WordPress
require_once __DIR__ . '/wp-load.php';

// Get the most recent vendor request
global $wpdb;
$vendor_request = $wpdb->get_row(
    "SELECT * FROM {$wpdb->prefix}ma_deal_vendor_requests
     ORDER BY id DESC
     LIMIT 1"
);

if (!$vendor_request) {
    echo "❌ No vendor requests found in database\n";
    exit(1);
}

echo "📧 Testing Email Sending for Vendor Request\n";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
echo "Vendor Request ID: {$vendor_request->id}\n";
echo "Vendor Email: {$vendor_request->vendor_email}\n";
echo "Vendor Type: {$vendor_request->vendor_type}\n";
echo "Status: {$vendor_request->status}\n";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n\n";

// Create VendorService instance
use MADealRoom\Services\VendorService;
use MADealRoom\Repositories\VendorRequestRepository;

$vendor_request_repository = new VendorRequestRepository();
$vendor_service = new VendorService($vendor_request_repository);

// Test email sending
echo "🔄 Sending invitation email...\n";
$result = $vendor_service->sendVendorInvitation($vendor_request);

if ($result) {
    echo "✅ Email sent successfully!\n";
    echo "\nCheck your email at: {$vendor_request->vendor_email}\n";
    echo "\nEmail Details:\n";
    echo "  - Subject: [MA Deal Room] Vendor Portal Access\n";
    echo "  - From: MA Deal Room <noreply@localhost>\n";
    echo "  - Portal URL: " . home_url('/vendor/' . $vendor_request->token . '/') . "\n";
} else {
    echo "❌ Failed to send email\n";
    echo "\nPlease check:\n";
    echo "  1. WordPress debug log: wp-content/debug.log\n";
    echo "  2. PHP mail configuration\n";
    echo "  3. SMTP settings (if configured)\n";
}

echo "\n━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
echo "Test completed.\n";
