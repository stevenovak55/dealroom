<?php
/**
 * Test Report Export Functionality
 *
 * Tests T2.3.3 (PDF Export) and T2.3.4 (Excel/CSV Export)
 */

// Load WordPress
require_once __DIR__ . '/wp-load.php';

// Check if WordPress loaded
if (!function_exists('wp_get_current_user')) {
	die("Error: WordPress not loaded\n");
}

echo "=== Testing Report Export Functionality (T2.3.3 & T2.3.4) ===\n\n";

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
	echo "✓ All services instantiated successfully\n\n";
} catch (Exception $e) {
	die("✗ Error: " . $e->getMessage() . "\n");
}

// ==========================================
// T2.3.3: PDF Export Functionality Tests
// ==========================================
echo "====== T2.3.3: PDF Export Functionality ======\n\n";

// Test 1: Transaction Summary PDF
echo "Test 1: Generate Transaction Summary PDF...\n";
try {
	$pdf_content = $pdf_generator->generateTransactionSummaryReport(
		$account->id,
		'2025-01-01',
		'2025-12-31'
	);
	$size = strlen($pdf_content);
	$size_kb = number_format($size / 1024, 2);
	echo "✓ Transaction Summary PDF generated successfully\n";
	echo "  - Size: {$size_kb} KB\n";
	echo "  - Within size limit: " . ($size < 2097152 ? 'Yes' : 'No') . " (< 2MB)\n\n";
} catch (Exception $e) {
	echo "✗ Error: " . $e->getMessage() . "\n\n";
}

// Test 2: Agent Performance PDF
echo "Test 2: Generate Agent Performance PDF...\n";
try {
	$pdf_content = $pdf_generator->generateAgentPerformanceReport(
		$account->id,
		'2025-01-01',
		'2025-12-31'
	);
	$size = strlen($pdf_content);
	$size_kb = number_format($size / 1024, 2);
	echo "✓ Agent Performance PDF generated successfully\n";
	echo "  - Size: {$size_kb} KB\n";
	echo "  - Within size limit: " . ($size < 2097152 ? 'Yes' : 'No') . " (< 2MB)\n\n";
} catch (Exception $e) {
	echo "✗ Error: " . $e->getMessage() . "\n\n";
}

// Test 3: Vendor Activity PDF
echo "Test 3: Generate Vendor Activity PDF...\n";
try {
	$pdf_content = $pdf_generator->generateVendorActivityReport(
		$account->id,
		'2025-01-01',
		'2025-12-31'
	);
	$size = strlen($pdf_content);
	$size_kb = number_format($size / 1024, 2);
	echo "✓ Vendor Activity PDF generated successfully\n";
	echo "  - Size: {$size_kb} KB\n";
	echo "  - Within size limit: " . ($size < 2097152 ? 'Yes' : 'No') . " (< 2MB)\n\n";
} catch (Exception $e) {
	echo "✗ Error: " . $e->getMessage() . "\n\n";
}

// Test 4: Custom Report PDF
echo "Test 4: Generate Custom Report PDF...\n";
try {
	$custom_data = [
		['Metric' => 'Total Transactions', 'Value' => '50'],
		['Metric' => 'Average Volume', 'Value' => '$250,000'],
		['Metric' => 'Success Rate', 'Value' => '85%'],
	];
	$pdf_content = $pdf_generator->generateCustomReport(
		$account->id,
		$custom_data,
		'Custom Analytics Report'
	);
	$size = strlen($pdf_content);
	$size_kb = number_format($size / 1024, 2);
	echo "✓ Custom Report PDF generated successfully\n";
	echo "  - Size: {$size_kb} KB\n";
	echo "  - Within size limit: " . ($size < 2097152 ? 'Yes' : 'No') . " (< 2MB)\n\n";
} catch (Exception $e) {
	echo "✗ Error: " . $e->getMessage() . "\n\n";
}

// ==========================================
// T2.3.4: Excel/CSV Export Tests
// ==========================================
echo "\n====== T2.3.4: Excel/CSV Export Functionality ======\n\n";

// Test 5: Transaction Excel Export
echo "Test 5: Generate Transaction Excel Export...\n";
try {
	$excel_content = $excel_generator->generateTransactionExport(
		$account->id,
		'2025-01-01',
		'2025-12-31'
	);
	$size = strlen($excel_content);
	$size_kb = number_format($size / 1024, 2);
	echo "✓ Transaction Excel export generated successfully\n";
	echo "  - Size: {$size_kb} KB\n\n";
} catch (Exception $e) {
	echo "✗ Error: " . $e->getMessage() . "\n\n";
}

