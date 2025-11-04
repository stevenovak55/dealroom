<?php
/**
 * MATimelineCalculator Unit Tests
 *
 * Tests for Massachusetts real estate transaction timeline calculations.
 *
 * @package MADealRoom\Tests\Unit\Services
 */

namespace MADealRoom\Tests\Unit\Services;

use MADealRoom\Tests\TestCase;
use MADealRoom\Services\MATimelineCalculator;

/**
 * MATimelineCalculator Test Class
 */
class MATimelineCalculatorTest extends TestCase
{
    private $calculator;

    protected function setUp(): void
    {
        parent::setUp();
        $this->calculator = new MATimelineCalculator();
    }

    // ========================================
    // calculateMilestones() - Buyer Side Tests
    // ========================================

    public function testCalculateMilestonesBuyerSideWithOfferDate()
    {
        $dates = [
            'offer_accepted_date' => '2025-02-01',
        ];

        $milestones = $this->calculator->calculateMilestones('buyer', $dates);

        // Attorney review deadline (3 days)
        $this->assertEquals('2025-02-04', $milestones['attorney_review_deadline']);

        // Inspection deadline (7 days)
        $this->assertEquals('2025-02-08', $milestones['inspection_deadline']);

        // P&S target (10 days)
        $this->assertEquals('2025-02-11', $milestones['ps_agreement_target_date']);

        // Closing target (45 days)
        $this->assertEquals('2025-03-18', $milestones['closing_target_date']);
    }

    public function testCalculateMilestonesBuyerSideWithLeadPaint()
    {
        $dates = [
            'offer_accepted_date' => '2025-02-01',
        ];

        $property_data = [
            'year_built' => 1970, // Before 1978
        ];

        $milestones = $this->calculator->calculateMilestones('buyer', $dates, $property_data);

        // Lead paint inspection deadline (10 days for pre-1978 properties)
        $this->assertArrayHasKey('lead_paint_inspection_deadline', $milestones);
        $this->assertEquals('2025-02-11', $milestones['lead_paint_inspection_deadline']);
    }

    public function testCalculateMilestonesBuyerSideNoLeadPaint()
    {
        $dates = [
            'offer_accepted_date' => '2025-02-01',
        ];

        $property_data = [
            'year_built' => 2020, // After 1978
        ];

        $milestones = $this->calculator->calculateMilestones('buyer', $dates, $property_data);

        // No lead paint inspection for newer properties
        $this->assertArrayNotHasKey('lead_paint_inspection_deadline', $milestones);
    }

    public function testCalculateMilestonesBuyerSideWithPSDate()
    {
        $dates = [
            'offer_accepted_date' => '2025-02-01',
            'ps_agreement_date' => '2025-02-10',
        ];

        $milestones = $this->calculator->calculateMilestones('buyer', $dates);

        // Should not suggest P&S date if already provided
        $this->assertArrayNotHasKey('ps_agreement_target_date', $milestones);

        // Mortgage commitment (25 days after P&S)
        $this->assertEquals('2025-03-07', $milestones['mortgage_commitment_target_date']);
    }

    public function testCalculateMilestonesBuyerSideWithClosingDate()
    {
        $dates = [
            'offer_accepted_date' => '2025-02-01',
            'closing_date' => '2025-03-15',
        ];

        $milestones = $this->calculator->calculateMilestones('buyer', $dates);

        // Should not suggest closing date if already provided
        $this->assertArrayNotHasKey('closing_target_date', $milestones);

        // Final walkthrough (2 days before closing)
        $this->assertEquals('2025-03-13', $milestones['final_walkthrough_date']);

        // Title search (30 days before closing)
        $this->assertEquals('2025-02-13', $milestones['title_search_target_date']);

        // Appraisal (20 days before closing)
        $this->assertEquals('2025-02-23', $milestones['appraisal_target_date']);
    }

    public function testCalculateMilestonesBuyerSideNoOfferDate()
    {
        $dates = [];

        $milestones = $this->calculator->calculateMilestones('buyer', $dates);

        // Should return empty array if no offer date
        $this->assertEmpty($milestones);
    }

    // ========================================
    // calculateMilestones() - Listing Side Tests
    // ========================================

    public function testCalculateMilestonesListingSideWithListingDate()
    {
        $dates = [
            'listing_date' => '2025-01-15',
        ];

        $milestones = $this->calculator->calculateMilestones('listing', $dates);

        // Photography (3 days)
        $this->assertEquals('2025-01-18', $milestones['photography_target_date']);

        // MLS entry (1 day)
        $this->assertEquals('2025-01-16', $milestones['mls_entry_target_date']);

        // Disclosure preparation (same day)
        $this->assertEquals('2025-01-15', $milestones['disclosure_preparation_date']);
    }

