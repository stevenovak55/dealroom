<?php
/**
 * SecurityDeposit Model
 *
 * @package MADealRoom\Models
 * @since 2.0.0
 */

namespace MADealRoom\Models;

/**
 * SecurityDeposit model representing MA rental security deposit tracking
 * with full compliance monitoring for M.G.L. c. 186, § 15B
 */
class SecurityDeposit {
	public int $id;
	public int $transaction_id;
	public int $tenant_party_id;

	// Deposit Amounts
	public float $deposit_amount;
	public float $last_month_rent_amount = 0.00;
	public float $key_deposit_amount = 0.00;

	// Receipt & Collection
	public string $received_date;
	public bool $receipt_provided = false;
	public ?string $receipt_date = null;

	// Bank Account Information (MA Law Requirement)
	public ?string $bank_name = null;
	public ?string $bank_address = null;
	public ?string $bank_account_number = null;
	public ?string $bank_routing_number = null;
	public string $account_type = 'savings';

	// MA Law Compliance: 30-Day Deadlines
	public ?string $deposited_date = null;
	public ?string $tenant_notified_date = null;
	public bool $statement_of_condition_provided = false;
	public ?string $statement_of_condition_date = null;

	// Interest Tracking (MA Law Requirement)
	public float $interest_rate = 0.0000;
	public float $interest_accrued = 0.00;
	public ?string $last_interest_calculation_date = null;

	// Transfer on Sale (for multifamily properties)
	public bool $transferred_to_new_owner = false;
	public ?string $transfer_date = null;
	public bool $transfer_confirmed_by_tenant = false;
	public ?string $new_owner_notification_date = null;

	// Return of Deposit
	public ?string $lease_end_date = null;
	public ?string $return_date = null;
	public ?float $return_amount = null;
	public float $deductions_amount = 0.00;
	public ?string $deductions_itemized = null;
	public bool $deductions_notification_sent = false;

	// Compliance Tracking
	public bool $is_compliant = true;
	public ?string $compliance_issues = null;
	public ?string $compliance_last_checked = null;

	// Audit Trail
	public string $created_at;
	public string $updated_at;
	public ?int $created_by = null;
	public ?int $updated_by = null;

	/**
	 * Constructor
	 *
	 * @param array $data Initial data
	 */
	public function __construct(array $data = []) {
		foreach ($data as $key => $value) {
			if (property_exists($this, $key)) {
				// Handle boolean conversion for TINYINT(1) from database
				if (is_numeric($value) && $this->isBooleanProperty($key)) {
					$this->$key = (bool) $value;
				}
				// Handle JSON fields
				elseif ($key === 'compliance_issues' && is_string($value) && !empty($value)) {
					$this->$key = $value; // Keep as JSON string
				} else {
					$this->$key = $value;
				}
			}
		}
	}

	/**
	 * Check if a property should be treated as boolean
	 *
	 * @param string $property Property name
	 * @return bool
	 */
	private function isBooleanProperty(string $property): bool {
		$boolean_properties = [
			'receipt_provided', 'statement_of_condition_provided',
			'transferred_to_new_owner', 'transfer_confirmed_by_tenant',
			'deductions_notification_sent', 'is_compliant'
		];

		return in_array($property, $boolean_properties);
	}

	/**
	 * Convert model to array
	 *
	 * @return array
	 */
	public function toArray(): array {
		$array = get_object_vars($this);

		// Parse compliance_issues JSON if present
		if (!empty($array['compliance_issues'])) {
			$array['compliance_issues_parsed'] = json_decode($array['compliance_issues'], true);
		}

		return $array;
	}

	/**
	 * Get the related transaction
	 *
	 * @return Transaction|null
	 */
	public function getTransaction(): ?Transaction {
		global $wpdb;
		$prefix = $wpdb->prefix;

		$transaction_data = $wpdb->get_row($wpdb->prepare(
			"SELECT * FROM {$prefix}ma_deal_transactions WHERE id = %d",
			$this->transaction_id
		), ARRAY_A);

		return $transaction_data ? new Transaction($transaction_data) : null;
	}

