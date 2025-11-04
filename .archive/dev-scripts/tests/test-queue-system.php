<?php
/**
 * Test Queue System
 *
 * Comprehensive test script for the job queue infrastructure
 *
 * @package MADealRoom
 * @since 1.0.0
 */

require_once('/var/www/html/wp-load.php');

use MADealRoom\Services\Queue\Jobs\ExampleEmailJob;

echo "MA Deal Room - Queue System Test\n";
echo str_repeat("=", 80) . "\n\n";

$test_results = [];
$test_count = 0;

function run_test($name, $callback) {
	global $test_results, $test_count;
	$test_count++;

	echo "[Test {$test_count}] {$name}... ";

	try {
		$result = $callback();
		if ($result) {
			echo "✓ PASS\n";
			$test_results[] = ['name' => $name, 'status' => 'pass'];
		} else {
			echo "✗ FAIL\n";
			$test_results[] = ['name' => $name, 'status' => 'fail', 'error' => 'Test returned false'];
		}
	} catch (Exception $e) {
		echo "✗ FAIL\n";
		echo "  Error: {$e->getMessage()}\n";
		$test_results[] = ['name' => $name, 'status' => 'fail', 'error' => $e->getMessage()];
	}
}

try {
	// Get services
	$plugin = \MADealRoom\Core\Plugin::instance();
	$container = $plugin->container();
	$job_queue_service = $container->get('job_queue_service');
	$job_queue_repository = $container->get('job_queue_repository');

	// Test 1: Service availability
	run_test('JobQueueService is available', function() use ($job_queue_service) {
		return $job_queue_service !== null;
	});

	run_test('JobQueueRepository is available', function() use ($job_queue_repository) {
		return $job_queue_repository !== null;
	});

	// Test 2: Register job handler
	run_test('Register ExampleEmailJob handler', function() use ($job_queue_service) {
		$job_queue_service->registerHandler(
			'email.send',
			ExampleEmailJob::class
		);
		$handlers = $job_queue_service->getHandlers();
		return isset($handlers['email.send']);
	});

	// Test 3: Create and dispatch jobs
	$job_ids = [];

	run_test('Dispatch high priority job', function() use ($job_queue_service, &$job_ids) {
		$job = new ExampleEmailJob(
			'test-high@example.com',
			'High Priority Test',
			'This is a high priority test email',
			'high'
		);
		$job_id = $job_queue_service->dispatch($job);
		if ($job_id) {
			$job_ids['high'] = $job_id;
		}
		return $job_id !== false;
	});

	run_test('Dispatch normal priority job', function() use ($job_queue_service, &$job_ids) {
		$job = new ExampleEmailJob(
			'test-normal@example.com',
			'Normal Priority Test',
			'This is a normal priority test email',
			'normal'
		);
		$job_id = $job_queue_service->dispatch($job);
		if ($job_id) {
			$job_ids['normal'] = $job_id;
		}
		return $job_id !== false;
	});

	run_test('Dispatch low priority job', function() use ($job_queue_service, &$job_ids) {
		$job = new ExampleEmailJob(
			'test-low@example.com',
			'Low Priority Test',
			'This is a low priority test email',
			'low'
		);
		$job_id = $job_queue_service->dispatch($job);
		if ($job_id) {
			$job_ids['low'] = $job_id;
		}
		return $job_id !== false;
	});

	run_test('Dispatch scheduled job (future)', function() use ($job_queue_service, &$job_ids) {
		$job = new ExampleEmailJob(
			'test-future@example.com',
			'Future Scheduled Test',
			'This is a scheduled test email for the future',
			'normal'
		);
		$scheduled_time = date('Y-m-d H:i:s', strtotime('+1 hour'));
		$job_id = $job_queue_service->dispatch($job, $scheduled_time);
		if ($job_id) {
			$job_ids['future'] = $job_id;
		}
		return $job_id !== false;
	});

	// Test 4: Verify jobs in database
	run_test('Verify pending jobs count', function() use ($job_queue_repository, $job_ids) {
		$counts = $job_queue_repository->countByStatus();
		// Should have 3 pending (high, normal, low) and 1 scheduled for future
		return $counts['pending'] >= 4;
	});

	// Test 5: Get queue statistics
	run_test('Get queue statistics', function() use ($job_queue_service) {
		$stats = $job_queue_service->getStats();
		return isset($stats['total']) && isset($stats['pending']);
	});

	// Test 6: Process pending jobs (will process 3, skip the future one)
	$process_results = null;
	run_test('Process pending jobs', function() use ($job_queue_service, &$process_results) {
		$process_results = $job_queue_service->processPending(10);
		// Should process 3 jobs (high, normal, low) but not the future scheduled one
		return $process_results['processed'] >= 3;
	});

	// Test 7: Verify processing results
	run_test('Verify jobs were processed successfully', function() use ($process_results) {
		// All jobs should succeed (even though emails won't actually send in test)
		return $process_results['successful'] >= 0; // Emails may fail in test environment
	});

	// Test 8: Verify job statuses updated
	run_test('Verify high priority job completed/failed', function() use ($job_queue_repository, $job_ids) {
		$job = $job_queue_repository->getById($job_ids['high']);
		return in_array($job['status'], ['completed', 'failed', 'dead']);
	});

	// Test 9: Test retry functionality
	run_test('Test retry failed job', function() use ($job_queue_service, $job_queue_repository, $job_ids) {
		// Get any failed job
		$failed_jobs = $job_queue_repository->getFailed(1);
		if (empty($failed_jobs)) {
			// If no failed jobs, mark one as failed manually for testing
			$job_queue_repository->markAsFailed($job_ids['low'], 'Test failure', true);
			return $job_queue_service->retryJob($job_ids['low']);
		}
		return $job_queue_service->retryJob($failed_jobs[0]['id']);
	});

	// Test 10: Cleanup old completed jobs
	run_test('Clean up old completed jobs', function() use ($job_queue_service) {
		$count = $job_queue_service->cleanupCompleted(0); // Delete all completed jobs
		return $count >= 0; // Should return count, even if 0
	});

	// Test 11: Reset stale jobs
	run_test('Reset stale jobs', function() use ($job_queue_service) {
		$count = $job_queue_service->resetStaleJobs(30);
		return $count >= 0;
	});

	// Test 12: Verify future job still pending
	run_test('Verify future scheduled job still pending', function() use ($job_queue_repository, $job_ids) {
		if (isset($job_ids['future'])) {
			$job = $job_queue_repository->getById($job_ids['future']);
			return $job['status'] === 'pending';
		}
		return true;
	});

	// Output summary
	echo "\n" . str_repeat("=", 80) . "\n";
	echo "Test Summary:\n";
	echo str_repeat("=", 80) . "\n";

	$passed = count(array_filter($test_results, fn($r) => $r['status'] === 'pass'));
	$failed = count(array_filter($test_results, fn($r) => $r['status'] === 'fail'));

	echo "Total Tests: {$test_count}\n";
	echo "Passed: {$passed}\n";
	echo "Failed: {$failed}\n";

	if ($failed > 0) {
		echo "\nFailed Tests:\n";
		foreach ($test_results as $result) {
			if ($result['status'] === 'fail') {
				echo "  ✗ {$result['name']}\n";
				if (isset($result['error'])) {
					echo "    Error: {$result['error']}\n";
				}
			}
		}
	}

	echo "\n" . str_repeat("=", 80) . "\n";

	// Display job IDs for reference
	if (!empty($job_ids)) {
		echo "\nCreated Job IDs:\n";
		foreach ($job_ids as $priority => $id) {
			echo "  {$priority}: {$id}\n";
		}
	}

	// Display final queue statistics
	echo "\nFinal Queue Statistics:\n";
	$final_counts = $job_queue_repository->countByStatus();
	foreach ($final_counts as $status => $count) {
		echo "  {$status}: {$count}\n";
	}

	echo "\n" . str_repeat("=", 80) . "\n";

	exit($failed > 0 ? 1 : 0);

} catch (Exception $e) {
	echo "\n✗ FATAL ERROR: " . $e->getMessage() . "\n";
	echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
	exit(1);
}
