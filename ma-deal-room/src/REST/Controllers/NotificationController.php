<?php
/**
 * Notification REST Controller
 *
 * @package MADealRoom\REST\Controllers
 * @since 1.0.0
 */

namespace MADealRoom\REST\Controllers;

use MADealRoom\Repositories\NotificationRepository;
use WP_REST_Request;

class NotificationController extends BaseController {
	protected $rest_base = 'notifications';
	private $repository;

	public function __construct(NotificationRepository $repository) {
		parent::__construct(null); // No event logging for notifications
		$this->repository = $repository;
	}

	public function register_routes(): void {
		// GET /notifications - List notifications for current user
		register_rest_route($this->namespace, '/' . $this->rest_base, [
			'methods' => 'GET',
			'callback' => [$this, 'get_items'],
			'permission_callback' => [$this, 'permission_callback']
		]);

		// PUT /notifications/{id}/read - Mark notification as read
		register_rest_route($this->namespace, '/' . $this->rest_base . '/(?P<id>[\d]+)/read', [
			'methods' => 'PUT',
			'callback' => [$this, 'mark_as_read'],
			'permission_callback' => [$this, 'permission_callback']
		]);

		// PUT /notifications/mark-all-read - Mark all as read
		register_rest_route($this->namespace, '/' . $this->rest_base . '/mark-all-read', [
			'methods' => 'PUT',
			'callback' => [$this, 'mark_all_as_read'],
			'permission_callback' => [$this, 'permission_callback']
		]);

		// GET /notifications/unread-count - Get unread count
		register_rest_route($this->namespace, '/' . $this->rest_base . '/unread-count', [
			'methods' => 'GET',
			'callback' => [$this, 'get_unread_count'],
			'permission_callback' => [$this, 'permission_callback']
		]);
	}

	public function get_items(WP_REST_Request $request) {
		$user_id = get_current_user_id();
		$unread_only = $request->get_param('unread_only') === 'true';
		$limit = $request->get_param('limit') ? (int) $request->get_param('limit') : 50;

		$notifications = $this->repository->findByUser($user_id, $unread_only, $limit);

		$notifications_array = array_map(function($notification) {
			return $notification->toArray();
		}, $notifications);

		return $this->success([
			'data' => $notifications_array,
			'total' => count($notifications_array)
		]);
	}

	public function mark_as_read(WP_REST_Request $request) {
		$id = (int) $request->get_param('id');
		$user_id = get_current_user_id();

		// Verify notification belongs to current user
		$notification = $this->repository->find($id);
		if (!$notification || $notification->user_id !== $user_id) {
			return $this->error('Notification not found', 404);
		}

		$success = $this->repository->markAsRead($id);

		if (!$success) {
			return $this->error('Failed to mark notification as read', 500);
		}

		return $this->success(null, 'Notification marked as read');
	}

	public function mark_all_as_read(WP_REST_Request $request) {
		$user_id = get_current_user_id();
		$success = $this->repository->markAllAsRead($user_id);

		if (!$success) {
			return $this->error('Failed to mark notifications as read', 500);
		}

		return $this->success(null, 'All notifications marked as read');
	}

	public function get_unread_count(WP_REST_Request $request) {
		$user_id = get_current_user_id();
		$count = $this->repository->getUnreadCount($user_id);

		return $this->success(['count' => $count]);
	}
}