	/**
	 * Check if deposit deadline is approaching or passed (30 days from received_date)
	 * MA Law: Must deposit within 30 days of receipt
	 *
	 * @return array ['status' => 'compliant'|'warning'|'violation', 'days_remaining' => int, 'deadline' => string]
	 */
	public function checkDepositDeadline(): array {
		if (empty($this->received_date)) {
			return ['status' => 'unknown', 'days_remaining' => null, 'deadline' => null];
		}

		$received = new \DateTime($this->received_date);
		$deadline = clone $received;
		$deadline->modify('+30 days');
		$now = new \DateTime();

		if ($this->deposited_date !== null) {
			$deposited = new \DateTime($this->deposited_date);
			$days_taken = $received->diff($deposited)->days;

			return [
				'status' => $days_taken <= 30 ? 'compliant' : 'violation',
				'days_taken' => $days_taken,
				'deadline' => $deadline->format('Y-m-d'),
				'deposited' => $this->deposited_date
			];
		}

		$days_remaining = $now->diff($deadline)->days;
		$is_past = $now > $deadline;

		return [
			'status' => $is_past ? 'violation' : ($days_remaining <= 7 ? 'warning' : 'compliant'),
			'days_remaining' => $is_past ? -$days_remaining : $days_remaining,
			'deadline' => $deadline->format('Y-m-d')
		];
	}

	/**
	 * Check if tenant notification deadline is approaching or passed (30 days from received_date)
	 * MA Law: Must notify tenant of bank account within 30 days
	 *
	 * @return array ['status' => 'compliant'|'warning'|'violation', 'days_remaining' => int, 'deadline' => string]
	 */
	public function checkNotificationDeadline(): array {
		if (empty($this->received_date)) {
			return ['status' => 'unknown', 'days_remaining' => null, 'deadline' => null];
		}

		$received = new \DateTime($this->received_date);
		$deadline = clone $received;
		$deadline->modify('+30 days');
		$now = new \DateTime();

		if ($this->tenant_notified_date !== null) {
			$notified = new \DateTime($this->tenant_notified_date);
			$days_taken = $received->diff($notified)->days;

			return [
				'status' => $days_taken <= 30 ? 'compliant' : 'violation',
				'days_taken' => $days_taken,
				'deadline' => $deadline->format('Y-m-d'),
				'notified' => $this->tenant_notified_date
			];
		}

		$days_remaining = $now->diff($deadline)->days;
		$is_past = $now > $deadline;

		return [
			'status' => $is_past ? 'violation' : ($days_remaining <= 7 ? 'warning' : 'compliant'),
			'days_remaining' => $is_past ? -$days_remaining : $days_remaining,
			'deadline' => $deadline->format('Y-m-d')
		];
	}

	/**
	 * Check if Statement of Condition deadline is approaching or passed (10 days from received_date)
	 * MA Law: Must provide Statement of Condition within 10 days
	 *
	 * @return array ['status' => 'compliant'|'warning'|'violation', 'days_remaining' => int, 'deadline' => string]
	 */
	public function checkStatementDeadline(): array {
		if (empty($this->received_date)) {
			return ['status' => 'unknown', 'days_remaining' => null, 'deadline' => null];
		}

		$received = new \DateTime($this->received_date);
		$deadline = clone $received;
		$deadline->modify('+10 days');
		$now = new \DateTime();

		if ($this->statement_of_condition_provided) {
			$provided = $this->statement_of_condition_date ? new \DateTime($this->statement_of_condition_date) : null;
			$days_taken = $provided ? $received->diff($provided)->days : null;

			return [
				'status' => ($days_taken === null || $days_taken <= 10) ? 'compliant' : 'violation',
				'days_taken' => $days_taken,
				'deadline' => $deadline->format('Y-m-d'),
				'provided' => $this->statement_of_condition_date
			];
		}

		$days_remaining = $now->diff($deadline)->days;
		$is_past = $now > $deadline;

		return [
			'status' => $is_past ? 'violation' : ($days_remaining <= 3 ? 'warning' : 'compliant'),
			'days_remaining' => $is_past ? -$days_remaining : $days_remaining,
			'deadline' => $deadline->format('Y-m-d')
		];
	}