    public function testCalculateMilestonesListingSideWithOfferAccepted()
    {
        $dates = [
            'listing_date' => '2025-01-15',
            'offer_accepted_date' => '2025-02-01',
        ];

        $milestones = $this->calculator->calculateMilestones('listing', $dates);

        // P&S target (10 days)
        $this->assertEquals('2025-02-11', $milestones['ps_agreement_target_date']);

        // Closing target (45 days)
        $this->assertEquals('2025-03-18', $milestones['closing_target_date']);
    }

    public function testCalculateMilestonesListingSideNoListingDate()
    {
        $dates = [];

        $milestones = $this->calculator->calculateMilestones('listing', $dates);

        // Should return empty array if no listing date
        $this->assertEmpty($milestones);
    }

    // ========================================
    // suggestTransactionDates() Tests
    // ========================================

    public function testSuggestTransactionDatesBuyerSide()
    {
        $suggested = $this->calculator->suggestTransactionDates('buyer', '2025-02-01');

        $this->assertArrayHasKey('ps_agreement_date', $suggested);
        $this->assertArrayHasKey('closing_date', $suggested);

        // P&S: 10 days from offer
        $this->assertEquals('2025-02-11', $suggested['ps_agreement_date']);

        // Closing: 45 days from offer
        $this->assertEquals('2025-03-18', $suggested['closing_date']);
    }

    public function testSuggestTransactionDatesListingSide()
    {
        $suggested = $this->calculator->suggestTransactionDates('listing', '2025-02-01');

        $this->assertArrayHasKey('ps_agreement_date', $suggested);
        $this->assertArrayHasKey('closing_date', $suggested);

        // P&S: 10 days from offer
        $this->assertEquals('2025-02-11', $suggested['ps_agreement_date']);

        // Closing: 45 days from offer
        $this->assertEquals('2025-03-18', $suggested['closing_date']);
    }

    public function testSuggestTransactionDatesNoOfferDate()
    {
        $suggested = $this->calculator->suggestTransactionDates('buyer');

        // Should return empty array if no offer date
        $this->assertEmpty($suggested);
    }

    public function testSuggestTransactionDatesWithListingDate()
    {
        // Listing date alone shouldn't generate suggestions
        $suggested = $this->calculator->suggestTransactionDates('listing', null, '2025-01-15');

        $this->assertEmpty($suggested);
    }

    // ========================================
    // getStatusMilestones() Tests
    // ========================================

    public function testGetStatusMilestonesProspectBuyer()
    {
        $milestones = $this->calculator->getStatusMilestones('prospect', 'buyer', []);

        $this->assertArrayHasKey('next_step', $milestones);
        $this->assertArrayHasKey('estimated_timeline', $milestones);
        $this->assertEquals('Search for properties', $milestones['next_step']);
    }

    public function testGetStatusMilestonesProspectListing()
    {
        $milestones = $this->calculator->getStatusMilestones('prospect', 'listing', []);

        $this->assertArrayHasKey('next_step', $milestones);
        $this->assertEquals('Sign listing agreement', $milestones['next_step']);
    }

    public function testGetStatusMilestonesListingActive()
    {
        $dates = [
            'listing_date' => '2025-01-01',
        ];

        $milestones = $this->calculator->getStatusMilestones('listing_active', 'listing', $dates);

        $this->assertArrayHasKey('next_step', $milestones);
        $this->assertArrayHasKey('days_on_market', $milestones);
        $this->assertArrayHasKey('average_dom_ma', $milestones);
        $this->assertEquals('Await offers', $milestones['next_step']);
        $this->assertIsInt($milestones['days_on_market']);
    }

    public function testGetStatusMilestonesUnderAgreementNoPSDate()
    {
        $dates = [
            'offer_accepted_date' => '2025-02-01',
        ];

        $milestones = $this->calculator->getStatusMilestones('under_agreement', 'buyer', $dates);

        $this->assertArrayHasKey('next_step', $milestones);
        $this->assertEquals('Sign P&S Agreement', $milestones['next_step']);
        $this->assertArrayHasKey('attorney_review_deadline', $milestones);
    }

    public function testGetStatusMilestonesUnderAgreementWithPSDate()
    {
        $dates = [
            'offer_accepted_date' => '2025-02-01',
            'ps_agreement_date' => '2025-02-10',
        ];

        $milestones = $this->calculator->getStatusMilestones('under_agreement', 'buyer', $dates);

        $this->assertEquals('Mortgage approval & inspections', $milestones['next_step']);
        $this->assertArrayHasKey('mortgage_commitment_target_date', $milestones);
    }

