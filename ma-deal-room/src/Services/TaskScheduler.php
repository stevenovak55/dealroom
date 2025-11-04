<?php
/**
 * Task Scheduler Service
 *
 * @package MADealRoom\Services
 * @since 1.0.0
 */

namespace MADealRoom\Services;

use MADealRoom\Repositories\TaskRepository;

class TaskScheduler {
	private $task_repository;

	public function __construct(TaskRepository $task_repository) {
		$this->task_repository = $task_repository;
	}

	/**
	 * Calculate due dates for tasks based on transaction dates
	 *
	 * @param array $tasks Tasks to calculate dates for
	 * @param object $transaction Transaction model
	 * @return array Tasks with calculated due dates
	 */
	public function calculateDueDates(array $tasks, $transaction): array {
		// This will handle relative date calculations
		// e.g., "Closing-21d" becomes actual date
		$updated_tasks = [];

		foreach ($tasks as $task) {
			$metadata = json_decode($task->metadata, true);
			$relative_due_date = $metadata['relative_due_date'] ?? null;

			if ($relative_due_date) {
				$key_dates = [
					'closing_date' => $transaction->closing_date,
					'ps_date' => $transaction->ps_agreement_date,
					'listing_date' => $transaction->listing_date,
				];

				$calculated_due_date = $this->calculateSingleDueDate($relative_due_date, $key_dates);
				if ($calculated_due_date) {
					$task->due_at = $calculated_due_date;
				}
			}
			$updated_tasks[] = $task;
		}

		return $updated_tasks;
	}

	/**
	 * Calculate a single due date from relative offset
	 *
	 * @param string $relative_date Relative date string (e.g., "Closing-21d")
	 * @param array $key_dates Key dates for calculation
	 * @return string|null Calculated due date in MySQL format
	 */
	private function calculateSingleDueDate(string $relative_date, array $key_dates): ?string {
		// Handle special case: exact date anchor with no offset
		// This logic is duplicated from TemplateEngine, consider centralizing
		if (preg_match('/^(Closing|PS|Listing|FirstMeeting)$/', $relative_date, $matches)) {
			$anchor = $matches[1];
			$anchor_date = $this->getAnchorDate($anchor, $key_dates);

			if (!$anchor_date) {
				return null;
			}

			return date('Y-m-d H:i:s', strtotime($anchor_date));
		}

		// Parse format: "Closing-21d" or "PS+7d" or "Listing+0d"
		if (preg_match('/^(Closing|PS|Listing|FirstMeeting)([\+\-])(\d+)(d|h|m)$/', $relative_date, $matches)) {
			$anchor = $matches[1];
			$operator = $matches[2];
			$amount = (int)$matches[3];
			$unit = $matches[4];

			$anchor_date = $this->getAnchorDate($anchor, $key_dates);

			if (!$anchor_date) {
				return null;
			}

			$timestamp = strtotime($anchor_date);
			$multiplier = [
				'd' => 86400, // seconds in a day
				'h' => 3600,  // seconds in an hour
				'm' => 60,    // seconds in a minute
			][$unit];

			if ($operator === '+') {
				$timestamp += $amount * $multiplier;
			} else {
				$timestamp -= $amount * $multiplier;
			}

			return date('Y-m-d H:i:s', $timestamp);
		}

		return null;
	}

	/**
	 * Get anchor date from key dates array
	 *
	 * @param string $anchor Anchor name
	 * @param array $key_dates Key dates array
	 * @return string|null Anchor date
	 */
	private function getAnchorDate(string $anchor, array $key_dates): ?string {
		$mapping = [
			'Closing' => 'closing_date',
			'PS' => 'ps_date',
			'Listing' => 'listing_date',
			'FirstMeeting' => 'listing_date', // Use listing as proxy for first meeting
		];

		$key = $mapping[$anchor] ?? null;

		return $key && isset($key_dates[$key]) ? $key_dates[$key] : null;
	}
}
