<?php

namespace MADealRoom\REST\Controllers;

use WP_REST_Request;
use WP_REST_Response;
use MADealRoom\Services\VendorNetworkService;
use MADealRoom\Services\NotificationService;

/**
 * Vendor Network REST Controller
 *
 * Handles vendor directory and network management endpoints
 *
 * @package MADealRoom
 * @since 2.1.0
 */
class VendorNetworkController extends BaseController {
    protected $rest_base = 'vendor-network';
    private VendorNetworkService $vendor_network_service;
    private NotificationService $notification_service;

    public function __construct(
        VendorNetworkService $vendor_network_service,
        NotificationService $notification_service
    ) {
        parent::__construct(); // Initialize base controller
        $this->vendor_network_service = $vendor_network_service;
        $this->notification_service = $notification_service;
    }

    /**
     * Register routes
     */
    public function register_routes(): void {
        // Search vendors
        register_rest_route($this->namespace, '/' . $this->rest_base . '/search', [
            'methods' => 'GET',
            'callback' => [$this, 'search_vendors'],
            'permission_callback' => [$this, 'permission_callback'],
            'args' => [
                'search' => [
                    'type' => 'string',
                    'description' => 'Search term for name, company, or bio'
                ],
                'vendor_type' => [
                    'type' => 'string',
                    'description' => 'Filter by vendor type'
                ],
                'state' => [
                    'type' => 'string',
                    'description' => 'Filter by state'
                ],
                'city' => [
                    'type' => 'string',
                    'description' => 'Filter by city'
                ],
                'min_rating' => [
                    'type' => 'number',
                    'description' => 'Minimum rating filter'
                ],
                'verified_only' => [
                    'type' => 'boolean',
                    'description' => 'Show only verified vendors'
                ]
            ]
        ]);

        // Get single vendor profile
        register_rest_route($this->namespace, '/' . $this->rest_base . '/(?P<id>\d+)', [
            'methods' => 'GET',
            'callback' => [$this, 'get_vendor_profile'],
            'permission_callback' => [$this, 'permission_callback'],
            'args' => [
                'id' => [
                    'validate_callback' => function($param) {
                        return is_numeric($param);
                    }
                ]
            ]
        ]);

        // Get preferred vendors
        register_rest_route($this->namespace, '/' . $this->rest_base . '/preferred', [
            'methods' => 'GET',
            'callback' => [$this, 'get_preferred_vendors'],
            'permission_callback' => [$this, 'permission_callback'],
            'args' => [
                'vendor_type' => [
                    'type' => 'string',
                    'description' => 'Filter by vendor type'
                ]
            ]
        ]);

        // Add to preferred
        register_rest_route($this->namespace, '/' . $this->rest_base . '/(?P<id>\d+)/prefer', [
            'methods' => 'POST',
            'callback' => [$this, 'add_to_preferred'],
            'permission_callback' => [$this, 'permission_callback']
        ]);

        // Remove from preferred
        register_rest_route($this->namespace, '/' . $this->rest_base . '/(?P<id>\d+)/prefer', [
            'methods' => 'DELETE',
            'callback' => [$this, 'remove_from_preferred'],
            'permission_callback' => [$this, 'permission_callback']
        ]);

        // Quick re-invite vendor
        register_rest_route($this->namespace, '/' . $this->rest_base . '/(?P<id>\d+)/reinvite', [
            'methods' => 'POST',
            'callback' => [$this, 'reinvite_vendor'],
            'permission_callback' => [$this, 'permission_callback']
        ]);

        // Create vendor review
        register_rest_route($this->namespace, '/' . $this->rest_base . '/(?P<id>\d+)/reviews', [
            [
                'methods' => 'GET',
                'callback' => [$this, 'get_vendor_reviews'],
                'permission_callback' => '__return_true'
            ],
            [
                'methods' => 'POST',
                'callback' => [$this, 'create_vendor_review'],
                'permission_callback' => [$this, 'permission_callback']
            ]
        ]);

        // Get vendor statistics
        register_rest_route($this->namespace, '/' . $this->rest_base . '/(?P<id>\d+)/stats', [
            'methods' => 'GET',
            'callback' => [$this, 'get_vendor_stats'],
            'permission_callback' => [$this, 'permission_callback']
        ]);

        // Import existing vendors
        register_rest_route($this->namespace, '/' . $this->rest_base . '/import', [
            'methods' => 'POST',
            'callback' => [$this, 'import_vendors'],
            'permission_callback' => [$this, 'admin_permission_callback']
        ]);

        // Create new vendor
        register_rest_route($this->namespace, '/' . $this->rest_base . '/create', [
            'methods' => 'POST',
            'callback' => [$this, 'create_vendor'],
            'permission_callback' => [$this, 'admin_permission_callback'],
            'args' => [
                'email' => [
                    'required' => true,
                    'type' => 'string',
                    'format' => 'email'
                ],
                'name' => [
                    'required' => true,
                    'type' => 'string'
                ],
                'vendor_type' => [
                    'required' => true,
                    'type' => 'string'
                ]
            ]
        ]);
    }

