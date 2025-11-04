<?php
/**
 * Test MLS PIN Property Import
 *
 * Tests importing a property from MLS PIN into the deal room
 */

// Load WordPress
require_once('/var/www/html/wp-load.php');

// Load required classes
require_once('/var/www/html/wp-content/plugins/ma-deal-room/src/Services/Integration/MLS/MLSClientFactory.php');
require_once('/var/www/html/wp-content/plugins/ma-deal-room/src/Services/Integration/MLS/MLSImportService.php');

use MA_Deal_Room\Services\Integration\MLS\MLSImportService;
use MA_Deal_Room\Services\Integration\MLS\MLSClientFactory;

echo "=== MLS PIN Property Import Test ===\n\n";

// Get services from container
global $ma_deal_room_container;

$import_service = new MLSImportService(
    new MLSClientFactory(),
    $ma_deal_room_container->get('transaction_repository'),
    $ma_deal_room_container->get('job_queue_service')
);

// MLS number from our earlier search (Boston commercial property)
$mls_number = '73350868'; // 400 Commonwealth, Boston - $2,399,000
$account_id = 1;
$user_id = 1; // Admin user

echo "Importing property from MLS PIN...\n";
echo "MLS Number: {$mls_number}\n";
echo str_repeat("-", 80) . "\n\n";

// Check if already imported
echo "Step 1: Checking if property is already imported...\n";
$already_imported = $import_service->isListingImported($account_id, $mls_number);

if ($already_imported) {
    echo "⚠ Property is already imported\n";
    echo "Proceeding anyway to show the import process...\n\n";
} else {
    echo "✓ Property is not imported yet\n\n";
}

// Import the property
echo "Step 2: Importing property...\n";
$result = $import_service->importProperty(
    $account_id,
    $mls_number,
    $user_id,
    true, // Queue photos for background processing
    1     // config_id
);

if ($result['success']) {
    echo "✓ Import successful!\n\n";

    echo "Import Details:\n";
    echo "  Transaction ID: {$result['transaction_id']}\n";
    echo "  MLS Number: {$mls_number}\n";
    echo "  Message: {$result['message']}\n";

    if (isset($result['property'])) {
        $property = $result['property'];
        echo "\nProperty Details:\n";
        echo "  Title: {$property['title']}\n";
        echo "  Address: {$property['property_address']}\n";
        echo "  City: {$property['city']}, {$property['state']} {$property['zip_code']}\n";
        echo "  Price: $" . number_format($property['purchase_price']) . "\n";
        echo "  Type: {$property['transaction_type']}\n";
        echo "  MLS Status: {$property['mls_status']}\n";

        if (isset($property['bedrooms'])) {
            echo "  Bedrooms: {$property['bedrooms']}\n";
        }
        if (isset($property['bathrooms'])) {
            echo "  Bathrooms: {$property['bathrooms']}\n";
        }
        if (isset($property['square_feet']) && $property['square_feet'] > 0) {
            echo "  Square Feet: " . number_format($property['square_feet']) . "\n";
        }
    }

    if (isset($result['photos_queued']) && $result['photos_queued']) {
        echo "\n  Photos: Queued for background download\n";
    }

    // Verify in database
    echo "\nStep 3: Verifying in database...\n";
    global $wpdb;
    $transaction = $wpdb->get_row($wpdb->prepare(
        "SELECT id, title, property_address, city, purchase_price, mls_number, mls_status
        FROM {$wpdb->prefix}ma_transactions
        WHERE id = %d",
        $result['transaction_id']
    ), ARRAY_A);

    if ($transaction) {
        echo "✓ Transaction found in database\n";
        echo "  ID: {$transaction['id']}\n";
        echo "  Title: {$transaction['title']}\n";
        echo "  MLS Number: {$transaction['mls_number']}\n";
        echo "  MLS Status: {$transaction['mls_status']}\n";
    } else {
        echo "✗ Transaction not found in database\n";
    }

} else {
    echo "✗ Import failed: {$result['message']}\n";

    if (isset($result['errors'])) {
        echo "\nErrors:\n";
        foreach ($result['errors'] as $error) {
            echo "  - {$error}\n";
        }
    }
}

echo "\n" . str_repeat("=", 80) . "\n";

// Show import statistics
echo "\nChecking import statistics...\n";

$stats = $wpdb->get_row($wpdb->prepare(
    "SELECT
        COUNT(*) as total_imported,
        COUNT(CASE WHEN mls_number IS NOT NULL THEN 1 END) as mls_linked,
        COUNT(CASE WHEN mls_status = 'Active' THEN 1 END) as active_listings
    FROM {$wpdb->prefix}ma_transactions
    WHERE account_id = %d",
    $account_id
), ARRAY_A);

echo "Account Statistics:\n";
echo "  Total Transactions: {$stats['total_imported']}\n";
echo "  MLS-Linked: {$stats['mls_linked']}\n";
echo "  Active Listings: {$stats['active_listings']}\n";

echo "\n" . str_repeat("=", 80) . "\n";
echo "Import test completed!\n";
echo str_repeat("=", 80) . "\n\n";

echo "Next steps:\n";
echo "1. View the transaction in the admin UI\n";
echo "2. Schedule automatic sync to keep it updated\n";
echo "3. Import more properties as needed\n";
