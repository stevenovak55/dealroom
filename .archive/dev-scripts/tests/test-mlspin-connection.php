<?php
/**
 * MLS PIN Bridge Interactive Connection Test
 *
 * Standalone test for MLS PIN using Bridge Interactive API
 * Tests connection without requiring WordPress.
 */

// Simple direct API test using cURL
function testBridgeConnection($config) {
    $results = [
        'success' => false,
        'steps' => [],
    ];

    // Step 1: Test basic connectivity
    $results['steps']['connectivity'] = testConnectivity($config['api_url']);

    // Step 2: Test authentication with server token
    $results['steps']['auth'] = testAuthentication($config);

    // Step 3: Try to fetch properties
    if ($results['steps']['auth']['success']) {
        $results['steps']['search'] = testPropertySearch($config);
    }

    $results['success'] = $results['steps']['connectivity']['success'] &&
                         $results['steps']['auth']['success'];

    return $results;
}

function testConnectivity($api_url) {
    echo "Step 1: Testing basic connectivity to MLS PIN...\n";

    $ch = curl_init($api_url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);

    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);

    if ($error) {
        echo "✗ Connection failed: {$error}\n\n";
        return ['success' => false, 'error' => $error];
    }

    echo "✓ Successfully connected to API endpoint\n";
    echo "HTTP Status: {$http_code}\n\n";

    return ['success' => true, 'http_code' => $http_code];
}

function testAuthentication($config) {
    echo "Step 2: Testing authentication with server token...\n";

    // Bridge Interactive uses access token in header
    $url = $config['api_url'] . '/Property';

    $ch = curl_init($url . '?$top=1');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Authorization: Bearer ' . $config['server_token'],
        'Accept: application/json',
        'Content-Type: application/json',
    ]);

    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);

    if ($error) {
        echo "✗ Authentication request failed: {$error}\n\n";
        return ['success' => false, 'error' => $error];
    }

    if ($http_code === 200) {
        echo "✓ Authentication successful!\n";
        echo "HTTP Status: {$http_code}\n\n";
        return ['success' => true, 'response' => $response];
    } else {
        echo "✗ Authentication failed\n";
        echo "HTTP Status: {$http_code}\n";
        echo "Response: " . substr($response, 0, 200) . "...\n\n";
        return ['success' => false, 'http_code' => $http_code, 'response' => $response];
    }
}

function testPropertySearch($config) {
    echo "Step 3: Testing property search...\n";
    echo "Searching for active listings (limit 5)...\n";

    // OData query for active properties
    $url = $config['api_url'] . '/Property';
    $query = [
        '$top' => '5',
        '$filter' => "StandardStatus eq 'Active'",
        '$select' => 'ListingId,UnparsedAddress,City,StateOrProvince,PostalCode,ListPrice,BedroomsTotal,BathroomsTotalInteger,LivingArea,PropertyType,ListingContractDate',
    ];

    $ch = curl_init($url . '?' . http_build_query($query));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Authorization: Bearer ' . $config['server_token'],
        'Accept: application/json',
        'Content-Type: application/json',
    ]);

    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);

    if ($error) {
        echo "✗ Search request failed: {$error}\n\n";
        return ['success' => false, 'error' => $error];
    }

    if ($http_code === 200) {
        $data = json_decode($response, true);

        if (isset($data['value'])) {
            $listings = $data['value'];
            $total = count($listings);

            echo "✓ Search successful!\n";
            echo "Retrieved: {$total} listings\n\n";

            if ($total > 0) {
                echo "Sample Listings:\n";
                echo str_repeat("-", 80) . "\n";

                foreach ($listings as $index => $listing) {
                    echo "\nListing #" . ($index + 1) . ":\n";
                    echo "  MLS Number: " . ($listing['ListingId'] ?? 'N/A') . "\n";
                    echo "  Address: " . ($listing['UnparsedAddress'] ?? 'N/A') . "\n";
                    echo "  City: " . ($listing['City'] ?? 'N/A') . "\n";
                    echo "  State: " . ($listing['StateOrProvince'] ?? 'N/A') . "\n";
                    echo "  ZIP: " . ($listing['PostalCode'] ?? 'N/A') . "\n";
                    echo "  Price: $" . number_format($listing['ListPrice'] ?? 0) . "\n";
                    echo "  Bedrooms: " . ($listing['BedroomsTotal'] ?? 'N/A') . "\n";
                    echo "  Bathrooms: " . ($listing['BathroomsTotalInteger'] ?? 'N/A') . "\n";
                    echo "  Sq Ft: " . number_format($listing['LivingArea'] ?? 0) . "\n";
                    echo "  Property Type: " . ($listing['PropertyType'] ?? 'N/A') . "\n";
                    if (!empty($listing['ListingContractDate'])) {
                        echo "  Listed: " . $listing['ListingContractDate'] . "\n";
                    }
                }

                echo "\n" . str_repeat("-", 80) . "\n";
            }

            return ['success' => true, 'listings' => $listings, 'total' => $total];
        }
    }

    echo "✗ Search failed\n";
    echo "HTTP Status: {$http_code}\n";
    echo "Response: " . substr($response, 0, 500) . "...\n\n";

    return ['success' => false, 'http_code' => $http_code, 'response' => $response];
}

echo "=== MLS PIN Bridge Interactive Connection Test ===\n\n";

// MLS PIN Credentials
$config = [
    'api_url' => 'https://api.bridgedataoutput.com/api/v2/OData/shared_mlspin_41854c5',
    'client_id' => 'sGpguQG8zItqvf4xs7nA',
    'client_secret' => 'pyaLiamANYne0v2ONgJoMp7GsmCsrqEJUVmkkVna',
    'server_token' => '1c69fed3083478d187d4ce8deb8788ed',
    'browser_token' => '6c3ff882c868eb6ace6cd2ad9005ea7c',
];

echo "Configuration:\n";
echo "API URL: {$config['api_url']}\n";
echo "Client ID: {$config['client_id']}\n\n";

// Run tests
$results = testBridgeConnection($config);

// Display summary
echo "\n" . str_repeat("=", 80) . "\n";

if ($results['success'] && isset($results['steps']['search']['success']) && $results['steps']['search']['success']) {
    echo "✓ ALL TESTS PASSED!\n";
    echo "MLS PIN Bridge Interactive integration is working correctly.\n";
    echo str_repeat("=", 80) . "\n\n";

    echo "Next Steps:\n";
    echo "1. Your BridgeClient.php should work with these credentials\n";
    echo "2. Create an MLS configuration via the REST API:\n";
    echo "   POST /wp-json/ma-deal-room/v1/mls/config\n";
    echo "   {\n";
    echo "     \"name\": \"MLS PIN\",\n";
    echo "     \"provider_type\": \"bridge\",\n";
    echo "     \"credentials\": {\n";
    echo "       \"api_url\": \"{$config['api_url']}\",\n";
    echo "       \"server_token\": \"{$config['server_token']}\"\n";
    echo "     }\n";
    echo "   }\n\n";
    echo "3. Start importing properties from MLS PIN\n";
    echo "4. Set up automatic sync scheduling\n\n";
} else {
    echo "✗ TESTS FAILED\n";
    echo "Please review the errors above.\n";
    echo str_repeat("=", 80) . "\n\n";

    if (!$results['success']) {
        echo "Troubleshooting:\n";
        echo "1. Verify your server token is correct\n";
        echo "2. Check if your IP is whitelisted with Bridge Interactive\n";
        echo "3. Ensure the API URL is accessible from your server\n";
        echo "4. Contact Bridge Interactive support if issues persist\n\n";
    }

    exit(1);
}