// Test 6: Agent Performance Excel Export
echo "Test 6: Generate Agent Performance Excel Export...\n";
try {
	$excel_content = $excel_generator->generateAgentPerformanceExport(
		$account->id,
		'2025-01-01',
		'2025-12-31'
	);
	$size = strlen($excel_content);
	$size_kb = number_format($size / 1024, 2);
	echo "✓ Agent Performance Excel export generated successfully\n";
	echo "  - Size: {$size_kb} KB\n\n";
} catch (Exception $e) {
	echo "✗ Error: " . $e->getMessage() . "\n\n";
}

// Test 7: Vendor Activity Excel Export
echo "Test 7: Generate Vendor Activity Excel Export...\n";
try {
	$excel_content = $excel_generator->generateVendorActivityExport(
		$account->id,
		'2025-01-01',
		'2025-12-31'
	);
	$size = strlen($excel_content);
	$size_kb = number_format($size / 1024, 2);
	echo "✓ Vendor Activity Excel export generated successfully\n";
	echo "  - Size: {$size_kb} KB\n\n";
} catch (Exception $e) {
	echo "✗ Error: " . $e->getMessage() . "\n\n";
}

// Test 8: Task Excel Export
echo "Test 8: Generate Task Excel Export...\n";
try {
	$excel_content = $excel_generator->generateTaskExport(
		$account->id,
		'2025-01-01',
		'2025-12-31'
	);
	$size = strlen($excel_content);
	$size_kb = number_format($size / 1024, 2);
	echo "✓ Task Excel export generated successfully\n";
	echo "  - Size: {$size_kb} KB\n\n";
} catch (Exception $e) {
	echo "✗ Error: " . $e->getMessage() . "\n\n";
}

// Test 9: Transaction CSV Export
echo "Test 9: Generate Transaction CSV Export...\n";
try {
	$csv_content = $csv_generator->generateTransactionExport(
		$account->id,
		'2025-01-01',
		'2025-12-31'
	);
	$size = strlen($csv_content);
	$size_kb = number_format($size / 1024, 2);
	echo "✓ Transaction CSV export generated successfully\n";
	echo "  - Size: {$size_kb} KB\n";
	// Check if it's valid CSV
	$lines = explode("\n", $csv_content);
	echo "  - Lines: " . count($lines) . "\n\n";
} catch (Exception $e) {
	echo "✗ Error: " . $e->getMessage() . "\n\n";
}

// Test 10: Agent Performance CSV Export
echo "Test 10: Generate Agent Performance CSV Export...\n";
try {
	$csv_content = $csv_generator->generateAgentPerformanceExport(
		$account->id,
		'2025-01-01',
		'2025-12-31'
	);
	$size = strlen($csv_content);
	$size_kb = number_format($size / 1024, 2);
	echo "✓ Agent Performance CSV export generated successfully\n";
	echo "  - Size: {$size_kb} KB\n";
	$lines = explode("\n", $csv_content);
	echo "  - Lines: " . count($lines) . "\n\n";
} catch (Exception $e) {
	echo "✗ Error: " . $e->getMessage() . "\n\n";
}

// Test 11: Vendor Activity CSV Export
echo "Test 11: Generate Vendor Activity CSV Export...\n";
try {
	$csv_content = $csv_generator->generateVendorActivityExport(
		$account->id,
		'2025-01-01',
		'2025-12-31'
	);
	$size = strlen($csv_content);
	$size_kb = number_format($size / 1024, 2);
	echo "✓ Vendor Activity CSV export generated successfully\n";
	echo "  - Size: {$size_kb} KB\n";
	$lines = explode("\n", $csv_content);
	echo "  - Lines: " . count($lines) . "\n\n";
} catch (Exception $e) {
	echo "✗ Error: " . $e->getMessage() . "\n\n";
}

// Test 12: Task CSV Export
echo "Test 12: Generate Task CSV Export...\n";
try {
	$csv_content = $csv_generator->generateTaskExport(
		$account->id,
		'2025-01-01',
		'2025-12-31'
	);
	$size = strlen($csv_content);
	$size_kb = number_format($size / 1024, 2);
	echo "✓ Task CSV export generated successfully\n";
	echo "  - Size: {$size_kb} KB\n";
	$lines = explode("\n", $csv_content);
	echo "  - Lines: " . count($lines) . "\n\n";
} catch (Exception $e) {
	echo "✗ Error: " . $e->getMessage() . "\n\n";
}

echo "\n=== All Tests Complete ===\n";
