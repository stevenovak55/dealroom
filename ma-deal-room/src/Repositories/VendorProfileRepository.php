<?php

namespace MADealRoom\Repositories;

use MADealRoom\Models\VendorProfile;

/**
 * Vendor Profile Repository
 *
 * @package MADealRoom
 * @since 2.1.0
 */
class VendorProfileRepository extends BaseRepository {
    protected $table = 'ma_deal_vendor_profiles';
    protected $model_class = VendorProfile::class;

    protected $allowed_columns = [
        'id', 'email', 'name', 'company', 'phone', 'website',
        'address', 'city', 'state', 'zip', 'license_number',
        'license_state', 'insurance_expires', 'bio', 'profile_photo',
        'years_experience', 'service_areas', 'certifications', 'languages',
        'average_rating', 'total_reviews', 'total_completed',
        'response_time_hours', 'completion_rate', 'is_verified',
        'verified_at', 'is_active', 'metadata', 'created_at', 'updated_at'
    ];

    /**
     * Find vendor by email
     */
    public function findByEmail(string $email): ?VendorProfile {
        $data = $this->wpdb->get_row(
            $this->wpdb->prepare(
                "SELECT * FROM {$this->get_table_name()} WHERE email = %s AND is_active = 1",
                $email
            ),
            ARRAY_A
        );

        return $data ? new $this->model_class($data) : null;
    }

    /**
     * Search vendors with filters
     */
    public function searchVendors(array $filters = [], array $options = []): array {
        $table = $this->get_table_name();
        $categories_table = $this->wpdb->prefix . 'ma_deal_vendor_categories';

        // Build WHERE conditions
        $where = ['p.is_active = 1'];
        $params = [];

        // Search term (name, company, bio)
        if (!empty($filters['search'])) {
            $search = '%' . $this->wpdb->esc_like($filters['search']) . '%';
            $where[] = "(p.name LIKE %s OR p.company LIKE %s OR p.bio LIKE %s)";
            $params[] = $search;
            $params[] = $search;
            $params[] = $search;
        }

        // Vendor type filter
        if (!empty($filters['vendor_type'])) {
            $where[] = "EXISTS (
                SELECT 1 FROM {$categories_table} c
                WHERE c.vendor_profile_id = p.id
                AND c.vendor_type = %s
            )";
            $params[] = $filters['vendor_type'];
        }

        // Location filters
        if (!empty($filters['state'])) {
            $where[] = "p.state = %s";
            $params[] = $filters['state'];
        }

        if (!empty($filters['city'])) {
            $where[] = "p.city = %s";
            $params[] = $filters['city'];
        }

        if (!empty($filters['zip'])) {
            $where[] = "p.zip = %s";
            $params[] = $filters['zip'];
        }

        // Rating filter
        if (!empty($filters['min_rating'])) {
            $where[] = "p.average_rating >= %f";
            $params[] = (float)$filters['min_rating'];
        }

        // Verified only
        if (!empty($filters['verified_only'])) {
            $where[] = "p.is_verified = 1";
        }

        // Build ORDER BY
        $order_by = 'p.average_rating DESC, p.total_reviews DESC';
        if (!empty($options['order'])) {
            $allowed_orders = [
                'rating' => 'p.average_rating DESC',
                'reviews' => 'p.total_reviews DESC',
                'name' => 'p.name ASC',
                'recent' => 'p.created_at DESC'
            ];
            $order_by = $allowed_orders[$options['order']] ?? $order_by;
        }

        // Pagination
        $limit = isset($options['limit']) ? (int)$options['limit'] : 20;
        $offset = isset($options['offset']) ? (int)$options['offset'] : 0;

        // Build query
        $where_clause = implode(' AND ', $where);
        $query = "SELECT p.* FROM {$table} p WHERE {$where_clause} ORDER BY {$order_by} LIMIT %d OFFSET %d";
        $params[] = $limit;
        $params[] = $offset;

        // Execute query
        $results = $this->wpdb->get_results(
            $this->wpdb->prepare($query, $params),
            ARRAY_A
        );

