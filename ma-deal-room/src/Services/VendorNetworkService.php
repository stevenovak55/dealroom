<?php

namespace MADealRoom\Services;

use MADealRoom\Repositories\VendorProfileRepository;
use MADealRoom\Repositories\VendorRequestRepository;
use MADealRoom\Models\VendorProfile;

/**
 * Vendor Network Service
 *
 * Manages vendor directory, profiles, and network features
 *
 * @package MADealRoom
 * @since 2.1.0
 */
class VendorNetworkService {
    private VendorProfileRepository $vendor_profile_repository;
    private VendorRequestRepository $vendor_request_repository;

    public function __construct(
        VendorProfileRepository $vendor_profile_repository,
        VendorRequestRepository $vendor_request_repository
    ) {
        $this->vendor_profile_repository = $vendor_profile_repository;
        $this->vendor_request_repository = $vendor_request_repository;
    }

    /**
     * Search vendor network
     */
    public function searchVendors(array $filters = [], array $options = []): array {
        $vendors = $this->vendor_profile_repository->searchVendors($filters, $options);

        // Enhance vendor data
        foreach ($vendors as $vendor) {
            $vendor->stats = $vendor->getStats();
        }

        return $vendors;
    }

    /**
     * Get vendor profile with full details
     */
    public function getVendorProfile(int $vendor_id): ?VendorProfile {
        $vendor = $this->vendor_profile_repository->find($vendor_id);

        if (!$vendor) {
            return null;
        }

        // Add additional data
        $vendor->categories = $vendor->getCategories();
        $vendor->recent_reviews = $vendor->getRecentReviews();
        $vendor->stats = $vendor->getStats();

        return $vendor;
    }

    /**
     * Get preferred vendors for current user
     */
    public function getPreferredVendors(int $user_id, ?string $vendor_type = null): array {
        return $this->vendor_profile_repository->getPreferredVendors($user_id, $vendor_type);
    }

    /**
     * Add vendor to preferred list
     */
    public function addToPreferred(int $user_id, int $vendor_id, string $vendor_type, array $data = []): bool {
        return $this->vendor_profile_repository->addToPreferred($user_id, $vendor_id, $vendor_type, $data);
    }

    /**
     * Remove from preferred list
     */
    public function removeFromPreferred(int $user_id, int $vendor_id): bool {
        return $this->vendor_profile_repository->removeFromPreferred($user_id, $vendor_id);
    }

    /**
     * Quick re-invite vendor for new request
     */
    public function reinviteVendor(int $vendor_id, array $request_data): ?array {
        $vendor = $this->vendor_profile_repository->find($vendor_id);

        if (!$vendor) {
            throw new \Exception('Vendor not found');
        }

        // Merge vendor profile data with request
        $request_data['vendor_email'] = $vendor->email;
        $request_data['vendor_name'] = $vendor->name;
        $request_data['vendor_company'] = $vendor->company;
        $request_data['vendor_phone'] = $vendor->phone;

        // Generate token
        $token = bin2hex(random_bytes(32));
        $token_expires_at = date('Y-m-d H:i:s', strtotime('+30 days'));

        $request_data['token'] = $token;
        $request_data['token_expires_at'] = $token_expires_at;
        $request_data['status'] = 'sent';
        $request_data['created_at'] = current_time('mysql');
        $request_data['updated_at'] = current_time('mysql');

        // Create vendor request
        $request_id = $this->vendor_request_repository->create($request_data);

        if (!$request_id) {
            throw new \Exception('Failed to create vendor request');
        }

        // Track invitation
        global $wpdb;
        $wpdb->insert(
            $wpdb->prefix . 'ma_deal_vendor_invitations',
            [
                'vendor_profile_id' => $vendor_id,
                'vendor_request_id' => $request_id,
                'invited_by_user_id' => get_current_user_id(),
                'invitation_sent_at' => current_time('mysql'),
                'response_status' => 'pending'
            ]
        );

        // Update preferred vendor usage
        $wpdb->query($wpdb->prepare(
            "UPDATE {$wpdb->prefix}ma_deal_preferred_vendors
             SET times_used = times_used + 1, last_used_at = %s
             WHERE vendor_profile_id = %d AND user_id = %d",
            current_time('mysql'),
            $vendor_id,
            get_current_user_id()
        ));

        $vendor_request = $this->vendor_request_repository->find($request_id);

        return [
            'vendor_request' => $vendor_request,
            'vendor_profile' => $vendor,
            'portal_url' => home_url('/vendor-portal/?token=' . $token)
        ];
    }