    /**
     * Search vendors
     */
    public function search_vendors(WP_REST_Request $request): WP_REST_Response {
        $filters = [
            'search' => $request->get_param('search'),
            'vendor_type' => $request->get_param('vendor_type'),
            'state' => $request->get_param('state'),
            'city' => $request->get_param('city'),
            'zip' => $request->get_param('zip'),
            'min_rating' => $request->get_param('min_rating'),
            'verified_only' => $request->get_param('verified_only')
        ];

        $options = [
            'order' => $request->get_param('order') ?? 'rating',
            'limit' => $request->get_param('per_page') ?? 20,
            'offset' => (($request->get_param('page') ?? 1) - 1) * ($request->get_param('per_page') ?? 20)
        ];

        try {
            $vendors = $this->vendor_network_service->searchVendors(
                array_filter($filters),
                $options
            );

            // Check preferred status for current user
            $user_id = get_current_user_id();
            foreach ($vendors as $vendor) {
                $vendor->is_preferred = $vendor->isPreferredBy($user_id);
            }

            return $this->success([
                'vendors' => array_map(fn($v) => $v->toArray(), $vendors),
                'total' => count($vendors)
            ]);
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 500);
        }
    }

    /**
     * Get vendor profile
     */
    public function get_vendor_profile(WP_REST_Request $request): WP_REST_Response {
        $vendor_id = (int)$request->get_param('id');

        try {
            $vendor = $this->vendor_network_service->getVendorProfile($vendor_id);

            if (!$vendor) {
                return $this->error('Vendor not found', 404);
            }

            $vendor->is_preferred = $vendor->isPreferredBy(get_current_user_id());

            return $this->success([
                'vendor' => $vendor->toArray()
            ]);
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 500);
        }
    }

    /**
     * Get preferred vendors
     */
    public function get_preferred_vendors(WP_REST_Request $request): WP_REST_Response {
        $user_id = get_current_user_id();
        $vendor_type = $request->get_param('vendor_type');

        try {
            $vendors = $this->vendor_network_service->getPreferredVendors($user_id, $vendor_type);

            return $this->success([
                'vendors' => array_map(fn($v) => $v->toArray(), $vendors),
                'total' => count($vendors)
            ]);
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 500);
        }
    }

    /**
     * Add vendor to preferred list
     */
    public function add_to_preferred(WP_REST_Request $request): WP_REST_Response {
        $vendor_id = (int)$request->get_param('id');
        $user_id = get_current_user_id();

        $data = [
            'vendor_type' => $request->get_param('vendor_type') ?? 'general',
            'notes' => $request->get_param('notes'),
            'tags' => $request->get_param('tags')
        ];

        try {
            $result = $this->vendor_network_service->addToPreferred(
                $user_id,
                $vendor_id,
                $data['vendor_type'],
                $data
            );

            if ($result) {
                return $this->success(null, 'Vendor added to preferred list');
            } else {
                return $this->error('Failed to add vendor to preferred list', 500);
            }
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 500);
        }
    }

    /**
     * Remove vendor from preferred list
     */
    public function remove_from_preferred(WP_REST_Request $request): WP_REST_Response {
        $vendor_id = (int)$request->get_param('id');
        $user_id = get_current_user_id();

        try {
            $result = $this->vendor_network_service->removeFromPreferred($user_id, $vendor_id);

            if ($result) {
                return $this->success(null, 'Vendor removed from preferred list');
            } else {
                return $this->error('Vendor not in preferred list', 404);
            }
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 500);
        }
    }

    /**
     * Quick re-invite vendor
     */
    public function reinvite_vendor(WP_REST_Request $request): WP_REST_Response {
        $vendor_id = (int)$request->get_param('id');

        // Validate required fields
        $required = ['transaction_id', 'vendor_type'];
        foreach ($required as $field) {
            if (empty($request->get_param($field))) {
                return $this->error("Missing required field: {$field}", 400);
            }
        }

        $request_data = [
            'transaction_id' => (int)$request->get_param('transaction_id'),
            'task_id' => $request->get_param('task_id') ? (int)$request->get_param('task_id') : null,
            'vendor_type' => sanitize_text_field($request->get_param('vendor_type')),
            'notes' => $request->get_param('notes')
        ];

        try {
            $result = $this->vendor_network_service->reinviteVendor($vendor_id, $request_data);

            // Send email notification
            if ($result['vendor_request'] && $result['portal_url']) {
                $email_sent = $this->notification_service->sendVendorRequest(
                    $result['vendor_request'],
                    $result['portal_url']
                );
                $result['email_sent'] = $email_sent;
            }

            // Log event
            $this->logEvent(
                'vendor_reinvite',
                $vendor_id,
                'reinvited',
                [],
                $request_data,
                null,
                $request_data['transaction_id']
            );

            return $this->success($result, 'Vendor invitation sent successfully');
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 500);
        }
    }

    /**
     * Get vendor reviews
     */
    public function get_vendor_reviews(WP_REST_Request $request): WP_REST_Response {
        $vendor_id = (int)$request->get_param('id');

        $options = [
            'limit' => $request->get_param('per_page') ?? 10,
            'offset' => (($request->get_param('page') ?? 1) - 1) * ($request->get_param('per_page') ?? 10)
        ];

        try {
            $reviews = $this->vendor_network_service->getVendorReviews($vendor_id, $options);

            return $this->success([
                'reviews' => $reviews,
                'total' => count($reviews)
            ]);
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 500);
        }
    }

    /**
     * Create vendor review
     */
    public function create_vendor_review(WP_REST_Request $request): WP_REST_Response {
        $vendor_id = (int)$request->get_param('id');

        $review_data = [
            'vendor_request_id' => $request->get_param('vendor_request_id'),
            'rating' => (int)$request->get_param('rating'),
            'review_text' => sanitize_textarea_field($request->get_param('review_text')),
            'service_type' => $request->get_param('service_type'),
            'professionalism' => $request->get_param('professionalism'),
            'communication' => $request->get_param('communication'),
            'timeliness' => $request->get_param('timeliness'),
            'value' => $request->get_param('value'),
            'would_recommend' => $request->get_param('would_recommend')
        ];

        // Validate rating
        if ($review_data['rating'] < 1 || $review_data['rating'] > 5) {
            return $this->error('Rating must be between 1 and 5', 400);
        }

        try {
            $result = $this->vendor_network_service->createReview($vendor_id, $review_data);

            if ($result) {
                return $this->success(null, 'Review submitted successfully');
            } else {
                return $this->error('Failed to submit review', 500);
            }
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 500);
        }
    }

    /**
     * Get vendor statistics
     */
    public function get_vendor_stats(WP_REST_Request $request): WP_REST_Response {
        $vendor_id = (int)$request->get_param('id');

        try {
            $stats = $this->vendor_network_service->getVendorStats($vendor_id);

            return $this->success([
                'stats' => $stats
            ]);
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 500);
        }
    }

    /**
     * Import existing vendors
     */
    public function import_vendors(WP_REST_Request $request): WP_REST_Response {
        try {
            $result = $this->vendor_network_service->importExistingVendors();

            return $this->success($result, 'Vendor import completed');
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 500);
        }
    }

    /**
     * Create new vendor
     */
    public function create_vendor(WP_REST_Request $request): WP_REST_Response {
        $data = [
            'email' => sanitize_email($request->get_param('email')),
            'name' => sanitize_text_field($request->get_param('name')),
            'company' => sanitize_text_field($request->get_param('company')),
            'phone' => sanitize_text_field($request->get_param('phone')),
            'website' => esc_url_raw($request->get_param('website')),
            'address' => sanitize_text_field($request->get_param('address')),
            'city' => sanitize_text_field($request->get_param('city')),
            'state' => sanitize_text_field($request->get_param('state')),
            'zip' => sanitize_text_field($request->get_param('zip')),
            'vendor_type' => sanitize_text_field($request->get_param('vendor_type')),
            'license_number' => sanitize_text_field($request->get_param('license_number')),
            'license_state' => sanitize_text_field($request->get_param('license_state')),
            'insurance_expires' => $request->get_param('insurance_expires'),
            'years_experience' => intval($request->get_param('years_experience')),
            'bio' => sanitize_textarea_field($request->get_param('bio')),
            'service_areas' => $request->get_param('service_areas'),
            'certifications' => $request->get_param('certifications'),
            'languages' => $request->get_param('languages'),
            'is_verified' => (bool)$request->get_param('is_verified'),
            'is_active' => $request->get_param('is_active') !== false ? (bool)$request->get_param('is_active') : true,
        ];

        try {
            $vendor_id = $this->vendor_network_service->createVendor($data);

            if ($vendor_id) {
                // Log the event
                $this->logEvent(
                    'vendor_created',
                    $vendor_id,
                    'created',
                    [],
                    $data
                );

                return $this->success([
                    'vendor_id' => $vendor_id,
                    'message' => 'Vendor created successfully'
                ], 'Vendor created successfully');
            } else {
                return $this->error('Failed to create vendor', 500);
            }
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 400);
        }
    }

    /**
     * Admin permission callback
     */
    public function admin_permission_callback(): bool {
        return current_user_can('manage_options');
    }
}