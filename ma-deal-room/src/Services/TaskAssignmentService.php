<?php
/**
 * Task Assignment Service
 *
 * @package MADealRoom\Services
 * @since 1.0.0
 */

namespace MADealRoom\Services;

use MADealRoom\Repositories\TaskRepository;
use MADealRoom\Repositories\PartyRepository;
use MADealRoom\Repositories\TransactionRepository;
use MADealRoom\Services\EmailService;
use MADealRoom\Services\NotificationQueueService;

/**
 * Service for automatically assigning tasks to parties
 */
class TaskAssignmentService {
	private TaskRepository $task_repository;
	private PartyRepository $party_repository;
	private TransactionRepository $transaction_repository;
	private ?EmailService $email_service;
	private ?NotificationQueueService $queue_service;

	/**
	 * Constructor
	 */
	public function __construct(
		TaskRepository $task_repository,
		PartyRepository $party_repository,
		TransactionRepository $transaction_repository,
		EmailService $email_service = null,
		NotificationQueueService $queue_service = null
	) {
		$this->task_repository = $task_repository;
		$this->party_repository = $party_repository;
		$this->transaction_repository = $transaction_repository;
		$this->email_service = $email_service;
		$this->queue_service = $queue_service ?? new NotificationQueueService($email_service);
	}

	/**
	 * Map task owner_role to party roles
	 *
	 * @param string $owner_role Task owner role
	 * @return array Array of matching party roles
	 */
	private function mapOwnerRoleToPartyRoles(string $owner_role): array {
		$role_mapping = [
			'agent' => ['buyer_agent', 'seller_agent'],
			'seller' => ['seller'],
			'buyer' => ['buyer'],
			'seller_attorney' => ['seller_attorney'],
			'buyer_attorney' => ['buyer_attorney'],
			'vendor' => ['inspector', 'appraiser', 'title_company', 'septic_inspector'],
			'system' => [], // System tasks are not assigned to parties
		];

		return $role_mapping[$owner_role] ?? [];
	}

	/**
	 * Auto-assign tasks for a transaction based on parties
	 *
	 * @param int $transaction_id Transaction ID
	 * @return array Results of assignment attempts
	 */
	public function autoAssignTasksForTransaction(int $transaction_id): array {
		$results = [];

		// Get all unassigned tasks for the transaction
		$tasks = $this->task_repository->findByTransaction($transaction_id);
		$unassigned_tasks = array_filter($tasks, function($task) {
			// Auto-assign any unassigned task regardless of status
			return $task->assigned_party_id === null;
		});

		if (empty($unassigned_tasks)) {
			return ['message' => 'No unassigned tasks found'];
		}

		// Get all parties for the transaction
		$parties = $this->party_repository->findByTransaction($transaction_id);

		if (empty($parties)) {
			return ['message' => 'No parties found for auto-assignment'];
		}

		// Create a mapping of roles to parties for quick lookup
		$role_to_parties = [];
		foreach ($parties as $party) {
			if (!isset($role_to_parties[$party->role])) {
				$role_to_parties[$party->role] = [];
			}
			$role_to_parties[$party->role][] = $party;
		}

		// Attempt to assign each unassigned task
		foreach ($unassigned_tasks as $task) {
			$matching_party_roles = $this->mapOwnerRoleToPartyRoles($task->owner_role);

			if (empty($matching_party_roles)) {
				$results[] = [
					'task_id' => $task->id,
					'task_title' => $task->title,
					'status' => 'skipped',
					'reason' => "No party role mapping for owner_role: {$task->owner_role}"
				];
				continue;
			}

			// Find the first matching party
			$assigned = false;
			foreach ($matching_party_roles as $party_role) {
				if (isset($role_to_parties[$party_role]) && !empty($role_to_parties[$party_role])) {
					// Assign to the first party with matching role
					$party = $role_to_parties[$party_role][0];

					$success = $this->task_repository->update($task->id, [
						'assigned_party_id' => $party->id
					]);

					if ($success) {
						// Queue notification for batched sending
						if ($this->queue_service && $party->email) {
							$transaction = $this->transaction_repository->find($transaction_id);
							$updated_task = $this->task_repository->find($task->id);

							// Get user ID from party email
							$user = get_user_by('email', $party->email);
							if ($user) {
								// Queue the notification with all necessary data
								$this->queue_service->queue(
									$user->ID,
									'task_assigned',
									[
										'task' => $updated_task,
										'transaction' => $transaction,
										'party' => $party,
									]
								);
							}
						}

						$results[] = [
							'task_id' => $task->id,
							'task_title' => $task->title,
							'status' => 'assigned',
							'assigned_to' => $party->contact_name,
							'party_role' => $party->role
						];
						$assigned = true;
						break;
					}
				}
			}

			if (!$assigned) {
				$results[] = [
					'task_id' => $task->id,
					'task_title' => $task->title,
					'status' => 'not_assigned',
					'reason' => 'No matching party found for roles: ' . implode(', ', $matching_party_roles)
				];
			}
		}

		return $results;
	}