    /**
     * Create vendor review
     */
    public function createReview(int $vendor_id, array $review_data): bool {
        global $wpdb;
        $table = $wpdb->prefix . 'ma_deal_vendor_reviews';

        // Verify vendor exists
        $vendor = $this->vendor_profile_repository->find($vendor_id);
        if (!$vendor) {
            throw new \Exception('Vendor not found');
        }

        // Insert review
        $result = $wpdb->insert(
            $table,
            [
                'vendor_profile_id' => $vendor_id,
                'vendor_request_id' => $review_data['vendor_request_id'] ?? null,
                'reviewer_user_id' => get_current_user_id() ?: null,
                'reviewer_name' => $review_data['reviewer_name'] ?? wp_get_current_user()->display_name,
                'rating' => (int)$review_data['rating'],
                'review_text' => $review_data['review_text'] ?? null,
                'service_type' => $review_data['service_type'] ?? null,
                'professionalism' => $review_data['professionalism'] ?? null,
                'communication' => $review_data['communication'] ?? null,
                'timeliness' => $review_data['timeliness'] ?? null,
                'value' => $review_data['value'] ?? null,
                'would_recommend' => isset($review_data['would_recommend']) ? (int)$review_data['would_recommend'] : null,
                'is_verified_transaction' => !empty($review_data['vendor_request_id']) ? 1 : 0,
                'is_published' => 1,
                'created_at' => current_time('mysql'),
                'updated_at' => current_time('mysql')
            ]
        );

        return $result !== false;
    }

