<?php
/**
 * Document REST Controller
 *
 * @package MADealRoom\REST\Controllers
 * @since 1.0.0
 */

namespace MADealRoom\REST\Controllers;

use MADealRoom\Repositories\DocumentRepository;
use MADealRoom\Repositories\TransactionRepository;
use MADealRoom\Repositories\PartyRepository;
use MADealRoom\Repositories\EventRepository;
use MADealRoom\Services\EmailService;
use MADealRoom\Services\FileSecurityService;
use MADealRoom\Services\FileStorageService;
use WP_REST_Request;
use WP_Error;

class DocumentController extends BaseController {
	protected $rest_base = 'documents';
	private $repository;
	private $transaction_repository;
	private $party_repository;
	private $email_service;
	private $file_security_service;
	private $file_storage_service;

	public function __construct(
		DocumentRepository $repository,
		TransactionRepository $transaction_repository,
		PartyRepository $party_repository,
		EventRepository $event_repository,
		EmailService $email_service,
		FileSecurityService $file_security_service,
		FileStorageService $file_storage_service
	) {
		parent::__construct($event_repository);
		$this->repository = $repository;
		$this->transaction_repository = $transaction_repository;
		$this->party_repository = $party_repository;
		$this->email_service = $email_service;
		$this->file_security_service = $file_security_service;
		$this->file_storage_service = $file_storage_service;
	}

	public function register_routes(): void {
		// GET & POST /documents - List and upload documents
		register_rest_route($this->namespace, '/' . $this->rest_base, [
			[
				'methods' => 'GET',
				'callback' => [$this, 'get_items'],
				'permission_callback' => [$this, 'permission_callback']
			],
			[
				'methods' => 'POST',
				'callback' => [$this, 'upload_document'],
				'permission_callback' => [$this, 'permission_callback'],
				'nonce_callback' => [$this, 'verify_nonce']
			],
		]);

		// GET, PUT, DELETE /documents/{id} - Single document operations
		register_rest_route($this->namespace, '/' . $this->rest_base . '/(?P<id>[\d]+)', [
			[
				'methods' => 'GET',
				'callback' => [$this, 'get_item'],
				'permission_callback' => [$this, 'permission_callback']
			],
			[
				'methods' => 'PUT',
				'callback' => [$this, 'update_document'],
				'permission_callback' => [$this, 'permission_callback'],
				'nonce_callback' => [$this, 'verify_nonce']
			],
			[
				'methods' => 'DELETE',
				'callback' => [$this, 'delete_document'],
				'permission_callback' => [$this, 'permission_callback'],
				'nonce_callback' => [$this, 'verify_nonce']
			],
		]);

		// GET /documents/{id}/download - Download file
		register_rest_route($this->namespace, '/' . $this->rest_base . '/(?P<id>[\d]+)/download', [
			'methods' => 'GET',
			'callback' => [$this, 'download_document'],
			'permission_callback' => [$this, 'permission_callback']
		]);

		// GET /public/documents/{token} - Public document access
		register_rest_route($this->namespace, '/public/' . $this->rest_base . '/(?P<token>[a-f0-9]+)', [
			'methods' => 'GET',
			'callback' => [$this, 'get_public_document'],
			'permission_callback' => '__return_true' // Public access
		]);
	}

