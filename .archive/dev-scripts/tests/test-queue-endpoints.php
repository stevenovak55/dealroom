<?php
/**
 * Test Queue Controller REST API Endpoints
 *
 * This script tests the queue monitoring endpoints
 * to ensure they are properly registered and functional.
 */

// Load WordPress
require_once '/var/www/html/wp-load.php';

echo "=== Queue Controller REST API Test ===\n\n";

// Get admin user for authentication
$admin_user = get_users(['role' => 'administrator', 'number' => 1])[0] ?? null;

if (!$admin_user) {
	echo "❌ No admin user found. Cannot test authenticated endpoints.\n";
	exit(1);
}

echo "✓ Admin user found: {$admin_user->user_login}\n";

// Set current user for permission checks
wp_set_current_user($admin_user->ID);

echo "\n--- Testing Queue Service Direct Access ---\n";

try {
	$job_queue_repo = new MADealRoom\Repositories\JobQueueRepository();
	$job_queue_service = new MADealRoom\Services\Queue\JobQueueService($job_queue_repo);

	// Test getStats()
	echo "\n1. Testing JobQueueService::getStats()\n";
	$stats = $job_queue_service->getStats();
	echo "   Stats: " . json_encode($stats, JSON_PRETTY_PRINT) . "\n";

	// Test countByStatus()
	echo "\n2. Testing JobQueueService::countByStatus()\n";
	$counts = $job_queue_service->countByStatus();
	echo "   Counts: " . json_encode($counts, JSON_PRETTY_PRINT) . "\n";

	// Test getFailed()
	echo "\n3. Testing JobQueueService::getFailed()\n";
	$failed = $job_queue_service->getFailed(10);
	echo "   Failed jobs count: " . count($failed) . "\n";
	if (count($failed) > 0) {
		echo "   First failed job: " . json_encode($failed[0], JSON_PRETTY_PRINT) . "\n";
	}

	echo "\n✓ Direct service access successful!\n";

} catch (Exception $e) {
	echo "❌ Service error: " . $e->getMessage() . "\n";
	exit(1);
}

echo "\n--- Testing REST API Endpoints ---\n";

// Helper function to test REST endpoint
function test_rest_endpoint($route, $method = 'GET', $data = []) {
	global $admin_user;

	$request = new WP_REST_Request($method, $route);

	// Add authentication
	wp_set_current_user($admin_user->ID);

	// Add request data
	if (!empty($data)) {
		foreach ($data as $key => $value) {
			$request->set_param($key, $value);
		}
	}

	// Process request
	$server = rest_get_server();
	$response = $server->dispatch($request);

	return [
		'status' => $response->get_status(),
		'data' => $response->get_data(),
	];
}

// Test 1: GET /queue/stats
echo "\n1. GET /ma-deal-room/v1/queue/stats\n";
try {
	$result = test_rest_endpoint('/ma-deal-room/v1/queue/stats');
	echo "   Status: {$result['status']}\n";

	if ($result['status'] === 200) {
		echo "   ✓ SUCCESS\n";
		echo "   Data: " . json_encode($result['data'], JSON_PRETTY_PRINT) . "\n";
	} else {
		echo "   ❌ FAILED\n";
		echo "   Response: " . json_encode($result['data'], JSON_PRETTY_PRINT) . "\n";
	}
} catch (Exception $e) {
	echo "   ❌ ERROR: " . $e->getMessage() . "\n";
}

// Test 2: GET /queue/counts
echo "\n2. GET /ma-deal-room/v1/queue/counts\n";
try {
	$result = test_rest_endpoint('/ma-deal-room/v1/queue/counts');
	echo "   Status: {$result['status']}\n";

	if ($result['status'] === 200) {
		echo "   ✓ SUCCESS\n";
		echo "   Data: " . json_encode($result['data'], JSON_PRETTY_PRINT) . "\n";
	} else {
		echo "   ❌ FAILED\n";
		echo "   Response: " . json_encode($result['data'], JSON_PRETTY_PRINT) . "\n";
	}
} catch (Exception $e) {
	echo "   ❌ ERROR: " . $e->getMessage() . "\n";
}

// Test 3: GET /queue/jobs
echo "\n3. GET /ma-deal-room/v1/queue/jobs?limit=10\n";
try {
	$result = test_rest_endpoint('/ma-deal-room/v1/queue/jobs', 'GET', ['limit' => 10]);
	echo "   Status: {$result['status']}\n";

	if ($result['status'] === 200) {
		echo "   ✓ SUCCESS\n";
		$job_count = count($result['data']['data']['jobs'] ?? []);
		echo "   Jobs returned: {$job_count}\n";
	} else {
		echo "   ❌ FAILED\n";
		echo "   Response: " . json_encode($result['data'], JSON_PRETTY_PRINT) . "\n";
	}
} catch (Exception $e) {
	echo "   ❌ ERROR: " . $e->getMessage() . "\n";
}

// Test 4: GET /queue/failed
echo "\n4. GET /ma-deal-room/v1/queue/failed?limit=10\n";
try {
	$result = test_rest_endpoint('/ma-deal-room/v1/queue/failed', 'GET', ['limit' => 10]);
	echo "   Status: {$result['status']}\n";

	if ($result['status'] === 200) {
		echo "   ✓ SUCCESS\n";
		$failed_count = count($result['data']['data']['jobs'] ?? []);
		echo "   Failed jobs returned: {$failed_count}\n";
	} else {
		echo "   ❌ FAILED\n";
		echo "   Response: " . json_encode($result['data'], JSON_PRETTY_PRINT) . "\n";
	}
} catch (Exception $e) {
	echo "   ❌ ERROR: " . $e->getMessage() . "\n";
}

// Test 5: GET /queue/jobs with status filter
echo "\n5. GET /ma-deal-room/v1/queue/jobs?status=pending&limit=5\n";
try {
	$result = test_rest_endpoint('/ma-deal-room/v1/queue/jobs', 'GET', ['status' => 'pending', 'limit' => 5]);
	echo "   Status: {$result['status']}\n";

	if ($result['status'] === 200) {
		echo "   ✓ SUCCESS\n";
		$job_count = count($result['data']['data']['jobs'] ?? []);
		echo "   Pending jobs returned: {$job_count}\n";
	} else {
		echo "   ❌ FAILED\n";
		echo "   Response: " . json_encode($result['data'], JSON_PRETTY_PRINT) . "\n";
	}
} catch (Exception $e) {
	echo "   ❌ ERROR: " . $e->getMessage() . "\n";
}

echo "\n=== Test Complete ===\n";
echo "\nNote: POST endpoints (retry) not tested as they would modify queue state.\n";
echo "These should be tested manually when needed.\n";
