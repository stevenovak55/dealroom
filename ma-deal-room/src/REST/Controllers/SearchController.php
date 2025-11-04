<?php
/**
 * Search REST Controller
 *
 * Handles global search across all entities
 *
 * @package MADealRoom\REST\Controllers
 * @since 1.0.0
 */

namespace MADealRoom\REST\Controllers;

use WP_REST_Request;
use WP_REST_Response;
use WP_Error;

/**
 * Search controller
 */
class SearchController extends BaseController {

	/**
	 * Register REST API routes
	 *
	 * @return void
	 */
	public function register_routes(): void {
		$this->rest_base = 'search';

		// GET /search - Global search
		register_rest_route( $this->namespace, '/' . $this->rest_base, [
			'methods' => 'GET',
			'callback' => [ $this, 'search' ],
			'permission_callback' => [ $this, 'permission_callback' ],
			'args' => [
				'q' => [
					'required' => true,
					'type' => 'string',
					'description' => 'Search query',
					'sanitize_callback' => 'sanitize_text_field',
				],
				'types' => [
					'required' => false,
					'type' => 'string',
					'description' => 'Comma-separated entity types to search (transactions,tasks,parties,documents,templates)',
					'default' => 'transactions,tasks,parties,documents,templates',
				],
				'limit' => [
					'required' => false,
					'type' => 'integer',
					'description' => 'Results per type',
					'default' => 5,
				],
			],
		] );
	}

	/**
	 * Global search across all entities
	 *
	 * @param WP_REST_Request $request Request object
	 * @return WP_REST_Response|WP_Error
	 */
	public function search( WP_REST_Request $request ) {
		try {
			$query = $request->get_param( 'q' );
			$types = explode( ',', $request->get_param( 'types' ) );
			$limit = (int) $request->get_param( 'limit' );
			$account_id = $this->get_user_account_id();

			if ( ! $account_id ) {
				return new WP_Error(
					'no_account',
					'No account found for current user',
					[ 'status' => 404 ]
				);
			}

			if ( strlen( $query ) < 2 ) {
				return $this->success( [
					'transactions' => [],
					'tasks' => [],
					'parties' => [],
					'documents' => [],
					'templates' => [],
					'total' => 0,
				] );
			}

			global $wpdb;
			$results = [];
			$total = 0;

			// Search transactions
			if ( in_array( 'transactions', $types, true ) ) {
				$results['transactions'] = $this->search_transactions( $query, $limit, $account_id );
				$total += count( $results['transactions'] );
			}

			// Search tasks
			if ( in_array( 'tasks', $types, true ) ) {
				$results['tasks'] = $this->search_tasks( $query, $limit, $account_id );
				$total += count( $results['tasks'] );
			}

			// Search parties
			if ( in_array( 'parties', $types, true ) ) {
				$results['parties'] = $this->search_parties( $query, $limit, $account_id );
				$total += count( $results['parties'] );
			}

			// Search documents
			if ( in_array( 'documents', $types, true ) ) {
				$results['documents'] = $this->search_documents( $query, $limit, $account_id );
				$total += count( $results['documents'] );
			}

			// Search templates
			if ( in_array( 'templates', $types, true ) ) {
				$results['templates'] = $this->search_templates( $query, $limit, $account_id );
				$total += count( $results['templates'] );
			}

			$results['total'] = $total;

			return $this->success( $results );

		} catch ( \Exception $e ) {
			return $this->error(
				'Error searching: ' . $e->getMessage(),
				500,
				'search_failed'
			);
		}
	}

	/**
	 * Search transactions
	 *
	 * @param string $query Search query
	 * @param int $limit Results limit
	 * @param int $account_id Account ID
	 * @return array Results
	 */
	private function search_transactions( string $query, int $limit, int $account_id ): array {
		global $wpdb;
		$table = $wpdb->prefix . 'ma_deal_transactions';
		$search_term = '%' . $wpdb->esc_like( $query ) . '%';

		$results = $wpdb->get_results( $wpdb->prepare(
			"SELECT
				transaction_id,
				property_address,
				property_city,
				property_state,
				property_type,
				status,
				sale_price
			FROM {$table}
			WHERE account_id = %d
			AND (
				property_address LIKE %s
				OR property_city LIKE %s
				OR property_state LIKE %s
				OR notes LIKE %s
			)
			ORDER BY
				CASE
					WHEN property_address LIKE %s THEN 1
					WHEN property_city LIKE %s THEN 2
					ELSE 3
				END,
				created_at DESC
			LIMIT %d",
			$account_id,
			$search_term,
			$search_term,
			$search_term,
			$search_term,
			$search_term,
			$search_term,
			$limit
		), ARRAY_A );

		return array_map( function( $result ) {
			return [
				'id' => (int) $result['transaction_id'],
				'type' => 'transaction',
				'title' => $result['property_address'],
				'subtitle' => $result['property_city'] . ', ' . $result['property_state'],
				'meta' => ucfirst( $result['property_type'] ) . ' - ' . ucfirst( $result['status'] ),
				'url' => '/transactions/' . $result['transaction_id'],
			];
		}, $results ?: [] );
	}