	public function get_items(WP_REST_Request $request) {
		$transaction_id = $request->get_param('transaction_id');
		$document_type = $request->get_param('document_type');
		$limit = $request->get_param('per_page') ?? 100;

		// SECURITY: When filtering by transaction, verify account access
		if ($transaction_id && is_numeric($transaction_id)) {
			$transaction_id = (int) $transaction_id;

			// Verify transaction exists and user has access
			$transaction = $this->transaction_repository->find($transaction_id);
			if (!$transaction) {
				return $this->error('Transaction not found', 404);
			}
			if (!$this->verify_account_access($transaction->account_id)) {
				return $this->error('You do not have permission to access this transaction', 403);
			}

			if ($document_type) {
				$documents = $this->repository->findByType($transaction_id, $document_type);
			} else {
				$documents = $this->repository->findByTransaction($transaction_id);
			}
		} else {
			// SECURITY: Filter documents by current user's account
			$user_account_id = $this->get_user_account_id();
			if (!$user_account_id && !current_user_can('manage_options')) {
				return $this->success(['data' => [], 'pagination' => ['total' => 0, 'page' => 1, 'per_page' => $limit, 'total_pages' => 0]]);
			}

			// Get all documents and filter by account
			$all_documents = $this->repository->findAll($limit * 10); // Get more to filter
			$documents = array_filter($all_documents, function($doc) use ($user_account_id) {
				if (current_user_can('manage_options')) {
					return true; // Admins see all
				}
				return $doc->account_id === $user_account_id;
			});
			$documents = array_slice($documents, 0, $limit);
		}

		// Convert models to arrays and add download URLs
		$documents_array = array_map(function($doc) {
			$array = $doc->toArray();
			$array['download_url'] = rest_url($this->namespace . '/' . $this->rest_base . '/' . $doc->id . '/download');
			$array['formatted_size'] = $doc->getFormattedSize();
			return $array;
		}, $documents);

		return $this->success([
			'data' => $documents_array,
			'pagination' => [
				'total' => count($documents_array),
				'page' => 1,
				'per_page' => $limit,
				'total_pages' => 1,
			],
		]);
	}

	public function get_item(WP_REST_Request $request) {
		$id = (int) $request->get_param('id');
		$document = $this->repository->find($id);

		if (!$document) {
			return $this->error('Document not found', 404);
		}

		// SECURITY: Verify user has access to this document's account
		if (!$this->verify_account_access($document->account_id)) {
			return $this->error('You do not have permission to access this document', 403);
		}

		$array = $document->toArray();
		$array['download_url'] = rest_url($this->namespace . '/' . $this->rest_base . '/' . $document->id . '/download');
		$array['formatted_size'] = $document->getFormattedSize();

		return $this->success($array);
	}

