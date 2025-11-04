<?php
/**
 * Test Retry Mechanism
 *
 * Comprehensive test for job queue retry functionality
 *
 * @package MADealRoom
 * @since 1.0.0
 */

require_once('/var/www/html/wp-load.php');

use MADealRoom\Services\Queue\Jobs\TestFailingJob;
use MADealRoom\Services\Queue\Strategies\ExponentialRetryStrategy;
use MADealRoom\Services\Queue\Strategies\LinearRetryStrategy;
use MADealRoom\Services\Queue\Strategies\ImmediateRetryStrategy;

echo "MA Deal Room - Retry Mechanism Test\n";
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

	// Test 1: Verify next_retry_at column exists
	run_test('Verify next_retry_at column exists', function() use ($job_queue_repository) {
		global $wpdb;
		$columns = $wpdb->get_results("DESCRIBE wp_ma_deal_job_queue", ARRAY_A);
		$has_column = false;
		foreach ($columns as $col) {
			if ($col['Field'] === 'next_retry_at') {
				$has_column = true;
				break;
			}
		}
		return $has_column;
	});

	// Test 2: Test ExponentialRetryStrategy
	run_test('ExponentialRetryStrategy calculates delays correctly', function() {
		$strategy = new ExponentialRetryStrategy(60, 2.0, 3600, false);

		// Attempt 1: 60s
		$delay1 = $strategy->calculateDelay(1, 3);
		// Attempt 2: 120s (60 * 2^1)
		$delay2 = $strategy->calculateDelay(2, 3);
		// Attempt 3: 240s (60 * 2^2)
		$delay3 = $strategy->calculateDelay(3, 3);

		return $delay1 === 60 && $delay2 === 120 && $delay3 === 240;
	});

	// Test 3: Test LinearRetryStrategy
	run_test('LinearRetryStrategy calculates delays correctly', function() {
		$strategy = new LinearRetryStrategy(60, 900);

		// Attempt 1: 60s (1 * 60)
		$delay1 = $strategy->calculateDelay(1, 5);
		// Attempt 2: 120s (2 * 60)
		$delay2 = $strategy->calculateDelay(2, 5);
		// Attempt 3: 180s (3 * 60)
		$delay3 = $strategy->calculateDelay(3, 5);

		return $delay1 === 60 && $delay2 === 120 && $delay3 === 180;
	});

	// Test 4: Test ImmediateRetryStrategy
	run_test('ImmediateRetryStrategy returns zero delay', function() {
		$strategy = new ImmediateRetryStrategy();

		$delay1 = $strategy->calculateDelay(1, 3);
		$delay2 = $strategy->calculateDelay(2, 3);

		return $delay1 === 0 && $delay2 === 0;
	});

	// Register test job handler
	$job_queue_service->registerHandler('test.failing', TestFailingJob::class);

	// Test 5: Dispatch failing job with exponential backoff
	$job_ids = [];
	run_test('Dispatch failing job with exponential backoff', function() use ($job_queue_service, &$job_ids) {
		$job = new TestFailingJob('Exponential Test', 2, 'exponential', 'high');
		$job_id = $job_queue_service->dispatch($job);
		if ($job_id) {
			$job_ids['exponential'] = $job_id;
		}
		return $job_id !== false;
	});

	// Test 6: Dispatch failing job with linear backoff
	run_test('Dispatch failing job with linear backoff', function() use ($job_queue_service, &$job_ids) {
		$job = new TestFailingJob('Linear Test', 2, 'linear', 'normal');
		$job_id = $job_queue_service->dispatch($job);
		if ($job_id) {
			$job_ids['linear'] = $job_id;
		}
		return $job_id !== false;
	});

	// Test 7: Dispatch failing job with immediate retry
	run_test('Dispatch failing job with immediate retry', function() use ($job_queue_service, &$job_ids) {
		$job = new TestFailingJob('Immediate Test', 1, 'immediate', 'low');
		$job_id = $job_queue_service->dispatch($job);
		if ($job_id) {
			$job_ids['immediate'] = $job_id;
		}
		return $job_id !== false;
	});

	// Test 8: Process jobs (they will all fail on first attempt)
	run_test('Process pending jobs (first attempt - all fail)', function() use ($job_queue_service) {
		$results = $job_queue_service->processPending(10);
		// All 3 jobs should be processed and all should fail
		return $results['processed'] === 3 && $results['failed'] === 3;
	});

	// Test 9: Verify jobs are back in pending status with next_retry_at set
	run_test('Verify failed jobs marked for retry with delays', function() use ($job_queue_repository, $job_ids) {
		$expo_job = $job_queue_repository->getById($job_ids['exponential']);
		$linear_job = $job_queue_repository->getById($job_ids['linear']);
		$immediate_job = $job_queue_repository->getById($job_ids['immediate']);

		// All should be pending (will retry)
		$all_pending = ($expo_job['status'] === 'pending') &&
		               ($linear_job['status'] === 'pending') &&
		               ($immediate_job['status'] === 'pending');

		// Exponential and linear should have next_retry_at set
		$has_retry_times = !empty($expo_job['next_retry_at']) &&
		                   !empty($linear_job['next_retry_at']);

		// Immediate should NOT have next_retry_at (retry immediately)
		$immediate_no_delay = empty($immediate_job['next_retry_at']);

		return $all_pending && $has_retry_times && $immediate_no_delay;
	});

	// Test 10: Verify attempt counts incremented
	run_test('Verify attempt counts incremented', function() use ($job_queue_repository, $job_ids) {
		$expo_job = $job_queue_repository->getById($job_ids['exponential']);
		$linear_job = $job_queue_repository->getById($job_ids['linear']);
		$immediate_job = $job_queue_repository->getById($job_ids['immediate']);

		// All should have attempts = 1
		return ($expo_job['attempts'] == 1) &&
		       ($linear_job['attempts'] == 1) &&
		       ($immediate_job['attempts'] == 1);
	});

	// Test 11: Try processing again (only immediate retry job should be available)
	run_test('Only immediate retry job available for processing', function() use ($job_queue_service) {
		$results = $job_queue_service->processPending(10);
		// Only immediate retry job should process (others have future next_retry_at)
		return $results['processed'] === 1;
	});

	// Test 12: Verify immediate retry job is now dead (exceeded max attempts)
	run_test('Immediate retry job marked as dead', function() use ($job_queue_repository, $job_ids) {
		$immediate_job = $job_queue_repository->getById($job_ids['immediate']);
		// Should be dead (attempts = 2, max_attempts = 2)
		return $immediate_job['status'] === 'dead';
	});

	// Test 13: Manually retry a job (should clear next_retry_at)
	run_test('Manual retry clears next_retry_at', function() use ($job_queue_service, $job_queue_repository, $job_ids) {
		$job_queue_service->retryJob($job_ids['exponential']);
		$job = $job_queue_repository->getById($job_ids['exponential']);
		return $job['next_retry_at'] === null && $job['status'] === 'pending';
	});

	// Test 14: Verify exponential backoff timing
	run_test('Exponential backoff next_retry_at is ~60s in future', function() use ($job_queue_repository, $job_ids) {
		// First, dispatch a new job and fail it
		$service = \MADealRoom\Core\Plugin::instance()->container()->get('job_queue_service');
		$job = new TestFailingJob('Timing Test', 3, 'exponential', 'normal');
		$job_id = $service->dispatch($job);

		// Process it (will fail)
		$service->processPending(1);

		// Check next_retry_at
		$job_data = $job_queue_repository->getById($job_id);
		$next_retry = strtotime($job_data['next_retry_at']);
		$now = time();
		$diff = $next_retry - $now;

		// Should be approximately 60 seconds (allow 5s tolerance)
		return $diff >= 55 && $diff <= 65;
	});

	// Test 15: Verify linear backoff timing
	run_test('Linear backoff next_retry_at is ~60s in future', function() use ($job_queue_repository) {
		// Dispatch and fail a linear backoff job
		$service = \MADealRoom\Core\Plugin::instance()->container()->get('job_queue_service');
		$job = new TestFailingJob('Linear Timing Test', 3, 'linear', 'normal');
		$job_id = $service->dispatch($job);

		// Process it (will fail)
		$service->processPending(1);

		// Check next_retry_at
		$job_data = $job_queue_repository->getById($job_id);
		$next_retry = strtotime($job_data['next_retry_at']);
		$now = time();
		$diff = $next_retry - $now;

		// Should be approximately 60 seconds (allow 5s tolerance)
		return $diff >= 55 && $diff <= 65;
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

	// Display job details
	if (!empty($job_ids)) {
		echo "\nCreated Job IDs:\n";
		foreach ($job_ids as $type => $id) {
			$job = $job_queue_repository->getById($id);
			echo "  {$type}: {$id} - Status: {$job['status']}, Attempts: {$job['attempts']}, Max: {$job['max_attempts']}";
			if (!empty($job['next_retry_at'])) {
				echo ", Next Retry: {$job['next_retry_at']}";
			}
			echo "\n";
		}
	}

	echo "\n" . str_repeat("=", 80) . "\n";

	exit($failed > 0 ? 1 : 0);

} catch (Exception $e) {
	echo "\n✗ FATAL ERROR: " . $e->getMessage() . "\n";
	echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
	exit(1);
}
