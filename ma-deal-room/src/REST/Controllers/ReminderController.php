<?php
/**
 * Reminder REST Controller
 *
 * @package MADealRoom\REST\Controllers
 * @since 1.0.0
 */

namespace MADealRoom\REST\Controllers;

use MADealRoom\Repositories\ReminderRepository;
use MADealRoom\Repositories\EventRepository;
use MADealRoom\Repositories\TaskRepository;
use MADealRoom\Repositories\TransactionRepository;
use WP_REST_Request;

class ReminderController extends BaseController {
	protected $rest_base = 'reminders';
	private $repository;
	private $task_repository;
	private $transaction_repository;

	public function __construct(ReminderRepository $repository, TaskRepository $task_repository, TransactionRepository $transaction_repository, EventRepository $event_repository) {
		parent::__construct($event_repository);
		$this->repository = $repository;
		$this->task_repository = $task_repository;
		$this->transaction_repository = $transaction_repository;
	}

	public function register_routes(): void {
		register_rest_route($this->namespace, '/' . $this->rest_base . '/upcoming', [
			['methods' => 'GET', 'callback' => [$this, 'get_upcoming'], 'permission_callback' => [$this, 'permission_callback']],
		]);
	}

	public function get_upcoming(WP_REST_Request $request) {
		$limit = $request->get_param('limit') ?? 50;
		$all_reminders = $this->repository->findPending($limit * 10); // Get more to filter

		// SECURITY: Filter reminders by account access
		$user_account_id = $this->get_user_account_id();
		$filtered_reminders = [];

		foreach ($all_reminders as $reminder) {
			// Admins see all reminders
			if (current_user_can('manage_options')) {
				$filtered_reminders[] = $reminder;
				continue;
			}

			// Get task to check account ownership
			if ($reminder->task_id) {
				$task = $this->task_repository->find($reminder->task_id);
				if ($task) {
					$transaction = $this->transaction_repository->find($task->transaction_id);
					if ($transaction && $transaction->account_id === $user_account_id) {
						$filtered_reminders[] = $reminder;
					}
				}
			}

			// Stop when we have enough reminders
			if (count($filtered_reminders) >= $limit) {
				break;
			}
		}

		return $this->success(array_slice($filtered_reminders, 0, $limit));
	}
}