	public function upload_document(WP_REST_Request $request) {
		// Check if file was uploaded
		$files = $request->get_file_params();
		if (empty($files['file'])) {
			return $this->error('No file uploaded', 400);
		}

		$file = $files['file'];

		// Get form data
		$transaction_id = (int) $request->get_param('transaction_id');
		$document_type = $request->get_param('document_type');
		$title = $request->get_param('title');
		$description = $request->get_param('description');

		// Validate transaction exists
		$transaction = $this->transaction_repository->find($transaction_id);
		if (!$transaction) {
			return $this->error('Transaction not found', 404);
		}

		// SECURITY: Verify user has access to this transaction's account
		if (!$this->verify_account_access($transaction->account_id)) {
			return $this->error('You do not have permission to upload documents to this transaction', 403);
		}

		// Validate file upload errors
		if ($file['error'] !== UPLOAD_ERR_OK) {
			// SECURITY FIX: Map error codes to user-friendly messages
			// Don't expose raw error codes which reveal server configuration
			$upload_errors = [
				UPLOAD_ERR_INI_SIZE => 'File exceeds server upload limit',
				UPLOAD_ERR_FORM_SIZE => 'File exceeds form upload limit',
				UPLOAD_ERR_PARTIAL => 'File was only partially uploaded',
				UPLOAD_ERR_NO_FILE => 'No file was uploaded',
				UPLOAD_ERR_NO_TMP_DIR => 'Upload failed due to server configuration',
				UPLOAD_ERR_CANT_WRITE => 'Upload failed due to server permissions',
				UPLOAD_ERR_EXTENSION => 'Upload blocked by server extension',
			];

			$error_message = $upload_errors[$file['error']] ?? 'File upload failed';

			// Log actual error code server-side
			error_log(sprintf(
				'[Document Upload] File upload error code %d for user %d',
				$file['error'],
				get_current_user_id()
			));

			return $this->error($error_message, 400);
		}

		// SECURITY: Validate file using FileSecurityService
		// Includes: size check, MIME type validation, extension blocking, virus scanning
		$validation_result = $this->file_security_service->validate_file($file);
		if (is_wp_error($validation_result)) {
			error_log(sprintf(
				'[Document Upload] File validation failed for user %d: %s',
				get_current_user_id(),
				$validation_result->get_error_message()
			));
			return $this->error($validation_result->get_error_message(), 400);
		}

		// Get file MIME type (already validated by FileSecurityService)
		$wp_filetype = wp_check_filetype_and_ext($file['tmp_name'], $file['name']);
		$mime_type = $wp_filetype['type'];

		// Calculate checksum before storage
		$checksum = $this->file_security_service->calculate_checksum($file['tmp_name']);

		// Get virus scan metadata
		$scan_metadata = $this->file_security_service->get_scan_metadata($file['tmp_name'], $file['name']);

		// Store file securely using FileStorageService
		// Files are stored outside web root with random filenames and 0640 permissions
		$storage_result = $this->file_storage_service->store_file(
			$file['tmp_name'],
			$transaction_id,
			$file['name']
		);

		if (is_wp_error($storage_result)) {
			error_log(sprintf(
				'[Document Upload] File storage failed for user %d: %s',
				get_current_user_id(),
				$storage_result->get_error_message()
			));
			return $this->error($storage_result->get_error_message(), 500);
		}

		// Verify checksum after storage
		if (!$this->file_storage_service->validate_stored_file_checksum($storage_result['file_path'], $checksum)) {
			// Checksum mismatch - file may have been corrupted during transfer
			// Delete the stored file
			$this->file_storage_service->delete_file($storage_result['file_path']);

			error_log(sprintf(
				'[Document Upload] Checksum mismatch after storage for file: %s',
				$file['name']
			));

			return $this->error('File integrity check failed. Please try uploading again.', 500);
		}

		// Create database record with security metadata
		$document_id = $this->repository->create([
			'transaction_id' => $transaction_id,
			'account_id' => $transaction->account_id,
			'uploaded_by_user_id' => get_current_user_id(),
			'file_name' => $file['name'],
			'file_path' => $storage_result['file_path'],
			'file_size' => $file['size'],
			'mime_type' => $mime_type,
			'checksum' => $checksum,
			'scan_status' => $scan_metadata['scan_status'],
			'scan_date' => $scan_metadata['scan_date'],
			'scanner_used' => $scan_metadata['scanner_used'],
			'document_type' => $document_type,
			'title' => $title,
			'description' => $description,
		]);

		if (!$document_id) {
			// Clean up file if database insert fails
			$this->file_storage_service->delete_file($storage_result['file_path']);
			$db_error = $this->repository->get_last_error();

			// SECURITY FIX: Use secure error handler to prevent database schema disclosure
			// Logs database error server-side but returns generic message to client
			return $this->database_error('create document record', $db_error, [
				'file_name' => $file['name'],
				'transaction_id' => $transaction_id
			]);
		}

		// Log successful upload
		error_log(sprintf(
			'[Document Upload] File uploaded successfully: id=%d, checksum=%s, scan_status=%s',
			$document_id,
			$checksum,
			$scan_metadata['scan_status']
		));

		// Get created document
		$document = $this->repository->find($document_id);
		$array = $document->toArray();
		$array['download_url'] = rest_url($this->namespace . '/' . $this->rest_base . '/' . $document->id . '/download');
		$array['formatted_size'] = $document->getFormattedSize();

		// Log event
		$this->logEvent(
			'document',
			$document_id,
			'uploaded',
			[],
			$document->toArray(),
			$transaction->account_id,
			$transaction_id
		);

		// Send email notifications to all parties on the transaction
		$parties = $this->party_repository->findByTransaction($transaction_id);
		$recipient_emails = array_filter(array_map(function($party) {
			return $party->email;
		}, $parties));

		if (!empty($recipient_emails)) {
			$this->email_service->sendDocumentUploadedNotification(
				$document,
				$transaction,
				$recipient_emails
			);
		}

		return $this->success($array, 'Document uploaded successfully', 201);
	}

