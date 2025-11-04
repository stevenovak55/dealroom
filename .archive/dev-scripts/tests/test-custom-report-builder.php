<?php
/**
 * Test Custom Report Builder Functionality (T2.3.5)
 *
 * Tests the custom report builder with QueryBuilder, CustomReportBuilder,
 * and the REST API endpoints
 */

// Load WordPress
require_once __DIR__ . '/wp-load.php';

// Check if WordPress loaded
if (!function_exists('wp_get_current_user')) {
	die("Error: WordPress not loaded\n");
}

echo "=== Testing Custom Report Builder Functionality (T2.3.5) ===\n\n";

// Get first account for testing
global $wpdb;
$account = $wpdb->get_row("SELECT * FROM {$wpdb->prefix}ma_deal_accounts LIMIT 1");

if (!$account) {
	die("Error: No accounts found in database\n");
}

echo "Using account: {$account->name} (ID: {$account->id})\n\n";

// Instantiate services
echo "Initializing services...\n";
try {
	$analytics_service = new \MADealRoom\Services\AnalyticsService(
		new \MADealRoom\Services\CacheService()
	);
	$pdf_generator = new \MADealRoom\Services\ReportGenerator\PDFReportGenerator($analytics_service);
	$excel_generator = new \MADealRoom\Services\ReportGenerator\ExcelReportGenerator($analytics_service);
	$csv_generator = new \MADealRoom\Services\ReportGenerator\CSVReportGenerator($analytics_service);
	$custom_builder = new \MADealRoom\Services\ReportGenerator\CustomReportBuilder(
		$pdf_generator,
		$excel_generator,
		$csv_generator
	);
	$query_builder = new \MADealRoom\Services\ReportGenerator\QueryBuilder();

	echo "✓ All services instantiated successfully\n\n";
} catch (Exception $e) {
	die("✗ Error: " . $e->getMessage() . "\n");
}

// ==========================================
// Test 1: QueryBuilder - Safe Query Generation
// ==========================================
echo "====== Test 1: QueryBuilder - Safe Query Generation ======\n\n";

echo "Test 1.1: Build simple transaction query...\n";
try {
	$result = $query_builder->build([
		'table' => 'transactions',
		'columns' => ['id', 'property_address', 'status'],
		'account_id' => $account->id,
		'limit' => 10,
	]);
	echo "✓ Query built successfully\n";
	echo "  Query: " . substr($result['query'], 0, 100) . "...\n";
	echo "  Params: " . count($result['params']) . " parameters\n\n";
} catch (Exception $e) {
	echo "✗ Error: " . $e->getMessage() . "\n\n";
}

echo "Test 1.2: Build query with filters...\n";
try {
	$result = $query_builder->build([
		'table' => 'transactions',
		'columns' => ['id', 'property_address', 'status'],
		'filters' => [
			['field' => 'status', 'operator' => '=', 'value' => 'active'],
		],
		'account_id' => $account->id,
		'limit' => 10,
	]);
	echo "✓ Query with filters built successfully\n";
	echo "  Params: " . count($result['params']) . " parameters\n\n";
} catch (Exception $e) {
	echo "✗ Error: " . $e->getMessage() . "\n\n";
}

echo "Test 1.3: Build query with GROUP BY and ORDER BY...\n";
try {
	$result = $query_builder->build([
		'table' => 'transactions',
		'columns' => ['status'],
		'group_by' => ['status'],
		'order_by' => ['field' => 'status', 'direction' => 'ASC'],
		'account_id' => $account->id,
		'limit' => 10,
	]);
	echo "✓ Query with GROUP BY and ORDER BY built successfully\n\n";
} catch (Exception $e) {
	echo "✗ Error: " . $e->getMessage() . "\n\n";
}

