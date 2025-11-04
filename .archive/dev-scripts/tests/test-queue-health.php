<?php
/**
 * Test Queue Health Endpoint - T2.4.5
 */

require_once '/var/www/html/wp-load.php';

echo "=== Testing Queue Health Endpoint (T2.4.5) ===\n\n";

// Test health endpoint
echo "1. Testing GET /queue/health\n";

try {
	$request = new WP_REST_Request('GET', '/ma-deal-room/v1/queue/health');
	$server = rest_get_server();
	$response = $server->dispatch($request);

	echo "   Status Code: " . $response->get_status() . "\n";

	if ($response->get_status() === 200 || $response->get_status() === 503) {
		echo "   ✓ Endpoint accessible\n";
		$data = $response->get_data();
		echo "   Response:\n";
		echo json_encode($data, JSON_PRETTY_PRINT) . "\n";

		// Verify response structure
		$required_fields = ['status', 'timestamp', 'checks', 'metrics'];
		$missing = [];
		foreach ($required_fields as $field) {
			if (!isset($data[$field])) {
				$missing[] = $field;
			}
		}

		if (empty($missing)) {
			echo "   ✓ All required fields present\n";
		} else {
			echo "   ❌ Missing fields: " . implode(', ', $missing) . "\n";
		}
	} else {
		echo "   ❌ Unexpected status: " . $response->get_status() . "\n";
		print_r($response->get_data());
	}
} catch (Exception $e) {
	echo "   ❌ Error: " . $e->getMessage() . "\n";
}

echo "\n=== Test Complete ===\n";
