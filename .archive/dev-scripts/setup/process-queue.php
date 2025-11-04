<?php
/**
 * Queue Processor Script
 *
 * Process jobs from the job queue. Can be run via cron or manually.
 *
 * Usage:
 *   php process-queue.php [batch_size]
 *   docker exec ma-dealroom-wp php /var/www/html/process-queue.php
 *
 * @package MADealRoom
 * @since 1.0.0
 */

require_once('/var/www/html/wp-load.php');

// Get batch size from command line argument or use default
$batch_size = isset($argv[1]) ? (int) $argv[1] : 100;

echo "MA Deal Room - Queue Processor\n";
echo str_repeat("=", 80) . "\n";
echo "Batch Size: {$batch_size}\n";
echo "Time: " . date('Y-m-d H:i:s') . "\n";
echo str_repeat("=", 80) . "\n\n";

try {
	// Get job queue service from container
	$plugin = \MADealRoom\Core\Plugin::instance();
	$job_queue_service = $plugin->container()->get('job_queue_service');

	if (!$job_queue_service) {
		throw new Exception('JobQueueService not found in container');
	}

	// Reset stale jobs first (jobs stuck in processing for > 30 minutes)
	echo "Checking for stale jobs...\n";
	$reset_count = $job_queue_service->resetStaleJobs(30);
	if ($reset_count > 0) {
		echo "  ✓ Reset {$reset_count} stale jobs\n\n";
	} else {
		echo "  ✓ No stale jobs found\n\n";
	}

	// Get queue statistics before processing
	$stats = $job_queue_service->getStats();
	echo "Queue Status:\n";
	echo "  Pending: " . ($stats['pending'] ?? 0) . "\n";
	echo "  Processing: " . ($stats['processing'] ?? 0) . "\n";
	echo "  High Priority: " . ($stats['high_priority_pending'] ?? 0) . "\n";
	echo "\n";

	if (($stats['pending'] ?? 0) == 0) {
		echo "✓ No pending jobs to process.\n";
		exit(0);
	}

	// Process pending jobs
	echo "Processing jobs...\n";
	$start_time = microtime(true);

	$results = $job_queue_service->processPending($batch_size);

	$execution_time = round(microtime(true) - $start_time, 2);

	// Output results
	echo "\nProcessing Results:\n";
	echo str_repeat("-", 80) . "\n";
	echo "  Total Processed: {$results['processed']}\n";
	echo "  Successful: {$results['successful']}\n";
	echo "  Failed: {$results['failed']}\n";
	echo "  Execution Time: {$execution_time}s\n";

	// Show errors if any
	if (!empty($results['errors'])) {
		echo "\nErrors:\n";
		foreach ($results['errors'] as $error) {
			echo "  Job #{$error['job_id']}: {$error['error']}\n";
		}
	}

	echo str_repeat("=", 80) . "\n";

	// Exit with appropriate code
	exit($results['failed'] > 0 ? 1 : 0);

} catch (Exception $e) {
	echo "\n✗ ERROR: " . $e->getMessage() . "\n";
	echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
	exit(1);
}
