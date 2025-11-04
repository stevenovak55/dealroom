<?php
/**
 * Vendor Service
 *
 * @package MADealRoom\Services
 * @since 1.0.0
 */

namespace MADealRoom\Services;

use MADealRoom\Repositories\VendorRequestRepository;
use MADealRoom\Repositories\VendorMessageRepository;
use MADealRoom\Repositories\VendorAvailabilityRepository;
use MADealRoom\Repositories\VendorRatingRepository;
use MADealRoom\Repositories\TransactionRepository;
use MADealRoom\Repositories\TaskRepository;
use MADealRoom\Services\EmailService;
use MADealRoom\Services\FileStorageService;

/**
 * Vendor coordination service
 */
class VendorService {
	private $vendor_request_repository;
	private $vendor_message_repository;
	private $vendor_availability_repository;
	private $vendor_rating_repository;
	private $transaction_repository;
	private $task_repository;
	private $email_service;
	private $file_storage_service;

	public function __construct(
		VendorRequestRepository $vendor_request_repository,
		VendorMessageRepository $vendor_message_repository,
		VendorAvailabilityRepository $vendor_availability_repository,
		VendorRatingRepository $vendor_rating_repository,
		TransactionRepository $transaction_repository,
		TaskRepository $task_repository,
		EmailService $email_service,
		FileStorageService $file_storage_service
	) {
		$this->vendor_request_repository = $vendor_request_repository;
		$this->vendor_message_repository = $vendor_message_repository;
		$this->vendor_availability_repository = $vendor_availability_repository;
		$this->vendor_rating_repository = $vendor_rating_repository;
		$this->transaction_repository = $transaction_repository;
		$this->task_repository = $task_repository;
		$this->email_service = $email_service;
		$this->file_storage_service = $file_storage_service;
	}

	/**
	 * Generate a signed URL token
	 *
	 * @param int $request_id Vendor request ID
	 * @param int $expiration_days Days until expiration
	 * @return string Signed token
	 */
	public function generateSignedUrl(int $request_id, int $expiration_days = 30): string {
		// Generate secure random token
		$token = bin2hex(random_bytes(32));

		// Store token and expiration in database
		$expires_at = date('Y-m-d H:i:s', strtotime("+{$expiration_days} days"));

		$this->vendor_request_repository->update($request_id, [
			'token' => $token,
			'token_expires_at' => $expires_at,
		]);

		return $token;
	}

	/**
	 * Validate a signed token
	 *
	 * @param string $token Token to validate
	 * @return object|null Vendor request if valid, null otherwise
	 */
	public function validateToken(string $token): ?object {
		$vendor_request = $this->vendor_request_repository->findByToken($token);

		if (!$vendor_request) {
			return null;
		}

		// Check expiration
		if (strtotime($vendor_request->token_expires_at) < time()) {
			$this->vendor_request_repository->updateStatus($vendor_request->id, 'expired');
			return null;
		}

		// Update last opened timestamp
		$this->vendor_request_repository->update($vendor_request->id, [
			'last_opened_at' => current_time('mysql'),
		]);

		// Update status to opened if currently sent
		if ($vendor_request->status === 'sent') {
			$this->vendor_request_repository->updateStatus($vendor_request->id, 'opened');
		}

		return $vendor_request;
	}