	/**
	 * Auto-assign tasks when a new party is added
	 *
	 * @param int $party_id Party ID
	 * @return array Results of assignment attempts
	 */
	public function autoAssignTasksToNewParty(int $party_id): array {
		$results = [];

		// Get the party
		$party = $this->party_repository->find($party_id);
		if (!$party) {
			return ['error' => 'Party not found'];
		}

		// Get all unassigned tasks for the party's transaction
		$tasks = $this->task_repository->findByTransaction($party->transaction_id);
		$unassigned_tasks = array_filter($tasks, function($task) {
			return $task->assigned_party_id === null && $task->status === 'pending';
		});

		if (empty($unassigned_tasks)) {
			return ['message' => 'No unassigned tasks found'];
		}

		// Find which owner_roles match this party's role
		$matching_owner_roles = [];
		foreach (['agent', 'seller', 'buyer', 'seller_attorney', 'buyer_attorney', 'vendor'] as $owner_role) {
			$party_roles = $this->mapOwnerRoleToPartyRoles($owner_role);
			if (in_array($party->role, $party_roles)) {
				$matching_owner_roles[] = $owner_role;
			}
		}

		if (empty($matching_owner_roles)) {
			return ['message' => "No tasks match party role: {$party->role}"];
		}

		// Assign tasks with matching owner_role to this party
		foreach ($unassigned_tasks as $task) {
			if (in_array($task->owner_role, $matching_owner_roles)) {
				$success = $this->task_repository->update($task->id, [
					'assigned_party_id' => $party->id
				]);

				if ($success) {
					// Queue notification for batched sending
					if ($this->queue_service && $party->email) {
						$transaction = $this->transaction_repository->find($party->transaction_id);
						$updated_task = $this->task_repository->find($task->id);

						// Get user ID from party email
						$user = get_user_by('email', $party->email);
						if ($user) {
							// Queue the notification with all necessary data
							$this->queue_service->queue(
								$user->ID,
								'task_assigned',
								[
									'task' => $updated_task,
									'transaction' => $transaction,
									'party' => $party,
								]
							);
						}
					}

					$results[] = [
						'task_id' => $task->id,
						'task_title' => $task->title,
						'status' => 'assigned',
						'assigned_to' => $party->contact_name
					];
				}
			}
		}

		if (empty($results)) {
			return ['message' => 'No tasks were assigned'];
		}

		return $results;
	}

	/**
	 * Check if auto-assignment is needed and perform it
	 * This is called after transaction creation or party addition
	 *
	 * @param int $transaction_id Transaction ID
	 * @param bool $send_notifications Whether to send notifications for assignments
	 * @return array Assignment results
	 */
	public function performAutoAssignment(int $transaction_id, bool $send_notifications = true): array {
		$results = $this->autoAssignTasksForTransaction($transaction_id);

		// If notifications are enabled and tasks were assigned, trigger notifications
		if ($send_notifications && !empty($results)) {
			$assigned_tasks = array_filter($results, function($result) {
				return isset($result['status']) && $result['status'] === 'assigned';
			});

			if (!empty($assigned_tasks)) {
				// Notification sending would be handled by the EmailService
				// This is just a placeholder for where the integration would go
				foreach ($assigned_tasks as $assignment) {
					// The reassign_task endpoint in TaskController already handles notifications
					// So we don't need to duplicate that logic here
				}
			}
		}

		return $results;
	}
}