	/**
	 * Check if return deadline is approaching or passed (30 days from lease_end_date)
	 * MA Law: Must return deposit within 30 days of lease end
	 *
	 * @return array ['status' => 'compliant'|'warning'|'violation', 'days_remaining' => int, 'deadline' => string]
	 */
	public function checkReturnDeadline(): array {
		if (empty($this->lease_end_date)) {
			return ['status' => 'not_applicable', 'days_remaining' => null, 'deadline' => null];
		}

		$lease_end = new \DateTime($this->lease_end_date);
		$deadline = clone $lease_end;
		$deadline->modify('+30 days');
		$now = new \DateTime();

		if ($this->return_date !== null) {
			$returned = new \DateTime($this->return_date);
			$days_taken = $lease_end->diff($returned)->days;

			return [
				'status' => $days_taken <= 30 ? 'compliant' : 'violation',
				'days_taken' => $days_taken,
				'deadline' => $deadline->format('Y-m-d'),
				'returned' => $this->return_date
			];
		}

		$days_remaining = $now->diff($deadline)->days;
		$is_past = $now > $deadline;

		return [
			'status' => $is_past ? 'violation' : ($days_remaining <= 7 ? 'warning' : 'compliant'),
			'days_remaining' => $is_past ? -$days_remaining : $days_remaining,
			'deadline' => $deadline->format('Y-m-d')
		];
	}

	/**
	 * Get all compliance issues as parsed array
	 *
	 * @return array
	 */
	public function getComplianceIssues(): array {
		if (empty($this->compliance_issues)) {
			return [];
		}

		$issues = json_decode($this->compliance_issues, true);
		return is_array($issues) ? $issues : [];
	}

	/**
	 * Get comprehensive compliance status
	 * Checks all deadlines and requirements
	 *
	 * @return array ['overall_status' => 'compliant'|'warning'|'violation', 'checks' => array]
	 */
	public function getComplianceStatus(): array {
		$checks = [
			'deposit_deadline' => $this->checkDepositDeadline(),
			'notification_deadline' => $this->checkNotificationDeadline(),
			'statement_deadline' => $this->checkStatementDeadline(),
			'return_deadline' => $this->checkReturnDeadline(),
		];

		// Additional checks
		$checks['receipt_provided'] = [
			'status' => $this->receipt_provided ? 'compliant' : 'violation',
			'message' => $this->receipt_provided ? 'Receipt provided' : 'Receipt not yet provided'
		];

		$checks['bank_information'] = [
			'status' => !empty($this->bank_name) ? 'compliant' : 'violation',
			'message' => !empty($this->bank_name) ? 'Bank information recorded' : 'Bank information missing'
		];

		// Determine overall status
		$has_violation = false;
		$has_warning = false;

		foreach ($checks as $check) {
			if ($check['status'] === 'violation') {
				$has_violation = true;
			} elseif ($check['status'] === 'warning') {
				$has_warning = true;
			}
		}

		$overall_status = $has_violation ? 'violation' : ($has_warning ? 'warning' : 'compliant');

		return [
			'overall_status' => $overall_status,
			'is_compliant' => !$has_violation,
			'checks' => $checks,
			'compliance_issues' => $this->getComplianceIssues()
		];
	}

	/**
	 * Calculate interest accrued (simplified - actual calculation more complex)
	 * MA Law: Interest must be paid annually at 5% or bank's rate
	 *
	 * @return float
	 */
	public function calculateInterest(): float {
		if (empty($this->deposited_date) || $this->deposit_amount <= 0) {
			return 0.00;
		}

		$deposited = new \DateTime($this->deposited_date);
		$now = new \DateTime();
		$days = $deposited->diff($now)->days;
		$years = $days / 365.25;

		$rate = $this->interest_rate > 0 ? $this->interest_rate : 0.05; // Default 5% if not set
		$interest = $this->deposit_amount * $rate * $years;

		return round($interest, 2);
	}

	/**
	 * Validate security deposit data
	 *
	 * @return array Array of error messages
	 */
	public function validate(): array {
		$errors = [];

		if (empty($this->transaction_id)) {
			$errors[] = 'Transaction ID is required';
		}

		if (empty($this->tenant_party_id)) {
			$errors[] = 'Tenant party ID is required';
		}

		if ($this->deposit_amount <= 0) {
			$errors[] = 'Deposit amount must be greater than zero';
		}

		if (empty($this->received_date)) {
			$errors[] = 'Received date is required';
		}

		// MA Law: Security deposit cannot exceed 1 month's rent
		// (This check would require knowing the monthly rent, which might be in transaction metadata)

		if ($this->return_amount !== null && $this->return_amount > $this->deposit_amount + $this->interest_accrued) {
			$errors[] = 'Return amount exceeds deposit amount plus interest';
		}

		return $errors;
	}

	/**
	 * Create instance from array
	 *
	 * @param array $data Data array
	 * @return self
	 */
	public static function fromArray(array $data): self {
		return new self($data);
	}
}