	/**
	 * Generate ICS calendar file
	 *
	 * @param array $event_data Event data
	 * @return string ICS file content
	 */
	public function generateICS(array $event_data): string {
		$ics = "BEGIN:VCALENDAR\r\n";
		$ics .= "VERSION:2.0\r\n";
		$ics .= "PRODID:-//MA Deal Room//EN\r\n";
		$ics .= "CALSCALE:GREGORIAN\r\n";
		$ics .= "BEGIN:VEVENT\r\n";
		$ics .= "UID:" . ($event_data['uid'] ?? uniqid()) . "@madealroom.com\r\n";
		$ics .= "DTSTAMP:" . gmdate('Ymd\THis\Z') . "\r\n";

		// Summary
		$ics .= "SUMMARY:" . $this->escapeICSString($event_data['summary'] ?? 'Event') . "\r\n";

		// Start and End Times
		$start_time = $event_data['start'] ?? null;
		$end_time = $event_data['end'] ?? null;

		if ($start_time) {
			$ics .= "DTSTART:" . gmdate('Ymd\THis\Z', strtotime($start_time)) . "\r\n";
		}
		if ($end_time) {
			$ics .= "DTEND:" . gmdate('Ymd\THis\Z', strtotime($end_time)) . "\r\n";
		} else if ($start_time) {
			// If only start time is provided, assume 1 hour duration
			$ics .= "DTEND:" . gmdate('Ymd\THis\Z', strtotime($start_time . ' +1 hour')) . "\r\n";
		}

		// Description
		if (isset($event_data['description'])) {
			$ics .= "DESCRIPTION:" . $this->escapeICSString($event_data['description']) . "\r\n";
		}

		// Location
		if (isset($event_data['location'])) {
			$ics .= "LOCATION:" . $this->escapeICSString($event_data['location']) . "\r\n";
		}

		// URL
		if (isset($event_data['url'])) {
			$ics .= "URL:" . $this->escapeICSString($event_data['url']) . "\r\n";
		}

		// Organizer
		if (isset($event_data['organizer_name']) && isset($event_data['organizer_email'])) {
			$ics .= "ORGANIZER;CN=" . $this->escapeICSString($event_data['organizer_name']) . ":mailto:" . $this->escapeICSString($event_data['organizer_email']) . "\r\n";
		}

		$ics .= "END:VEVENT\r\n";
		$ics .= "END:VCALENDAR\r\n";

		return $ics;
	}

	/**
	 * Escape special characters for ICS format
	 *
	 * @param string $string Input string
	 * @return string Escaped string
	 */
	private function escapeICSString(string $string): string {
		$string = str_replace('\\', '\\\\', $string);
		$string = str_replace(';', '\\;', $string);
		$string = str_replace(',', '\\,', $string);
		$string = str_replace("\r\n", '\\n', $string);
		return $string;
	}

	/**
	 * Update vendor request with scheduling information
	 *
	 * @param int $request_id Vendor request ID
	 * @param array $schedule_data Schedule data (scheduled_date, scheduled_time)
	 * @return bool
	 */
	public function scheduleAppointment(int $request_id, array $schedule_data): bool {
		$update_data = [];

		if (isset($schedule_data['scheduled_date'])) {
			$update_data['scheduled_date'] = $schedule_data['scheduled_date'];
		}

		if (isset($schedule_data['scheduled_time'])) {
			$update_data['scheduled_time'] = $schedule_data['scheduled_time'];
		}

		if (isset($schedule_data['vendor_name'])) {
			$update_data['vendor_name'] = $schedule_data['vendor_name'];
		}

		if (isset($schedule_data['vendor_company'])) {
			$update_data['vendor_company'] = $schedule_data['vendor_company'];
		}

		// Update status to scheduled if date is provided
		if (isset($schedule_data['scheduled_date'])) {
			$update_data['status'] = 'scheduled';
			$update_data['confirmation_sent_at'] = current_time('mysql');
		}

		$result = $this->vendor_request_repository->update($request_id, $update_data);

		// Send confirmation email to agent
		if ($result && isset($schedule_data['scheduled_date'])) {
			$this->sendScheduleConfirmationEmail($request_id);
		}

		return $result;
	}

	/**
	 * Complete a vendor request
	 *
	 * @param int $request_id Vendor request ID
	 * @param array $completion_data Completion data (notes, document)
	 * @return bool
	 */
	public function completeRequest(int $request_id, array $completion_data): bool {
		$update_data = [
			'status' => 'completed',
			'completion_date' => $completion_data['completion_date'] ?? date('Y-m-d'),
		];

		if (isset($completion_data['completion_notes'])) {
			$update_data['completion_notes'] = $completion_data['completion_notes'];
		}

		if (isset($completion_data['document_url'])) {
			$update_data['document_url'] = $completion_data['document_url'];
		}

		$result = $this->vendor_request_repository->update($request_id, $update_data);

		// Auto-complete associated task if it exists
		if ($result) {
			$vendor_request = $this->vendor_request_repository->findById($request_id);
			if ($vendor_request && $vendor_request->task_id) {
				try {
					$task = $this->task_repository->findById($vendor_request->task_id);
					if ($task && $task->status !== 'completed') {
						$this->task_repository->update($vendor_request->task_id, [
							'status' => 'completed',
							'completed_at' => current_time('mysql'),
						]);
					}
				} catch (\Exception $e) {
					error_log("VendorService: Failed to auto-complete task {$vendor_request->task_id}: " . $e->getMessage());
				}
			}

			// Send completion notification to agent
			$this->sendCompletionNotificationEmail($request_id);
		}

		return $result;
	}

