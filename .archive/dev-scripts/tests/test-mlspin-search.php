<?php
/**
 * Test MLS PIN Property Search
 *
 * Tests the search functionality using the MLS import service
 */

// Load WordPress
require_once('/var/www/html/wp-load.php');

// Load required classes
require_once('/var/www/html/wp-content/plugins/ma-deal-room/src/Services/Integration/MLS/MLSClientFactory.php');
require_once('/var/www/html/wp-content/plugins/ma-deal-room/src/Services/Integration/MLS/MLSImportService.php');

use MA_Deal_Room\Services\Integration\MLS\MLSImportService;
use MA_Deal_Room\Services\Integration\MLS\MLSClientFactory;

echo "=== MLS PIN Property Search Test ===\n\n";

// Get services from container
global $ma_deal_room_container;

$import_service = new MLSImportService(
    new MLSClientFactory(),
    $ma_deal_room_container->get('transaction_repository'),
    $ma_deal_room_container->get('job_queue_service')
);

// Test 1: Search for properties in Boston
echo "Test 1: Searching for active properties in Boston...\n";
echo str_repeat("-", 80) . "\n";

$search_criteria = [
    'city' => 'Boston',
    'status' => 'Active',
    'limit' => 5,
];

$result = $import_service->searchListings(1, $search_criteria, 1); // account_id=1, config_id=1

if ($result['success']) {
    $listings = $result['listings'] ?? [];
    $total = $result['total'] ?? 0;

    echo "✓ Search successful!\n";
    echo "Total found: {$total}\n";
    echo "Showing: " . count($listings) . " listings\n\n";

    if (!empty($listings)) {
        foreach ($listings as $index => $listing) {
            echo "Listing #" . ($index + 1) . ":\n";
            echo "  MLS #: {$listing['mls_number']}\n";
            echo "  Address: {$listing['address']}\n";
            echo "  City: {$listing['city']}, {$listing['state']} {$listing['zip']}\n";
            echo "  Price: $" . number_format($listing['price']) . "\n";
            echo "  Type: {$listing['property_type']}\n";
            echo "  Status: {$listing['status']}\n";
            if (isset($listing['bedrooms'])) {
                echo "  Beds/Baths: {$listing['bedrooms']}bd / {$listing['bathrooms']}ba\n";
            }
            if (isset($listing['square_feet']) && $listing['square_feet'] > 0) {
                echo "  Sq Ft: " . number_format($listing['square_feet']) . "\n";
            }
            echo "\n";
        }
    }
} else {
    echo "✗ Search failed: {$result['message']}\n";
}

// Test 2: Search for properties with price range
echo "\n" . str_repeat("-", 80) . "\n";
echo "Test 2: Searching for properties between $500k - $1M...\n";
echo str_repeat("-", 80) . "\n";

$search_criteria = [
    'min_price' => 500000,
    'max_price' => 1000000,
    'status' => 'Active',
    'limit' => 5,
];

$result = $import_service->searchListings(1, $search_criteria, 1);

if ($result['success']) {
    $listings = $result['listings'] ?? [];

    echo "✓ Found " . count($listings) . " properties in this price range\n\n";

    foreach ($listings as $index => $listing) {
        echo ($index + 1) . ". {$listing['address']} - $" . number_format($listing['price']) . "\n";
    }
} else {
    echo "✗ Search failed: {$result['message']}\n";
}

// Test 3: Search by ZIP code
echo "\n" . str_repeat("-", 80) . "\n";
echo "Test 3: Searching in ZIP code 02116 (Back Bay, Boston)...\n";
echo str_repeat("-", 80) . "\n";

$search_criteria = [
    'zip' => '02116',
    'status' => 'Active',
    'limit' => 3,
];

$result = $import_service->searchListings(1, $search_criteria, 1);

if ($result['success']) {
    $listings = $result['listings'] ?? [];

    echo "✓ Found " . count($listings) . " properties in 02116\n\n";

    foreach ($listings as $index => $listing) {
        echo ($index + 1) . ". {$listing['mls_number']} - {$listing['address']}\n";
        echo "   Price: $" . number_format($listing['price']) . " | Type: {$listing['property_type']}\n\n";
    }
} else {
    echo "✗ Search failed: {$result['message']}\n";
}

echo "\n" . str_repeat("=", 80) . "\n";
echo "Search tests completed!\n";
echo str_repeat("=", 80) . "\n";
