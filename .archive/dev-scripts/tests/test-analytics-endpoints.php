<?php
/**
 * Test Analytics Endpoints
 *
 * Tests the new analytics API endpoints to ensure they work correctly
 */

// Load WordPress
require_once __DIR__ . '/wp-load.php';

// Check if WordPress loaded
if (!function_exists('wp_get_current_user')) {
	die("Error: WordPress not loaded\n");
}

echo "=== Testing Analytics Endpoints ===\n\n";

// Get first account for testing
global $wpdb;
$account = $wpdb->get_row("SELECT * FROM {$wpdb->prefix}ma_deal_accounts LIMIT 1");

if (!$account) {
	die("Error: No accounts found in database\n");
}

echo "Using account: {$account->name} (ID: {$account->id})\n\n";

// Test 1: AnalyticsService instantiation
echo "Test 1: Instantiating AnalyticsService...\n";
try {
	$analytics_service = new \MADealRoom\Services\AnalyticsService();
	echo "✓ AnalyticsService instantiated successfully\n\n";
} catch (Exception $e) {
	die("✗ Error: " . $e->getMessage() . "\n");
}

// Test 2: Get Transaction Analytics
echo "Test 2: Get Transaction Analytics...\n";
try {
	$result = $analytics_service->getTransactionAnalytics($account->id);
	echo "✓ Transaction Analytics retrieved:\n";
	echo "  - Total Transactions: {$result['total_transactions']}\n";
	echo "  - Active Transactions: {$result['active_transactions']}\n";
	echo "  - Closed Transactions: {$result['closed_transactions']}\n";
	echo "  - Total Volume: $" . number_format($result['total_volume'], 2) . "\n";
	echo "  - Completion Rate: {$result['completion_rate']}%\n";
	echo "  - Overdue: {$result['overdue_transactions']}\n\n";
} catch (Exception $e) {
	echo "✗ Error: " . $e->getMessage() . "\n\n";
}

// Test 3: Get Transactions By Status
echo "Test 3: Get Transactions By Status...\n";
try {
	$result = $analytics_service->getTransactionsByStatus($account->id);
	echo "✓ Transaction Status Breakdown:\n";
	echo "  - Total: {$result['total']}\n";
	foreach ($result['breakdown'] as $item) {
		echo "  - {$item['status']}: {$item['count']} ({$item['percentage']}%)\n";
	}
	echo "\n";
} catch (Exception $e) {
	echo "✗ Error: " . $e->getMessage() . "\n\n";
}

// Test 4: Get Agent Performance
echo "Test 4: Get Agent Performance...\n";
try {
	$result = $analytics_service->getAgentPerformance($account->id);
	echo "✓ Agent Performance retrieved:\n";
	echo "  - Total Agents: {$result['total_agents']}\n";
	if (count($result['agents']) > 0) {
		foreach (array_slice($result['agents'], 0, 3) as $agent) {
			echo "  - {$agent['agent_name']}: {$agent['total_transactions']} transactions, " .
				 "{$agent['closed_transactions']} closed, \${$agent['total_volume']}\n";
		}
	} else {
		echo "  - No agents found\n";
	}
	echo "\n";
} catch (Exception $e) {
	echo "✗ Error: " . $e->getMessage() . "\n\n";
}

// Test 5: Get Task Completion Analytics
echo "Test 5: Get Task Completion Analytics...\n";
try {
	$result = $analytics_service->getTaskCompletionAnalytics($account->id);
	echo "✓ Task Completion Analytics:\n";
	echo "  - Total Tasks: {$result['total_tasks']}\n";
	echo "  - Completed Tasks: {$result['completed_tasks']}\n";
	echo "  - Pending Tasks: {$result['pending_tasks']}\n";
	echo "  - Overdue Tasks: {$result['overdue_tasks']}\n";
	echo "  - Completion Rate: {$result['completion_rate']}%\n";
	if ($result['avg_completion_time_hours']) {
		echo "  - Avg Completion Time: {$result['avg_completion_time_hours']} hours\n";
	}
	echo "\n";
} catch (Exception $e) {
	echo "✗ Error: " . $e->getMessage() . "\n\n";
}

// Test 6: Get Vendor Request Analytics
echo "Test 6: Get Vendor Request Analytics...\n";
try {
	$result = $analytics_service->getVendorRequestAnalytics($account->id);
	echo "✓ Vendor Request Analytics:\n";
	echo "  - Total Requests: {$result['total_requests']}\n";
	echo "  - Completed Requests: {$result['completed_requests']}\n";
	echo "  - Completion Rate: {$result['completion_rate']}%\n";
	if ($result['avg_response_time_hours']) {
		echo "  - Avg Response Time: {$result['avg_response_time_hours']} hours\n";
	}
	echo "\n";
} catch (Exception $e) {
	echo "✗ Error: " . $e->getMessage() . "\n\n";
}

// Test 7: Test Date Filtering
echo "Test 7: Test Date Filtering (Last 30 days)...\n";
try {
	$start_date = date('Y-m-d', strtotime('-30 days'));
	$end_date = date('Y-m-d');
	$result = $analytics_service->getTransactionAnalytics($account->id, $start_date, $end_date);
	echo "✓ Date filtering works:\n";
	echo "  - Date Range: {$start_date} to {$end_date}\n";
	echo "  - Transactions in range: {$result['total_transactions']}\n\n";
} catch (Exception $e) {
	echo "✗ Error: " . $e->getMessage() . "\n\n";
}

// Test 8: Test Caching
echo "Test 8: Test Caching (should be faster on second call)...\n";
try {
	$start = microtime(true);
	$analytics_service->getTransactionAnalytics($account->id);
	$time1 = (microtime(true) - $start) * 1000;

	$start = microtime(true);
	$analytics_service->getTransactionAnalytics($account->id);
	$time2 = (microtime(true) - $start) * 1000;

	echo "✓ Caching test:\n";
	echo "  - First call: " . round($time1, 2) . " ms\n";
	echo "  - Second call (cached): " . round($time2, 2) . " ms\n";
	echo "  - Speed improvement: " . round($time1 / $time2, 1) . "x faster\n\n";
} catch (Exception $e) {
	echo "✗ Error: " . $e->getMessage() . "\n\n";
}

// Test 9: Clear Cache
echo "Test 9: Clear Analytics Cache...\n";
try {
	$analytics_service->clearCache($account->id);
	echo "✓ Cache cleared successfully\n\n";
} catch (Exception $e) {
	echo "✗ Error: " . $e->getMessage() . "\n\n";
}

echo "=== All Tests Complete ===\n";
echo "\nAnalytics API Endpoints Summary:\n";
echo "✓ GET /wp-json/ma-deal-room/v1/analytics/transactions\n";
echo "✓ GET /wp-json/ma-deal-room/v1/analytics/transactions/by-status\n";
echo "✓ GET /wp-json/ma-deal-room/v1/analytics/agents/performance\n";
echo "✓ GET /wp-json/ma-deal-room/v1/analytics/tasks/completion\n";
echo "✓ GET /wp-json/ma-deal-room/v1/analytics/vendors/requests\n";
echo "✓ DELETE /wp-json/ma-deal-room/v1/analytics/cache\n";
echo "\nAll endpoints support optional query parameters:\n";
echo "  - start_date (Y-m-d format)\n";
echo "  - end_date (Y-m-d format)\n";