	/**
	 * Send message from vendor to agent or vice versa
	 *
	 * @param int $vendor_request_id Vendor request ID
	 * @param string $sender_type 'vendor' or 'agent'
	 * @param string $sender_name Sender name
	 * @param string $sender_email Sender email
	 * @param string $message Message content
	 * @return int|false Message ID or false
	 */
	public function sendMessage(int $vendor_request_id, string $sender_type, string $sender_name, string $sender_email, string $message) {
		$vendor_request = $this->vendor_request_repository->findById($vendor_request_id);

		if (!$vendor_request) {
			return false;
		}

		$message_id = $this->vendor_message_repository->createMessage([
			'vendor_request_id' => $vendor_request_id,
			'transaction_id' => $vendor_request->transaction_id,
			'sender_type' => $sender_type,
			'sender_name' => $sender_name,
			'sender_email' => $sender_email,
			'message' => $message,
		]);

		// Send email notification to recipient
		if ($message_id) {
			$this->sendMessageNotificationEmail($vendor_request_id, $sender_type, $sender_name, $message);
		}

		return $message_id;
	}

	/**
	 * Submit vendor availability windows
	 *
	 * @param int $vendor_request_id Vendor request ID
	 * @param array $availability_windows Array of availability data
	 * @return bool
	 */
	public function submitAvailability(int $vendor_request_id, array $availability_windows): bool {
		// Clear existing availability
		$this->vendor_availability_repository->clearAvailability($vendor_request_id);

		// Add new availability windows
		foreach ($availability_windows as $window) {
			$window['vendor_request_id'] = $vendor_request_id;
			$this->vendor_availability_repository->addAvailability($window);
		}

		// Notify agent of availability submission
		$this->sendAvailabilityNotificationEmail($vendor_request_id);

		return true;
	}

	/**
	 * Upload completion document
	 *
	 * @param int $vendor_request_id Vendor request ID
	 * @param array $file_data Uploaded file data
	 * @return string|false Document URL or false
	 */
	public function uploadDocument(int $vendor_request_id, array $file_data) {
		$vendor_request = $this->vendor_request_repository->findById($vendor_request_id);

		if (!$vendor_request) {
			return false;
		}

		// Store file
		$result = $this->file_storage_service->storeFile(
			$file_data,
			'vendor_documents',
			[
				'vendor_request_id' => $vendor_request_id,
				'transaction_id' => $vendor_request->transaction_id,
			]
		);

		if ($result && isset($result['url'])) {
			// Update vendor request with document URL
			$this->vendor_request_repository->update($vendor_request_id, [
				'document_url' => $result['url'],
			]);

			return $result['url'];
		}

		return false;
	}

	/**
	 * Get vendor portal data
	 *
	 * @param string $token Vendor token
	 * @return array|null Portal data or null if invalid
	 */
	public function getVendorPortalData(string $token): ?array {
		$vendor_request = $this->validateToken($token);

		if (!$vendor_request) {
			return null;
		}

		// Get transaction details (limited info for vendor)
		$transaction = $this->transaction_repository->findById($vendor_request->transaction_id);

		// Get messages
		$messages = $this->vendor_message_repository->getThreadWithMetadata($vendor_request->id);

		// Get availability
		$availability = $this->vendor_availability_repository->getByVendorRequest($vendor_request->id, false);

		// Get rating (if completed)
		$rating = null;
		if ($vendor_request->status === 'completed') {
			$rating = $this->vendor_rating_repository->getByVendorRequest($vendor_request->id);
		}

		return [
			'vendor_request' => $vendor_request,
			'transaction' => [
				'id' => $transaction->id,
				'property_address' => $transaction->property_address,
				'property_city' => $transaction->property_city,
				'property_state' => $transaction->property_state,
				'property_zip' => $transaction->property_zip,
				'closing_date' => $transaction->closing_date,
			],
			'messages' => $messages,
			'availability' => $availability,
			'rating' => $rating,
		];
	}

	/**
	 * Send schedule confirmation email to agent
	 *
	 * @param int $vendor_request_id Vendor request ID
	 * @return void
	 */
	private function sendScheduleConfirmationEmail(int $vendor_request_id): void {
		try {
			$vendor_request = $this->vendor_request_repository->findById($vendor_request_id);
			if (!$vendor_request) {
				error_log("VendorService: Vendor request not found: {$vendor_request_id}");
				return;
			}

			$transaction = $this->transaction_repository->findById($vendor_request->transaction_id);
			if (!$transaction) {
				error_log("VendorService: Transaction not found: {$vendor_request->transaction_id}");
				return;
			}

			$this->email_service->sendVendorScheduleConfirmation($vendor_request, $transaction);
		} catch (\Exception $e) {
			error_log("VendorService: Failed to send schedule confirmation email: " . $e->getMessage());
		}
	}