    public function testGetStatusMilestonesUnderAgreementWithClosingDate()
    {
        $dates = [
            'offer_accepted_date' => '2025-02-01',
            'closing_date' => '2025-03-15',
        ];

        $milestones = $this->calculator->getStatusMilestones('under_agreement', 'buyer', $dates);

        $this->assertArrayHasKey('days_to_closing', $milestones);
        $this->assertIsInt($milestones['days_to_closing']);
        $this->assertGreaterThanOrEqual(0, $milestones['days_to_closing']);
    }

    public function testGetStatusMilestonesClosed()
    {
        $dates = [
            'actual_closing_date' => '2025-01-15',
        ];

        $milestones = $this->calculator->getStatusMilestones('closed', 'buyer', $dates);

        $this->assertArrayHasKey('next_step', $milestones);
        $this->assertArrayHasKey('days_since_closing', $milestones);
        $this->assertEquals('Post-closing follow-up', $milestones['next_step']);
        $this->assertIsInt($milestones['days_since_closing']);
    }

    public function testGetStatusMilestonesCancelled()
    {
        $milestones = $this->calculator->getStatusMilestones('cancelled', 'buyer', []);

        $this->assertArrayHasKey('next_step', $milestones);
        $this->assertEquals('None (transaction cancelled)', $milestones['next_step']);
    }

    // ========================================
    // Date Calculation Edge Cases
    // ========================================

    public function testDateCalculationCrossMonthBoundary()
    {
        $dates = [
            'offer_accepted_date' => '2025-01-25', // Near end of month
        ];

        $milestones = $this->calculator->calculateMilestones('buyer', $dates);

        // Should correctly cross into next month
        // Jan 25 + 7 days = Feb 1
        $this->assertEquals('2025-02-01', $milestones['inspection_deadline']);
        // Jan 25 + 45 days = Mar 11
        $this->assertEquals('2025-03-11', $milestones['closing_target_date']);
    }

    public function testDateCalculationCrossYearBoundary()
    {
        $dates = [
            'offer_accepted_date' => '2024-12-20', // Near end of year
        ];

        $milestones = $this->calculator->calculateMilestones('buyer', $dates);

        // Should correctly cross into next year
        $this->assertEquals('2024-12-30', $milestones['ps_agreement_target_date']);
        $this->assertEquals('2025-02-03', $milestones['closing_target_date']);
    }

    public function testDateCalculationLeapYear()
    {
        $dates = [
            'offer_accepted_date' => '2024-02-15', // 2024 is a leap year
        ];

        $milestones = $this->calculator->calculateMilestones('buyer', $dates);

        // Should handle leap year February correctly
        $this->assertEquals('2024-02-25', $milestones['ps_agreement_target_date']);
        $this->assertEquals('2024-03-31', $milestones['closing_target_date']);
    }

    public function testBackwardDateCalculationFromClosing()
    {
        $dates = [
            'closing_date' => '2025-03-15',
        ];

        $milestones = $this->calculator->calculateMilestones('buyer', $dates);

        // Should calculate dates backward from closing
        $this->assertEquals('2025-03-13', $milestones['final_walkthrough_date']);
        $this->assertEquals('2025-02-13', $milestones['title_search_target_date']);
        $this->assertEquals('2025-02-23', $milestones['appraisal_target_date']);

        // Verify all are before closing date
        $closing = new \DateTime('2025-03-15');
        $this->assertLessThan($closing, new \DateTime($milestones['final_walkthrough_date']));
        $this->assertLessThan($closing, new \DateTime($milestones['title_search_target_date']));
        $this->assertLessThan($closing, new \DateTime($milestones['appraisal_target_date']));
    }

    // ========================================
    // Multiple Date Anchors
    // ========================================

    public function testMultipleDateAnchors()
    {
        $dates = [
            'offer_accepted_date' => '2025-02-01',
            'ps_agreement_date' => '2025-02-10',
            'closing_date' => '2025-03-15',
        ];

        $milestones = $this->calculator->calculateMilestones('buyer', $dates);

        // Should use offer date for inspection deadlines
        $this->assertEquals('2025-02-04', $milestones['attorney_review_deadline']);
        $this->assertEquals('2025-02-08', $milestones['inspection_deadline']);

        // Should use PS date for mortgage
        $this->assertEquals('2025-03-07', $milestones['mortgage_commitment_target_date']);

        // Should use closing date for final walkthrough
        $this->assertEquals('2025-03-13', $milestones['final_walkthrough_date']);
    }
}
