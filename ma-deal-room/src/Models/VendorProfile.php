<?php

namespace MADealRoom\Models;

/**
 * Vendor Profile Model
 *
 * Represents a vendor in the network directory
 *
 * @package MADealRoom
 * @since 2.1.0
 */
class VendorProfile extends BaseModel {
    protected $table = 'ma_deal_vendor_profiles';

    protected $fillable = [
        'email',
        'name',
        'company',
        'phone',
        'website',
        'address',
        'city',
        'state',
        'zip',
        'license_number',
        'license_state',
        'insurance_expires',
        'bio',
        'profile_photo',
        'years_experience',
        'service_areas',
        'certifications',
        'languages',
        'average_rating',
        'total_reviews',
        'total_completed',
        'response_time_hours',
        'completion_rate',
        'is_verified',
        'verified_at',
        'is_active',
        'metadata'
    ];

    protected $casts = [
        'service_areas' => 'array',
        'certifications' => 'array',
        'languages' => 'array',
        'metadata' => 'array',
        'is_verified' => 'boolean',
        'is_active' => 'boolean',
        'average_rating' => 'float',
        'completion_rate' => 'float'
    ];

    /**
     * Get vendor categories
     */
    public function getCategories(): array {
        global $wpdb;
        $table = $wpdb->prefix . 'ma_deal_vendor_categories';

        $categories = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM {$table} WHERE vendor_profile_id = %d ORDER BY is_primary DESC",
            $this->id
        ), ARRAY_A);

        return array_map(function($cat) {
            $cat['specialties'] = json_decode($cat['specialties'], true) ?: [];
            return $cat;
        }, $categories);
    }

    /**
     * Get recent reviews
     */
    public function getRecentReviews($limit = 5): array {
        global $wpdb;
        $table = $wpdb->prefix . 'ma_deal_vendor_reviews';

        $reviews = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM {$table}
             WHERE vendor_profile_id = %d
             AND is_published = 1
             ORDER BY created_at DESC
             LIMIT %d",
            $this->id,
            $limit
        ), ARRAY_A);

        return $reviews;
    }

    /**
     * Check if vendor is preferred by user
     */
    public function isPreferredBy($user_id): bool {
        global $wpdb;
        $table = $wpdb->prefix . 'ma_deal_preferred_vendors';

        $exists = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$table} WHERE user_id = %d AND vendor_profile_id = %d",
            $user_id,
            $this->id
        ));

        return $exists > 0;
    }

    /**
     * Get completion statistics
     */
    public function getStats(): array {
        global $wpdb;
        $requests_table = $wpdb->prefix . 'ma_deal_vendor_requests';

        // Get statistics from vendor requests
        $stats = $wpdb->get_row($wpdb->prepare("
            SELECT
                COUNT(*) as total_requests,
                SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed_requests,
                AVG(CASE
                    WHEN status = 'completed' AND scheduled_date IS NOT NULL
                    THEN DATEDIFF(completion_date, scheduled_date)
                    ELSE NULL
                END) as avg_completion_days,
                AVG(TIMESTAMPDIFF(HOUR, created_at,
                    CASE WHEN last_opened_at IS NOT NULL
                    THEN last_opened_at
                    ELSE updated_at END
                )) as avg_response_hours
            FROM {$requests_table}
            WHERE vendor_email = %s
        ", $this->email), ARRAY_A);

        return [
            'total_requests' => (int)$stats['total_requests'],
            'completed_requests' => (int)$stats['completed_requests'],
            'completion_rate' => $stats['total_requests'] > 0 ?
                round(($stats['completed_requests'] / $stats['total_requests']) * 100, 1) : 0,
            'avg_completion_days' => round($stats['avg_completion_days'] ?? 0, 1),
            'avg_response_hours' => round($stats['avg_response_hours'] ?? 0, 1)
        ];
    }

    /**
     * Format vendor for display
     */
    public function toArray(): array {
        $data = parent::toArray();

        // Add computed fields
        $data['display_name'] = $this->company ?: $this->name;
        $data['location'] = trim("{$this->city}, {$this->state} {$this->zip}", ', ');
        $data['has_insurance'] = !empty($this->insurance_expires) &&
            strtotime($this->insurance_expires) > time();
        $data['has_license'] = !empty($this->license_number);

        return $data;
    }
}