	/**
	 * Send completion notification email to agent
	 *
	 * @param int $vendor_request_id Vendor request ID
	 * @return void
	 */
	private function sendCompletionNotificationEmail(int $vendor_request_id): void {
		try {
			$vendor_request = $this->vendor_request_repository->findById($vendor_request_id);
			if (!$vendor_request) {
				error_log("VendorService: Vendor request not found: {$vendor_request_id}");
				return;
			}

			$transaction = $this->transaction_repository->findById($vendor_request->transaction_id);
			if (!$transaction) {
				error_log("VendorService: Transaction not found: {$vendor_request->transaction_id}");
				return;
			}

			$this->email_service->sendVendorCompletionNotification($vendor_request, $transaction);
		} catch (\Exception $e) {
			error_log("VendorService: Failed to send completion notification email: " . $e->getMessage());
		}
	}

	/**
	 * Send message notification email
	 *
	 * @param int $vendor_request_id Vendor request ID
	 * @param string $sender_type Sender type
	 * @param string $sender_name Sender name
	 * @param string $message Message content
	 * @return void
	 */
	private function sendMessageNotificationEmail(int $vendor_request_id, string $sender_type, string $sender_name, string $message): void {
		try {
			$vendor_request = $this->vendor_request_repository->findById($vendor_request_id);
			if (!$vendor_request) {
				error_log("VendorService: Vendor request not found: {$vendor_request_id}");
				return;
			}

			$transaction = $this->transaction_repository->findById($vendor_request->transaction_id);
			if (!$transaction) {
				error_log("VendorService: Transaction not found: {$vendor_request->transaction_id}");
				return;
			}

			// Determine recipient email based on sender type
			$recipient_email = ($sender_type === 'vendor')
				? $transaction->agent_email ?? null
				: $vendor_request->vendor_email;

			if ($recipient_email) {
				$this->email_service->sendVendorMessageNotification(
					$vendor_request,
					$transaction,
					$recipient_email,
					$sender_name,
					$message
				);
			}
		} catch (\Exception $e) {
			error_log("VendorService: Failed to send message notification email: " . $e->getMessage());
		}
	}

	/**
	 * Send availability notification email to agent
	 *
	 * @param int $vendor_request_id Vendor request ID
	 * @return void
	 */
	private function sendAvailabilityNotificationEmail(int $vendor_request_id): void {
		try {
			$vendor_request = $this->vendor_request_repository->findById($vendor_request_id);
			if (!$vendor_request) {
				error_log("VendorService: Vendor request not found: {$vendor_request_id}");
				return;
			}

			$transaction = $this->transaction_repository->findById($vendor_request->transaction_id);
			if (!$transaction) {
				error_log("VendorService: Transaction not found: {$vendor_request->transaction_id}");
				return;
			}

			// Get availability slots
			$availability_slots = $this->vendor_availability_repository->getByVendorRequest($vendor_request_id);

			$this->email_service->sendVendorAvailabilityNotification(
				$vendor_request,
				$transaction,
				$availability_slots
			);
		} catch (\Exception $e) {
			error_log("VendorService: Failed to send availability notification email: " . $e->getMessage());
		}
	}

	/**
	 * Get vendor profile and statistics
	 *
	 * @param string $vendor_email Vendor email
	 * @return array Vendor profile data
	 */
	public function getVendorProfile(string $vendor_email): array {
		$stats = $this->vendor_rating_repository->getVendorStats($vendor_email);
		$distribution = $this->vendor_rating_repository->getRatingDistribution($vendor_email);
		$recent_reviews = $this->vendor_rating_repository->getRecentReviews($vendor_email, 5);

		// Get request history
		$requests = $this->vendor_request_repository->query(['vendor_email' => $vendor_email], [
			'order' => 'created_at',
			'direction' => 'DESC',
			'limit' => 10,
		]);

		return [
			'vendor_email' => $vendor_email,
			'stats' => $stats,
			'rating_distribution' => $distribution,
			'recent_reviews' => $recent_reviews,
			'recent_requests' => $requests,
		];
	}
}
