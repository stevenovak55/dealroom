<?php
/**
 * CRM Client Interface
 *
 * Standard interface for all CRM provider integrations (Salesforce, HubSpot, etc.)
 * Defines common methods that all CRM clients must implement for contacts, deals, and activities.
 *
 * @package MA_Deal_Room\Services\Integration\CRM
 * @since 1.0.0
 */

namespace MADealRoom\Services\Integration\CRM;

interface CRMClientInterface {
    /**
     * Authenticate with the CRM provider
     *
     * @param array $credentials Authentication credentials (varies by provider)
     * @return bool True if authentication successful
     * @throws \Exception If authentication fails
     */
    public function authenticate(array $credentials): bool;

    /**
     * Refresh the authentication token
     *
     * @return bool True if token refresh successful
     * @throws \Exception If token refresh fails
     */
    public function refreshToken(): bool;

    /**
     * Test the connection to CRM provider
     *
     * @return array ['success' => bool, 'message' => string, 'details' => array]
     */
    public function testConnection(): array;

    /**
     * Get contacts from CRM
     *
     * @param array $filters Optional filters (e.g., ['email' => 'test@example.com'])
     * @param int $limit Maximum number of contacts to retrieve
     * @param int $offset Offset for pagination
     * @return array Array of contacts
     */
    public function getContacts(array $filters = [], int $limit = 100, int $offset = 0): array;

    /**
     * Get a single contact by ID
     *
     * @param string $contactId CRM contact ID
     * @return array|null Contact data or null if not found
     */
    public function getContact(string $contactId): ?array;

    /**
     * Create a new contact in CRM
     *
     * @param array $contactData Contact data to create
     * @return array Created contact with CRM ID
     * @throws \Exception If creation fails
     */
    public function createContact(array $contactData): array;

    /**
     * Update an existing contact in CRM
     *
     * @param string $contactId CRM contact ID
     * @param array $contactData Contact data to update
     * @return array Updated contact data
     * @throws \Exception If update fails
     */
    public function updateContact(string $contactId, array $contactData): array;

    /**
     * Delete a contact from CRM
     *
     * @param string $contactId CRM contact ID
     * @return bool True if deletion successful
     * @throws \Exception If deletion fails
     */
    public function deleteContact(string $contactId): bool;

    /**
     * Get deals/opportunities from CRM
     *
     * @param array $filters Optional filters
     * @param int $limit Maximum number of deals to retrieve
     * @param int $offset Offset for pagination
     * @return array Array of deals
     */
    public function getDeals(array $filters = [], int $limit = 100, int $offset = 0): array;

    /**
     * Get a single deal by ID
     *
     * @param string $dealId CRM deal/opportunity ID
     * @return array|null Deal data or null if not found
     */
    public function getDeal(string $dealId): ?array;

    /**
     * Create a new deal/opportunity in CRM
     *
     * @param array $dealData Deal data to create
     * @return array Created deal with CRM ID
     * @throws \Exception If creation fails
     */
    public function createDeal(array $dealData): array;

    /**
     * Update an existing deal/opportunity in CRM
     *
     * @param string $dealId CRM deal/opportunity ID
     * @param array $dealData Deal data to update
     * @return array Updated deal data
     * @throws \Exception If update fails
     */
    public function updateDeal(string $dealId, array $dealData): array;

    /**
     * Delete a deal from CRM
     *
     * @param string $dealId CRM deal/opportunity ID
     * @return bool True if deletion successful
     * @throws \Exception If deletion fails
     */
    public function deleteDeal(string $dealId): bool;

    /**
     * Log an activity/event in CRM timeline
     *
     * @param string $objectType Type of object (contact, deal, etc.)
     * @param string $objectId CRM object ID
     * @param array $activityData Activity data (type, subject, description, date, etc.)
     * @return array Created activity with CRM ID
     * @throws \Exception If logging fails
     */
    public function logActivity(string $objectType, string $objectId, array $activityData): array;

    /**
     * Search CRM records
     *
     * @param string $objectType Type of object to search (contact, deal, etc.)
     * @param string $query Search query
     * @param int $limit Maximum results
     * @return array Search results
     */
    public function searchRecords(string $objectType, string $query, int $limit = 20): array;

    /**
     * Get the provider type
     *
     * @return string Provider type (salesforce, hubspot, etc.)
     */
    public function getProviderType(): string;

    /**
     * Check if client is authenticated
     *
     * @return bool True if authenticated
     */
    public function isAuthenticated(): bool;

    /**
     * Get current access token
     *
     * @return string|null Access token or null if not authenticated
     */
    public function getAccessToken(): ?string;

    /**
     * Get current refresh token
     *
     * @return string|null Refresh token or null if not available
     */
    public function getRefreshToken(): ?string;

    /**
     * Get instance URL (for Salesforce) or API base URL
     *
     * @return string|null Instance/base URL
     */
    public function getInstanceUrl(): ?string;
}
