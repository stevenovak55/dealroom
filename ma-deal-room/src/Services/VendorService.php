<?php
/**
 * Vendor Service
 *
 * @package MADealRoom\Services
 * @since 1.0.0
 */

namespace MADealRoom\Services;

use MADealRoom\Repositories\VendorRequestRepository;

/**
 * Vendor coordination service
 */
class VendorService {
	private $vendor_request_repository;

	public function __construct(VendorRequestRepository $vendor_request_repository) {
		$this->vendor_request_repository = $vendor_request_repository;
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
}