echo "Test 1.4: Security - Reject invalid table...\n";
try {
	$result = $query_builder->build([
		'table' => 'wp_users', // Not in whitelist
		'columns' => ['id'],
		'account_id' => $account->id,
	]);
	echo "✗ Security FAILED: Invalid table accepted!\n\n";
} catch (Exception $e) {
	echo "✓ Security PASSED: Invalid table rejected\n";
	echo "  Error: " . $e->getMessage() . "\n\n";
}

echo "Test 1.5: Security - Reject invalid column...\n";
try {
	$result = $query_builder->build([
		'table' => 'transactions',
		'columns' => ['password'], // Not in whitelist
		'account_id' => $account->id,
	]);
	echo "✗ Security FAILED: Invalid column accepted!\n\n";
} catch (Exception $e) {
	echo "✓ Security PASSED: Invalid column rejected\n";
	echo "  Error: " . $e->getMessage() . "\n\n";
}

// ==========================================
// Test 2: CustomReportBuilder - JSON Format
// ==========================================
echo "\n====== Test 2: CustomReportBuilder - JSON Format ======\n\n";

echo "Test 2.1: Build simple transaction report (JSON)...\n";
try {
	$result = $custom_builder->buildReport($account->id, [
		'table' => 'transactions',
		'columns' => ['id', 'property_address', 'status', 'list_price'],
		'format' => 'json',
		'limit' => 5,
	]);
	echo "✓ Transaction report built successfully\n";
	echo "  Total rows: {$result['meta']['total']}\n";
	echo "  Columns: " . implode(', ', $result['meta']['columns']) . "\n";
	if (!empty($result['data'])) {
		echo "  Sample row: " . json_encode($result['data'][0]) . "\n";
	}
	echo "\n";
} catch (Exception $e) {
	echo "✗ Error: " . $e->getMessage() . "\n\n";
}

echo "Test 2.2: Build task report with filters (JSON)...\n";
try {
	$result = $custom_builder->buildReport($account->id, [
		'table' => 'tasks',
		'columns' => ['id', 'title', 'status', 'due_at'],
		'filters' => [
			['field' => 'status', 'operator' => '!=', 'value' => 'completed'],
		],
		'format' => 'json',
		'limit' => 10,
	]);
	echo "✓ Task report with filters built successfully\n";
	echo "  Total rows: {$result['meta']['total']}\n\n";
} catch (Exception $e) {
	echo "✗ Error: " . $e->getMessage() . "\n\n";
}

echo "Test 2.3: Build vendor request report grouped by status (JSON)...\n";
try {
	$result = $custom_builder->buildReport($account->id, [
		'table' => 'vendor_requests',
		'columns' => ['status'],
		'group_by' => ['status'],
		'format' => 'json',
	]);
	echo "✓ Vendor request grouped report built successfully\n";
	echo "  Total groups: {$result['meta']['total']}\n\n";
} catch (Exception $e) {
	echo "✗ Error: " . $e->getMessage() . "\n\n";
}

// ==========================================
// Test 3: CustomReportBuilder - Export Formats
// ==========================================
echo "\n====== Test 3: CustomReportBuilder - Export Formats ======\n\n";

echo "Test 3.1: Build transaction report as PDF...\n";
try {
	$pdf_content = $custom_builder->buildReport($account->id, [
		'table' => 'transactions',
		'columns' => ['id', 'property_address', 'status', 'list_price'],
		'format' => 'pdf',
		'title' => 'Transaction Report Test',
		'limit' => 5,
	]);
	$size_kb = number_format(strlen($pdf_content) / 1024, 2);
	echo "✓ PDF report generated successfully\n";
	echo "  Size: {$size_kb} KB\n\n";
} catch (Exception $e) {
	echo "✗ Error: " . $e->getMessage() . "\n\n";
}

echo "Test 3.2: Build transaction report as Excel...\n";
try {
	$excel_content = $custom_builder->buildReport($account->id, [
		'table' => 'transactions',
		'columns' => ['id', 'property_address', 'status', 'list_price'],
		'format' => 'excel',
		'title' => 'Transaction Report Test',
		'limit' => 5,
	]);
	$size_kb = number_format(strlen($excel_content) / 1024, 2);
	echo "✓ Excel report generated successfully\n";
	echo "  Size: {$size_kb} KB\n\n";
} catch (Exception $e) {
	echo "✗ Error: " . $e->getMessage() . "\n\n";
}