    /**
     * Get vendor reviews
     */
    public function getVendorReviews(int $vendor_id, array $options = []): array {
        global $wpdb;
        $table = $wpdb->prefix . 'ma_deal_vendor_reviews';

        $limit = isset($options['limit']) ? (int)$options['limit'] : 10;
        $offset = isset($options['offset']) ? (int)$options['offset'] : 0;

        $reviews = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM {$table}
             WHERE vendor_profile_id = %d AND is_published = 1
             ORDER BY created_at DESC
             LIMIT %d OFFSET %d",
            $vendor_id,
            $limit,
            $offset
        ), ARRAY_A);

        return $reviews;
    }

    /**
     * Get vendor statistics summary
     */
    public function getVendorStats(int $vendor_id): array {
        $vendor = $this->vendor_profile_repository->find($vendor_id);

        if (!$vendor) {
            return [];
        }

        $stats = $vendor->getStats();

        // Add review breakdown
        global $wpdb;
        $table = $wpdb->prefix . 'ma_deal_vendor_reviews';

        $review_stats = $wpdb->get_row($wpdb->prepare(
            "SELECT
                AVG(professionalism) as avg_professionalism,
                AVG(communication) as avg_communication,
                AVG(timeliness) as avg_timeliness,
                AVG(value) as avg_value,
                SUM(CASE WHEN would_recommend = 1 THEN 1 ELSE 0 END) as would_recommend_count,
                COUNT(*) as total_reviews
            FROM {$table}
            WHERE vendor_profile_id = %d AND is_published = 1",
            $vendor_id
        ), ARRAY_A);

        $stats['review_breakdown'] = [
            'professionalism' => round($review_stats['avg_professionalism'] ?? 0, 1),
            'communication' => round($review_stats['avg_communication'] ?? 0, 1),
            'timeliness' => round($review_stats['avg_timeliness'] ?? 0, 1),
            'value' => round($review_stats['avg_value'] ?? 0, 1),
            'recommendation_rate' => $review_stats['total_reviews'] > 0 ?
                round(($review_stats['would_recommend_count'] / $review_stats['total_reviews']) * 100, 1) : 0
        ];

        // Rating distribution
        $distribution = $wpdb->get_results($wpdb->prepare(
            "SELECT rating, COUNT(*) as count
             FROM {$table}
             WHERE vendor_profile_id = %d AND is_published = 1
             GROUP BY rating
             ORDER BY rating DESC",
            $vendor_id
        ), ARRAY_A);

        $stats['rating_distribution'] = array_combine(
            array_column($distribution, 'rating'),
            array_column($distribution, 'count')
        );

        return $stats;
    }

    /**
     * Import existing vendor requests to profiles
     */
    public function importExistingVendors(): array {
        global $wpdb;
        $requests_table = $wpdb->prefix . 'ma_deal_vendor_requests';

        // Get unique vendors from requests
        $vendors = $wpdb->get_results(
            "SELECT DISTINCT vendor_email, vendor_name, vendor_company, vendor_phone, vendor_type
             FROM {$requests_table}
             WHERE vendor_email IS NOT NULL AND vendor_email != ''",
            ARRAY_A
        );

        $imported = 0;
        $skipped = 0;

        foreach ($vendors as $vendor_data) {
            $profile = $this->vendor_profile_repository->createFromRequest($vendor_data);
            if ($profile) {
                $imported++;
            } else {
                $skipped++;
            }
        }

        return [
            'imported' => $imported,
            'skipped' => $skipped,
            'total' => count($vendors)
        ];
    }

    /**
     * Create a new vendor
     */
    public function createVendor(array $data): ?int {
        global $wpdb;

        // Check if vendor with this email already exists
        $existing = $this->vendor_profile_repository->findByEmail($data['email']);
        if ($existing) {
            throw new \Exception('A vendor with this email already exists');
        }

        // Prepare vendor data
        $vendor_data = [
            'email' => $data['email'],
            'name' => $data['name'],
            'company' => $data['company'] ?? '',
            'phone' => $data['phone'] ?? '',
            'website' => $data['website'] ?? '',
            'address' => $data['address'] ?? '',
            'city' => $data['city'] ?? '',
            'state' => $data['state'] ?? '',
            'zip' => $data['zip'] ?? '',
            'license_number' => $data['license_number'] ?? '',
            'license_state' => $data['license_state'] ?? '',
            'insurance_expires' => $data['insurance_expires'] ?? null,
            'bio' => $data['bio'] ?? '',
            'years_experience' => $data['years_experience'] ?? null,
            'service_areas' => isset($data['service_areas']) ? json_encode($data['service_areas']) : null,
            'certifications' => isset($data['certifications']) ? json_encode($data['certifications']) : null,
            'languages' => isset($data['languages']) ? json_encode($data['languages']) : null,
            'average_rating' => 0,
            'total_reviews' => 0,
            'total_completed' => 0,
            'is_verified' => $data['is_verified'] ?? 0,
            'is_active' => $data['is_active'] ?? 1,
            'created_at' => current_time('mysql'),
            'updated_at' => current_time('mysql')
        ];

        // Create vendor profile
        $vendor_id = $this->vendor_profile_repository->create($vendor_data);

        if ($vendor_id && !empty($data['vendor_type'])) {
            // Add vendor category
            $wpdb->insert(
                $wpdb->prefix . 'ma_deal_vendor_categories',
                [
                    'vendor_profile_id' => $vendor_id,
                    'vendor_type' => $data['vendor_type'],
                    'is_primary' => 1,
                    'created_at' => current_time('mysql')
                ]
            );
        }

        return $vendor_id;
    }
}