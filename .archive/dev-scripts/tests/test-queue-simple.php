<?php
/**
 * Simple Queue Endpoint Test
 */

require_once '/var/www/html/wp-load.php';

// Get admin user
$admin = get_users(['role' => 'administrator', 'number' => 1])[0] ?? null;
if (!$admin) {
	echo "No admin found\n";
	exit(1);
}

wp_set_current_user($admin->ID);

echo "Testing Queue REST Endpoints\n\n";

// Test stats endpoint
$request = new WP_REST_Request('GET', '/ma-deal-room/v1/queue/stats');
$server = rest_get_server();
$response = $server->dispatch($request);

echo "1. GET /queue/stats\n";
echo "   Status: " . $response->get_status() . "\n";

if ($response->get_status() === 200) {
	echo "   ✓ SUCCESS\n";
	$data = $response->get_data();
	if (isset($data['data'])) {
		echo "   Total jobs: " . ($data['data']['total'] ?? 'N/A') . "\n";
		echo "   Pending: " . ($data['data']['pending'] ?? 'N/A') . "\n";
	}
} else {
	echo "   ❌ FAILED\n";
	print_r($response->get_data());
}

// Test counts endpoint
echo "\n2. GET /queue/counts\n";
$request = new WP_REST_Request('GET', '/ma-deal-room/v1/queue/counts');
$response = $server->dispatch($request);
echo "   Status: " . $response->get_status() . "\n";

if ($response->get_status() === 200) {
	echo "   ✓ SUCCESS\n";
	$data = $response->get_data();
	if (isset($data['data'])) {
		echo "   Counts: " . json_encode($data['data']) . "\n";
	}
} else {
	echo "   ❌ FAILED\n";
}

// Test jobs endpoint
echo "\n3. GET /queue/jobs?limit=5\n";
$request = new WP_REST_Request('GET', '/ma-deal-room/v1/queue/jobs');
$request->set_param('limit', 5);
$response = $server->dispatch($request);
echo "   Status: " . $response->get_status() . "\n";

if ($response->get_status() === 200) {
	echo "   ✓ SUCCESS\n";
	$data = $response->get_data();
	if (isset($data['data']['count'])) {
		echo "   Jobs returned: " . $data['data']['count'] . "\n";
	}
} else {
	echo "   ❌ FAILED\n";
}

echo "\n✓ All endpoint tests completed!\n";
