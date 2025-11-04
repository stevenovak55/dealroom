<?php
/**
 * Massachusetts Real Estate Timeline Calculator
 *
 * @package MADealRoom\Services
 * @since 1.0.0
 */

namespace MADealRoom\Services;

/**
 * Calculate milestone dates based on MA real estate transaction timelines
 *
 * Based on Massachusetts real estate practices:
 * - Attorney Review: 72 hours (3 days) from offer acceptance
 * - Home Inspections: 7-10 days from offer acceptance
 * - P&S Agreement: 10-14 days from offer acceptance
 * - Mortgage Commitment: 3-4 weeks after P&S
 * - Closing: Average 45 days from offer acceptance
 */
class MATimelineCalculator {
	/**
	 * Calculate milestone dates based on transaction side and key dates
	 *
	 * @param string $transaction_side 'listing' or 'buyer'
	 * @param array $dates Array of key dates (offer_accepted_date, ps_agreement_date, closing_date, listing_date)
	 * @param array $property_data Property metadata (year_built, etc.)
	 * @return array Calculated milestone dates
	 */
	public function calculateMilestones(
		string $transaction_side,
		array $dates,
		array $property_data = []
	): array {
		$milestones = [];

		// BUYER SIDE TRANSACTION
		if ($transaction_side === 'buyer' && !empty($dates['offer_accepted_date'])) {
			$offer_date = new \DateTime($dates['offer_accepted_date']);

			// Attorney Review Period (3 days from offer acceptance)
			$milestones['attorney_review_deadline'] = (clone $offer_date)
				->modify('+3 days')
				->format('Y-m-d');

			// Home Inspection Period (7 days standard)
			$milestones['inspection_deadline'] = (clone $offer_date)
				->modify('+7 days')
				->format('Y-m-d');

			// Lead Paint Inspection (10 days if property built before 1978)
			if (!empty($property_data['year_built']) && $property_data['year_built'] < 1978) {
				$milestones['lead_paint_inspection_deadline'] = (clone $offer_date)
					->modify('+10 days')
					->format('Y-m-d');
			}

			// P&S Agreement (10-14 days, use 10 as default)
			if (empty($dates['ps_agreement_date'])) {
				$milestones['ps_agreement_target_date'] = (clone $offer_date)
					->modify('+10 days')
					->format('Y-m-d');
			}

			// Closing Date (45 days average if not specified)
			if (empty($dates['closing_date'])) {
				$milestones['closing_target_date'] = (clone $offer_date)
					->modify('+45 days')
					->format('Y-m-d');
			}
		}

		// Calculate dates from P&S if available
		if (!empty($dates['ps_agreement_date'])) {
			$ps_date = new \DateTime($dates['ps_agreement_date']);

			// Mortgage Commitment (3-4 weeks after P&S, use 25 days)
			$milestones['mortgage_commitment_target_date'] = (clone $ps_date)
				->modify('+25 days')
				->format('Y-m-d');
		}

		// Calculate dates from Closing if available
		if (!empty($dates['closing_date'])) {
			$closing_date = new \DateTime($dates['closing_date']);

			// Final Walkthrough (24-48 hours before closing, use 2 days)
			$milestones['final_walkthrough_date'] = (clone $closing_date)
				->modify('-2 days')
				->format('Y-m-d');

			// Title Work should start early (30 days before closing)
			$milestones['title_search_target_date'] = (clone $closing_date)
				->modify('-30 days')
				->format('Y-m-d');

			// Appraisal should be done early (20 days before closing)
			$milestones['appraisal_target_date'] = (clone $closing_date)
				->modify('-20 days')
				->format('Y-m-d');
		}

		// LISTING SIDE TRANSACTION
		if ($transaction_side === 'listing' && !empty($dates['listing_date'])) {
			$listing_date = new \DateTime($dates['listing_date']);

			// Photography/Marketing (within 3 days of listing)
			$milestones['photography_target_date'] = (clone $listing_date)
				->modify('+3 days')
				->format('Y-m-d');

			// MLS Entry (same day or next day)
			$milestones['mls_entry_target_date'] = (clone $listing_date)
				->modify('+1 day')
				->format('Y-m-d');

			// Property disclosures should be ready at listing
			$milestones['disclosure_preparation_date'] = $listing_date->format('Y-m-d');
		}

		// If we have an offer_accepted_date on listing side, calculate closing timeline
		if ($transaction_side === 'listing' && !empty($dates['offer_accepted_date'])) {
			$offer_date = new \DateTime($dates['offer_accepted_date']);

			// P&S Agreement (10 days typical)
			if (empty($dates['ps_agreement_date'])) {
				$milestones['ps_agreement_target_date'] = (clone $offer_date)
					->modify('+10 days')
					->format('Y-m-d');
			}

			// Closing (45 days average if not specified)
			if (empty($dates['closing_date'])) {
				$milestones['closing_target_date'] = (clone $offer_date)
					->modify('+45 days')
					->format('Y-m-d');
			}
		}

		return $milestones;
	}