        // Convert to models
        return array_map(function($data) {
            $vendor = new $this->model_class($data);
            // Add categories
            $vendor->categories = $vendor->getCategories();
            return $vendor;
        }, $results);
    }

    /**
     * Get preferred vendors for user
     */
    public function getPreferredVendors(int $user_id, ?string $vendor_type = null): array {
        $table = $this->get_table_name();
        $preferred_table = $this->wpdb->prefix . 'ma_deal_preferred_vendors';

        $where = ['pv.user_id = %d', 'p.is_active = 1'];
        $params = [$user_id];

        if ($vendor_type) {
            $where[] = 'pv.vendor_type = %s';
            $params[] = $vendor_type;
        }

        $where_clause = implode(' AND ', $where);
        $query = "
            SELECT p.*, pv.preference_order, pv.notes, pv.tags, pv.last_used_at, pv.times_used
            FROM {$preferred_table} pv
            JOIN {$table} p ON p.id = pv.vendor_profile_id
            WHERE {$where_clause}
            ORDER BY pv.preference_order ASC, pv.times_used DESC
        ";

        $results = $this->wpdb->get_results(
            $this->wpdb->prepare($query, $params),
            ARRAY_A
        );

        return array_map(function($data) {
            $vendor = new $this->model_class($data);
            $vendor->preference_order = $data['preference_order'];
            $vendor->preferred_notes = $data['notes'];
            $vendor->tags = json_decode($data['tags'], true) ?: [];
            $vendor->last_used_at = $data['last_used_at'];
            $vendor->times_used = $data['times_used'];
            $vendor->categories = $vendor->getCategories();
            return $vendor;
        }, $results);
    }

    /**
     * Create or update vendor profile from request
     */
    public function createFromRequest(array $request_data): ?VendorProfile {
        // Check if vendor already exists
        $existing = $this->findByEmail($request_data['vendor_email']);

        if ($existing) {
            // Update existing profile with new info if provided
            $update_data = [];
            if (!empty($request_data['vendor_name']) && empty($existing->name)) {
                $update_data['name'] = $request_data['vendor_name'];
            }
            if (!empty($request_data['vendor_company']) && empty($existing->company)) {
                $update_data['company'] = $request_data['vendor_company'];
            }
            if (!empty($request_data['vendor_phone']) && empty($existing->phone)) {
                $update_data['phone'] = $request_data['vendor_phone'];
            }

            if (!empty($update_data)) {
                $this->update($existing->id, $update_data);
            }

            // Increment total completed if request is completed
            if ($request_data['status'] === 'completed') {
                $this->update($existing->id, [
                    'total_completed' => $existing->total_completed + 1
                ]);
            }

            return $existing;
        }

        // Create new vendor profile
        $profile_data = [
            'email' => $request_data['vendor_email'],
            'name' => $request_data['vendor_name'] ?? '',
            'company' => $request_data['vendor_company'] ?? '',
            'phone' => $request_data['vendor_phone'] ?? '',
            'is_active' => 1,
            'created_at' => current_time('mysql'),
            'updated_at' => current_time('mysql')
        ];

        $profile_id = $this->create($profile_data);

        if ($profile_id) {
            // Add vendor category if provided
            if (!empty($request_data['vendor_type'])) {
                $this->wpdb->insert(
                    $this->wpdb->prefix . 'ma_deal_vendor_categories',
                    [
                        'vendor_profile_id' => $profile_id,
                        'vendor_type' => $request_data['vendor_type'],
                        'is_primary' => 1,
                        'created_at' => current_time('mysql')
                    ]
                );
            }

            return $this->find($profile_id);
        }

        return null;
    }

    /**
     * Add vendor to user's preferred list
     */
    public function addToPreferred(int $user_id, int $vendor_id, string $vendor_type, array $data = []): bool {
        $table = $this->wpdb->prefix . 'ma_deal_preferred_vendors';

        // Check if already preferred
        $existing = $this->wpdb->get_var($this->wpdb->prepare(
            "SELECT id FROM {$table} WHERE user_id = %d AND vendor_profile_id = %d AND vendor_type = %s",
            $user_id,
            $vendor_id,
            $vendor_type
        ));

        if ($existing) {
            // Update existing
            return $this->wpdb->update(
                $table,
                [
                    'notes' => $data['notes'] ?? null,
                    'tags' => isset($data['tags']) ? json_encode($data['tags']) : null,
                    'updated_at' => current_time('mysql')
                ],
                ['id' => $existing]
            ) !== false;
        }

        // Get next preference order
        $max_order = $this->wpdb->get_var($this->wpdb->prepare(
            "SELECT MAX(preference_order) FROM {$table} WHERE user_id = %d AND vendor_type = %s",
            $user_id,
            $vendor_type
        ));

        // Insert new
        return $this->wpdb->insert(
            $table,
            [
                'user_id' => $user_id,
                'vendor_profile_id' => $vendor_id,
                'vendor_type' => $vendor_type,
                'preference_order' => ($max_order ?? 0) + 1,
                'notes' => $data['notes'] ?? null,
                'tags' => isset($data['tags']) ? json_encode($data['tags']) : null,
                'created_at' => current_time('mysql'),
                'updated_at' => current_time('mysql')
            ]
        ) !== false;
    }

    /**
     * Remove vendor from preferred list
     */
    public function removeFromPreferred(int $user_id, int $vendor_id): bool {
        return $this->wpdb->delete(
            $this->wpdb->prefix . 'ma_deal_preferred_vendors',
            [
                'user_id' => $user_id,
                'vendor_profile_id' => $vendor_id
            ]
        ) !== false;
    }
}