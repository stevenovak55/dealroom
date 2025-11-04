<?php
/**
 * DocuSign API Client Interface
 *
 * @package    MADealRoom
 * @subpackage Services\Integration\DocuSign
 * @since      1.0.0
 */

namespace MADealRoom\Services\Integration\DocuSign;

/**
 * Interface DocuSignClientInterface
 *
 * Standard interface for DocuSign API operations
 */
interface DocuSignClientInterface {
    /**
     * Authenticate with DocuSign API using OAuth 2.0 JWT Grant
     *
     * @return array{access_token: string, expires_in: int, token_type: string}
     * @throws \Exception If authentication fails
     */
    public function authenticate(): array;

    /**
     * Refresh the access token
     *
     * @return array{access_token: string, expires_in: int, token_type: string}
     * @throws \Exception If token refresh fails
     */
    public function refreshToken(): array;

    /**
     * Get current access token
     *
     * @return string|null
     */
    public function getAccessToken(): ?string;

    /**
     * Check if access token is valid and not expired
     *
     * @return bool
     */
    public function isAuthenticated(): bool;

    /**
     * List DocuSign templates
     *
     * @param array $options Query options (folder_id, search_text, etc.)
     * @return array List of templates
     * @throws \Exception If API call fails
     */
    public function listTemplates(array $options = []): array;

    /**
     * Get template details
     *
     * @param string $template_id Template ID
     * @return array Template details
     * @throws \Exception If API call fails
     */
    public function getTemplate(string $template_id): array;

    /**
     * Create an envelope from documents
     *
     * @param array $envelope_definition Envelope definition
     * @return array{envelope_id: string, status: string, uri: string}
     * @throws \Exception If API call fails
     */
    public function createEnvelope(array $envelope_definition): array;

    /**
     * Create an envelope from a template
     *
     * @param string $template_id Template ID
     * @param array  $envelope_data Envelope data (recipients, tabs, etc.)
     * @return array{envelope_id: string, status: string, uri: string}
     * @throws \Exception If API call fails
     */
    public function createEnvelopeFromTemplate(string $template_id, array $envelope_data): array;

    /**
     * Send an envelope
     *
     * @param string $envelope_id Envelope ID
     * @return array Updated envelope status
     * @throws \Exception If API call fails
     */
    public function sendEnvelope(string $envelope_id): array;

    /**
     * Get envelope status
     *
     * @param string $envelope_id Envelope ID
     * @return array Envelope status details
     * @throws \Exception If API call fails
     */
    public function getEnvelopeStatus(string $envelope_id): array;

    /**
     * Get envelope recipients
     *
     * @param string $envelope_id Envelope ID
     * @return array Recipients status
     * @throws \Exception If API call fails
     */
    public function getEnvelopeRecipients(string $envelope_id): array;

    /**
     * Download envelope documents
     *
     * @param string $envelope_id Envelope ID
     * @param string $document_id Document ID (or 'combined' for all)
     * @return string Binary document content
     * @throws \Exception If API call fails
     */
    public function downloadDocument(string $envelope_id, string $document_id = 'combined'): string;

    /**
     * Download certificate of completion
     *
     * @param string $envelope_id Envelope ID
     * @return string Binary PDF content
     * @throws \Exception If API call fails
     */
    public function downloadCertificate(string $envelope_id): string;

    /**
     * Void an envelope
     *
     * @param string $envelope_id Envelope ID
     * @param string $void_reason Reason for voiding
     * @return array Updated envelope status
     * @throws \Exception If API call fails
     */
    public function voidEnvelope(string $envelope_id, string $void_reason): array;

    /**
     * Resend envelope notification
     *
     * @param string $envelope_id Envelope ID
     * @return array Success status
     * @throws \Exception If API call fails
     */
    public function resendEnvelope(string $envelope_id): array;

    /**
     * Test connection to DocuSign
     *
     * @return array{success: bool, message: string, account_info?: array}
     */
    public function testConnection(): array;
}