	/**
	 * Auto-populate transaction dates based on transaction side
	 *
	 * @param string $transaction_side 'listing' or 'buyer'
	 * @param string|null $offer_accepted_date Offer acceptance date
	 * @param string|null $listing_date Listing date
	 * @param array $property_data Property metadata
	 * @return array Suggested dates for ps_agreement_date and closing_date
	 */
	public function suggestTransactionDates(
		string $transaction_side,
		?string $offer_accepted_date = null,
		?string $listing_date = null,
		array $property_data = []
	): array {
		$suggested_dates = [];

		if ($transaction_side === 'buyer' && $offer_accepted_date) {
			$offer_date = new \DateTime($offer_accepted_date);

			// Suggest P&S date (10 days from offer)
			$suggested_dates['ps_agreement_date'] = (clone $offer_date)
				->modify('+10 days')
				->format('Y-m-d');

			// Suggest closing date (45 days from offer)
			$suggested_dates['closing_date'] = (clone $offer_date)
				->modify('+45 days')
				->format('Y-m-d');
		}

		if ($transaction_side === 'listing' && $offer_accepted_date) {
			$offer_date = new \DateTime($offer_accepted_date);

			// Suggest P&S date (10 days from offer)
			$suggested_dates['ps_agreement_date'] = (clone $offer_date)
				->modify('+10 days')
				->format('Y-m-d');

			// Suggest closing date (45 days from offer)
			$suggested_dates['closing_date'] = (clone $offer_date)
				->modify('+45 days')
				->format('Y-m-d');
		}

		return $suggested_dates;
	}

	/**
	 * Get status-specific milestone dates
	 *
	 * @param string $status Current transaction status
	 * @param string $transaction_side Transaction side
	 * @param array $dates Current transaction dates
	 * @param array $property_data Property metadata
	 * @return array Status-specific milestone information
	 */
	public function getStatusMilestones(
		string $status,
		string $transaction_side,
		array $dates,
		array $property_data = []
	): array {
		$milestones = [];

		switch ($status) {
			case 'prospect':
				if ($transaction_side === 'listing') {
					$milestones['next_step'] = 'Sign listing agreement';
					$milestones['estimated_timeline'] = 'Listing preparation: 1-2 weeks';
				} else {
					$milestones['next_step'] = 'Search for properties';
					$milestones['estimated_timeline'] = 'Property search: 2-8 weeks typical';
				}
				break;

			case 'listing_active':
				if (!empty($dates['listing_date'])) {
					$listing_date = new \DateTime($dates['listing_date']);
					$milestones['days_on_market'] = (new \DateTime())->diff($listing_date)->days;
					$milestones['next_step'] = 'Await offers';
					$milestones['average_dom_ma'] = '30-45 days (market dependent)';
				}
				break;

			case 'under_agreement':
				$calculated = $this->calculateMilestones($transaction_side, $dates, $property_data);
				$milestones = array_merge($milestones, $calculated);

				if (!empty($dates['ps_agreement_date'])) {
					$milestones['next_step'] = 'Mortgage approval & inspections';
				} else {
					$milestones['next_step'] = 'Sign P&S Agreement';
				}

				if (!empty($dates['closing_date'])) {
					$closing_date = new \DateTime($dates['closing_date']);
					$milestones['days_to_closing'] = (new \DateTime())->diff($closing_date)->days;
				}
				break;

			case 'closed':
				$milestones['next_step'] = 'Post-closing follow-up';
				if (!empty($dates['actual_closing_date'])) {
					$closing_date = new \DateTime($dates['actual_closing_date']);
					$milestones['days_since_closing'] = (new \DateTime())->diff($closing_date)->days;
				}
				break;

			case 'cancelled':
				$milestones['next_step'] = 'None (transaction cancelled)';
				break;
		}

		return $milestones;
	}
}