	/**
	 * Search tasks
	 *
	 * @param string $query Search query
	 * @param int $limit Results limit
	 * @param int $account_id Account ID
	 * @return array Results
	 */
	private function search_tasks( string $query, int $limit, int $account_id ): array {
		global $wpdb;
		$tasks_table = $wpdb->prefix . 'ma_deal_tasks';
		$transactions_table = $wpdb->prefix . 'ma_deal_transactions';
		$search_term = '%' . $wpdb->esc_like( $query ) . '%';

		$results = $wpdb->get_results( $wpdb->prepare(
			"SELECT
				t.task_id,
				t.title,
				t.description,
				t.status,
				t.transaction_id,
				tr.property_address
			FROM {$tasks_table} t
			INNER JOIN {$transactions_table} tr ON t.transaction_id = tr.transaction_id
			WHERE tr.account_id = %d
			AND (
				t.title LIKE %s
				OR t.description LIKE %s
			)
			ORDER BY
				CASE
					WHEN t.title LIKE %s THEN 1
					ELSE 2
				END,
				t.created_at DESC
			LIMIT %d",
			$account_id,
			$search_term,
			$search_term,
			$search_term,
			$limit
		), ARRAY_A );

		return array_map( function( $result ) {
			return [
				'id' => (int) $result['task_id'],
				'type' => 'task',
				'title' => $result['title'],
				'subtitle' => $result['property_address'],
				'meta' => ucfirst( $result['status'] ),
				'url' => '/transactions/' . $result['transaction_id'] . '?tab=tasks',
			];
		}, $results ?: [] );
	}

	/**
	 * Search parties
	 *
	 * @param string $query Search query
	 * @param int $limit Results limit
	 * @param int $account_id Account ID
	 * @return array Results
	 */
	private function search_parties( string $query, int $limit, int $account_id ): array {
		global $wpdb;
		$parties_table = $wpdb->prefix . 'ma_deal_parties';
		$transactions_table = $wpdb->prefix . 'ma_deal_transactions';
		$search_term = '%' . $wpdb->esc_like( $query ) . '%';

		$results = $wpdb->get_results( $wpdb->prepare(
			"SELECT
				p.id,
				p.contact_name,
				p.company_name,
				p.email,
				p.role,
				p.transaction_id,
				tr.property_address
			FROM {$parties_table} p
			INNER JOIN {$transactions_table} tr ON p.transaction_id = tr.transaction_id
			WHERE tr.account_id = %d
			AND (
				p.contact_name LIKE %s
				OR p.company_name LIKE %s
				OR p.email LIKE %s
			)
			ORDER BY
				CASE
					WHEN p.contact_name LIKE %s THEN 1
					WHEN p.company_name LIKE %s THEN 2
					ELSE 3
				END,
				p.created_at DESC
			LIMIT %d",
			$account_id,
			$search_term,
			$search_term,
			$search_term,
			$search_term,
			$search_term,
			$limit
		), ARRAY_A );

		return array_map( function( $result ) {
			return [
				'id' => (int) $result['id'],
				'type' => 'party',
				'title' => $result['contact_name'],
				'subtitle' => $result['company_name'] ?: $result['email'],
				'meta' => ucfirst( str_replace( '_', ' ', $result['role'] ) ) . ' - ' . $result['property_address'],
				'url' => '/transactions/' . $result['transaction_id'] . '?tab=parties',
			];
		}, $results ?: [] );
	}

	/**
	 * Search documents
	 *
	 * @param string $query Search query
	 * @param int $limit Results limit
	 * @param int $account_id Account ID
	 * @return array Results
	 */
	private function search_documents( string $query, int $limit, int $account_id ): array {
		global $wpdb;
		$documents_table = $wpdb->prefix . 'ma_deal_documents';
		$transactions_table = $wpdb->prefix . 'ma_deal_transactions';
		$search_term = '%' . $wpdb->esc_like( $query ) . '%';

		$results = $wpdb->get_results( $wpdb->prepare(
			"SELECT
				d.document_id,
				d.title,
				d.document_type,
				d.transaction_id,
				tr.property_address
			FROM {$documents_table} d
			INNER JOIN {$transactions_table} tr ON d.transaction_id = tr.transaction_id
			WHERE tr.account_id = %d
			AND (
				d.title LIKE %s
				OR d.notes LIKE %s
			)
			ORDER BY
				CASE
					WHEN d.title LIKE %s THEN 1
					ELSE 2
				END,
				d.created_at DESC
			LIMIT %d",
			$account_id,
			$search_term,
			$search_term,
			$search_term,
			$limit
		), ARRAY_A );

		return array_map( function( $result ) {
			return [
				'id' => (int) $result['document_id'],
				'type' => 'document',
				'title' => $result['title'],
				'subtitle' => $result['property_address'],
				'meta' => ucfirst( str_replace( '_', ' ', $result['document_type'] ) ),
				'url' => '/transactions/' . $result['transaction_id'] . '?tab=documents',
			];
		}, $results ?: [] );
	}

	/**
	 * Search templates
	 *
	 * @param string $query Search query
	 * @param int $limit Results limit
	 * @param int $account_id Account ID
	 * @return array Results
	 */
	private function search_templates( string $query, int $limit, int $account_id ): array {
		global $wpdb;
		$table = $wpdb->prefix . 'ma_deal_templates';
		$search_term = '%' . $wpdb->esc_like( $query ) . '%';

		$results = $wpdb->get_results( $wpdb->prepare(
			"SELECT
				template_id,
				name,
				description,
				is_system
			FROM {$table}
			WHERE (account_id = %d OR is_system = 1)
			AND (
				name LIKE %s
				OR description LIKE %s
			)
			ORDER BY
				CASE
					WHEN name LIKE %s THEN 1
					ELSE 2
				END,
				is_system DESC,
				created_at DESC
			LIMIT %d",
			$account_id,
			$search_term,
			$search_term,
			$search_term,
			$limit
		), ARRAY_A );

		return array_map( function( $result ) {
			return [
				'id' => (int) $result['template_id'],
				'type' => 'template',
				'title' => $result['name'],
				'subtitle' => $result['description'],
				'meta' => $result['is_system'] ? 'System Template' : 'Custom Template',
				'url' => '/templates',
			];
		}, $results ?: [] );
	}
}