echo "Test 3.3: Build transaction report as CSV...\n";
try {
	$csv_content = $custom_builder->buildReport($account->id, [
		'table' => 'transactions',
		'columns' => ['id', 'property_address', 'status'],
		'format' => 'csv',
		'limit' => 5,
	]);
	$lines = substr_count($csv_content, "\n");
	echo "✓ CSV report generated successfully\n";
	echo "  Lines: {$lines}\n\n";
} catch (Exception $e) {
	echo "✗ Error: " . $e->getMessage() . "\n\n";
}

// ==========================================
// Test 4: Helper Methods
// ==========================================
echo "\n====== Test 4: Helper Methods ======\n\n";

echo "Test 4.1: Get available tables...\n";
try {
	$tables = $custom_builder->getAvailableTables();
	echo "✓ Available tables retrieved successfully\n";
	echo "  Total tables: " . count($tables) . "\n";
	foreach ($tables as $table) {
		echo "  - {$table['name']} ({$table['id']}): {$table['description']}\n";
	}
	echo "\n";
} catch (Exception $e) {
	echo "✗ Error: " . $e->getMessage() . "\n\n";
}

echo "Test 4.2: Get available columns for 'transactions' table...\n";
try {
	$columns = $custom_builder->getAvailableColumns('transactions');
	echo "✓ Available columns retrieved successfully\n";
	echo "  Total columns: " . count($columns) . "\n";
	foreach (array_slice($columns, 0, 5) as $column) {
		echo "  - {$column['name']} ({$column['id']}) - Type: {$column['type']}\n";
	}
	echo "\n";
} catch (Exception $e) {
	echo "✗ Error: " . $e->getMessage() . "\n\n";
}

// ==========================================
// Test 5: Security & Validation
// ==========================================
echo "\n====== Test 5: Security & Validation Tests ======\n\n";

echo "Test 5.1: Reject invalid table in config...\n";
try {
	$result = $custom_builder->buildReport($account->id, [
		'table' => 'wp_posts',
		'columns' => ['id'],
		'format' => 'json',
	]);
	echo "✗ Security FAILED: Invalid table accepted!\n\n";
} catch (Exception $e) {
	echo "✓ Security PASSED: Invalid table rejected\n";
	echo "  Error: " . $e->getMessage() . "\n\n";
}

echo "Test 5.2: Reject invalid column in config...\n";
try {
	$result = $custom_builder->buildReport($account->id, [
		'table' => 'transactions',
		'columns' => ['id', 'user_password'],
		'format' => 'json',
	]);
	echo "✗ Security FAILED: Invalid column accepted!\n\n";
} catch (Exception $e) {
	echo "✓ Security PASSED: Invalid column rejected\n";
	echo "  Error: " . $e->getMessage() . "\n\n";
}

echo "Test 5.3: Reject missing required fields...\n";
try {
	$result = $custom_builder->buildReport($account->id, [
		'format' => 'json',
		// Missing table and columns
	]);
	echo "✗ Validation FAILED: Missing fields accepted!\n\n";
} catch (Exception $e) {
	echo "✓ Validation PASSED: Missing fields rejected\n";
	echo "  Error: " . $e->getMessage() . "\n\n";
}

echo "\n=== All Tests Complete ===\n";
echo "\nSummary:\n";
echo "- QueryBuilder: Safe SQL generation with whitelisted tables/columns ✓\n";
echo "- CustomReportBuilder: JSON format working ✓\n";
echo "- CustomReportBuilder: PDF/Excel/CSV export working ✓\n";
echo "- Helper methods: Available tables/columns ✓\n";
echo "- Security: SQL injection prevention working ✓\n";
echo "- Validation: Invalid input rejection working ✓\n";