	public function update_document(WP_REST_Request $request) {
		$id = (int) $request->get_param('id');

		// Get existing document
		$old_document = $this->repository->find($id);
		if (!$old_document) {
			return $this->error('Document not found', 404);
		}

		// SECURITY: Verify user has access to this document's account
		if (!$this->verify_account_access($old_document->account_id)) {
			return $this->error('You do not have permission to modify this document', 403);
		}

		// Get and validate request data
		$data = $this->sanitize_data($request->get_json_params());

		// Only allow updating metadata fields
		$allowed_fields = ['title', 'description', 'document_type', 'is_public'];
		$update_data = array_intersect_key($data, array_flip($allowed_fields));

		// Handle public token generation
		if (isset($update_data['is_public']) && $update_data['is_public'] && !$old_document->public_token) {
			$update_data['public_token'] = $this->repository->generatePublicToken();
		}

		// Update document
		$success = $this->repository->update($id, $update_data);

		if (!$success) {
			return $this->error('Failed to update document', 500);
		}

		// Get updated document
		$document = $this->repository->find($id);
		$array = $document->toArray();
		$array['download_url'] = rest_url($this->namespace . '/' . $this->rest_base . '/' . $document->id . '/download');
		$array['formatted_size'] = $document->getFormattedSize();

		// Log event
		$this->logEvent(
			'document',
			$id,
			'updated',
			$old_document->toArray(),
			$document->toArray(),
			null,
			$document->transaction_id
		);

		return $this->success($array, 'Document updated successfully');
	}

	public function delete_document(WP_REST_Request $request) {
		$id = (int) $request->get_param('id');

		// Get existing document
		$document = $this->repository->find($id);
		if (!$document) {
			return $this->error('Document not found', 404);
		}

		// SECURITY: Verify user has access to this document's account
		if (!$this->verify_account_access($document->account_id)) {
			return $this->error('You do not have permission to delete this document', 403);
		}

		// Store data for audit before deletion
		$document_data = $document->toArray();

		// Delete file from secure storage first
		$file_delete_result = $this->file_storage_service->delete_file($document->file_path);

		if (is_wp_error($file_delete_result)) {
			error_log(sprintf(
				'[Document Delete] File deletion failed: id=%d, error=%s',
				$document->id,
				$file_delete_result->get_error_message()
			));
			// Continue with database deletion even if file delete fails
			// (file may have been manually deleted or is missing)
		}

		// Delete database record
		$success = $this->repository->delete($id);

		if (!$success) {
			return $this->error('Failed to delete document record', 500);
		}

		// Log event
		$this->logEvent(
			'document',
			$id,
			'deleted',
			$document_data,
			[],
			null,
			$document->transaction_id
		);

		return $this->success(null, 'Document deleted successfully');
	}

	public function download_document(WP_REST_Request $request) {
		$id = (int) $request->get_param('id');
		$document = $this->repository->find($id);

		if (!$document) {
			return $this->error('Document not found', 404);
		}

		// SECURITY: Verify user has access to this document's account
		if (!$this->verify_account_access($document->account_id)) {
			return $this->error('You do not have permission to download this document', 403);
		}

		// SECURITY: Check if file passed virus scan
		if ($document->scan_status === 'infected') {
			error_log(sprintf(
				'[Document Download] Blocked download of infected file: id=%d, user=%d',
				$document->id,
				get_current_user_id()
			));
			return $this->error('This file failed virus scan and cannot be downloaded', 403);
		}

		// Use FileStorageService to serve file securely
		// This handles path validation, security headers, and file streaming
		$serve_result = $this->file_storage_service->serve_file(
			$document->file_path,
			$document->file_name,
			$document->mime_type
		);

		// If serve_file returns (error case), return error response
		if (is_wp_error($serve_result)) {
			error_log(sprintf(
				'[Document Download] File serve failed: id=%d, error=%s',
				$document->id,
				$serve_result->get_error_message()
			));
			return $this->error($serve_result->get_error_message(), 404);
		}

		// Note: serve_file() calls exit, so this line is never reached on success
	}

	public function get_public_document(WP_REST_Request $request) {
		// SECURITY: Apply rate limiting for public document access
		// Prevents token enumeration and abuse
		$rate_limit_check = $this->rate_limiter->check_rate_limit($request);
		if (is_wp_error($rate_limit_check)) {
			return $rate_limit_check;
		}

		$token = $request->get_param('token');
		$document = $this->repository->findByPublicToken($token);

		if (!$document) {
			return $this->error('Document not found or not public', 404);
		}

		$file_path = $document->getFilePath();

		if (!file_exists($file_path)) {
			return $this->error('File not found on disk', 404);
		}

		// Set headers for file download
		header('Content-Type: ' . $document->mime_type);
		header('Content-Disposition: attachment; filename="' . $document->file_name . '"');
		header('Content-Length: ' . filesize($file_path));
		header('Cache-Control: no-cache, must-revalidate');
		header('Expires: 0');

		// Output file
		readfile($file_path);
		exit;
	}
}
