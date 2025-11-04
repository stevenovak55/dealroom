<?php
/**
 * Verify Excel Export Contains Actual Data
 */

// Load WordPress
require_once __DIR__ . '/wp-load.php';

if (!function_exists('wp_get_current_user')) {
	die("Error: WordPress not loaded\n");
}

echo "=== Verifying Excel Export Data (Bug Fix) ===\n\n";

// Get first account
global $wpdb;
$account = $wpdb->get_row("SELECT * FROM {$wpdb->prefix}ma_deal_accounts LIMIT 1");

if (!$account) {
	die("Error: No accounts found\n");
}

echo "Using account: {$account->name} (ID: {$account->id})\n\n";

// Instantiate services
$analytics_service = new \MADealRoom\Services\AnalyticsService(
	new \MADealRoom\Services\CacheService()
);
$excel_generator = new \MADealRoom\Services\ReportGenerator\ExcelReportGenerator($analytics_service);

// Generate transaction export
echo "Test: Generate Transaction Excel Export...\n";
$excel_content = $excel_generator->generateTransactionExport($account->id, null, null);

// Load the Excel file to inspect
$reader = new \PhpOffice\PhpSpreadsheet\Reader\Xlsx();
$spreadsheet = $reader->loadFromString($excel_content);
$sheet = $spreadsheet->getActiveSheet();

// Get dimensions
$highestRow = $sheet->getHighestRow();
$highestColumn = $sheet->getHighestColumn();

echo "✓ Excel file loaded successfully\n";
echo "  Rows: {$highestRow}\n";
echo "  Columns: {$highestColumn}\n\n";

// Check headers (row 1)
echo "Headers (Row 1):\n";
for ($col = 'A'; $col <= $highestColumn; $col++) {
	$value = $sheet->getCell($col . '1')->getValue();
	echo "  {$col}: {$value}\n";
}
echo "\n";

// Check if we have data rows (should be > 1 if there's data)
if ($highestRow > 1) {
	echo "✓ DATA FOUND: {" . ($highestRow - 1) . "} transaction rows\n\n";

	// Show first data row (row 2)
	echo "Sample Data (Row 2):\n";
	for ($col = 'A'; $col <= $highestColumn; $col++) {
		$header = $sheet->getCell($col . '1')->getValue();
		$value = $sheet->getCell($col . '2')->getValue();
		echo "  {$header}: " . ($value ?? 'NULL') . "\n";
	}
	echo "\n";

	// Show row 3 if exists
	if ($highestRow > 2) {
		echo "Sample Data (Row 3):\n";
		for ($col = 'A'; $col <= $highestColumn; $col++) {
			$header = $sheet->getCell($col . '1')->getValue();
			$value = $sheet->getCell($col . '3')->getValue();
			echo "  {$header}: " . ($value ?? 'NULL') . "\n";
		}
	}
} else {
	echo "✗ NO DATA: Only headers found (no transaction rows)\n";
}

echo "\n=== Verification Complete ===\n";
echo "\n";
echo "RESULT: " . ($highestRow > 1 ? "✅ BUG FIXED - Excel contains data!" : "❌ Bug not fixed - Excel is still empty") . "\n";
