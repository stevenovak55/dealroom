<?php
/**
 * DocuSign Template Service
 *
 * @package    MADealRoom
 * @subpackage Services\Integration\DocuSign
 * @since      1.0.0
 */

namespace MADealRoom\Services\Integration\DocuSign;

use MADealRoom\Core\Logger;

/**
 * Class DocuSignTemplateService
 *
 * Manages DocuSign templates and field mapping
 */
class DocuSignTemplateService {
    /**
     * DocuSign client
     *
     * @var DocuSignClient
     */
    private DocuSignClient $client;

    /**
     * Logger instance
     *
     * @var Logger
     */
    private Logger $logger;

    /**
     * Cache key prefix
     */
    private const CACHE_PREFIX = 'ma_deal_docusign_templates_';

    /**
     * Cache TTL (1 hour)
     */
    private const CACHE_TTL = 3600;

    /**
     * Constructor
     *
     * @param DocuSignClient $client DocuSign client
     * @param Logger|null    $logger Logger instance
     */
    public function __construct(DocuSignClient $client, ?Logger $logger = null) {
        $this->client = $client;
        $this->logger = $logger ?? new Logger();
    }

    /**
     * Get all templates
     *
     * @param array $options Query options
     * @return array List of templates
     */
    public function getTemplates(array $options = []): array {
        $cache_key = $this->getCacheKey($options);

        // Check cache first
        $cached = get_transient($cache_key);
        if ($cached !== false) {
            return $cached;
        }

        try {
            $result = $this->client->listTemplates($options);
            $templates = $result['envelopeTemplates'] ?? [];

            // Cache the result
            set_transient($cache_key, $templates, self::CACHE_TTL);

            return $templates;
        } catch (\Exception $e) {
            $this->logger->error('Failed to fetch DocuSign templates', [
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Get template details
     *
     * @param string $template_id Template ID
     * @return array Template details
     */
    public function getTemplate(string $template_id): array {
        $cache_key = $this->getCacheKey(['template_id' => $template_id]);

        // Check cache first
        $cached = get_transient($cache_key);
        if ($cached !== false) {
            return $cached;
        }

        try {
            $template = $this->client->getTemplate($template_id);

            // Cache the result
            set_transient($cache_key, $template, self::CACHE_TTL);

            return $template;
        } catch (\Exception $e) {
            $this->logger->error('Failed to fetch DocuSign template', [
                'template_id' => $template_id,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Map transaction data to template fields
     *
     * @param array $template      Template data
     * @param array $transaction   Transaction data
     * @return array Mapped tabs/fields
     */
    public function mapTransactionDataToTemplate(array $template, array $transaction): array {
        $tabs = [];

        // Get template recipients and their tabs
        $recipients = $template['recipients'] ?? [];

        // Map common transaction fields to DocuSign tabs
        $field_mapping = $this->getFieldMapping();

        foreach ($recipients as $recipient_type => $recipient_list) {
            if (!is_array($recipient_list)) {
                continue;
            }

            foreach ($recipient_list as $recipient) {
                if (!isset($recipient['tabs'])) {
                    continue;
                }

                // Process each tab type (textTabs, dateTabs, etc.)
                foreach ($recipient['tabs'] as $tab_type => $tab_list) {
                    foreach ($tab_list as $tab) {
                        $tab_label = $tab['tabLabel'] ?? '';

                        // Map transaction field to tab if mapping exists
                        if (isset($field_mapping[$tab_label])) {
                            $field_name = $field_mapping[$tab_label];
                            $value = $this->getTransactionFieldValue($transaction, $field_name);

                            if ($value !== null) {
                                $tabs[$tab_type][] = [
                                    'tabLabel' => $tab_label,
                                    'value' => $value,
                                ];
                            }
                        }
                    }
                }
            }
        }

        return $tabs;
    }

    /**
     * Preview template with transaction data
     *
     * @param string $template_id Template ID
     * @param array  $transaction Transaction data
     * @return array Preview data with mapped fields
     */
    public function previewTemplate(string $template_id, array $transaction): array {
        try {
            $template = $this->getTemplate($template_id);
            $mapped_tabs = $this->mapTransactionDataToTemplate($template, $transaction);

            return [
                'template' => $template,
                'mapped_fields' => $mapped_tabs,
                'transaction' => $transaction,
            ];
        } catch (\Exception $e) {
            $this->logger->error('Failed to preview template', [
                'template_id' => $template_id,
                'transaction_id' => $transaction['id'] ?? null,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Clear template cache
     *
     * @param string|null $template_id Optional template ID to clear specific cache
     * @return void
     */
    public function clearCache(?string $template_id = null): void {
        global $wpdb;

        if ($template_id) {
            $cache_key = $this->getCacheKey(['template_id' => $template_id]);
            delete_transient($cache_key);
        } else {
            // Clear all template caches
            $wpdb->query(
                $wpdb->prepare(
                    "DELETE FROM {$wpdb->options} WHERE option_name LIKE %s",
                    '_transient_' . self::CACHE_PREFIX . '%'
                )
            );
        }
    }

    /**
     * Get field mapping configuration
     *
     * Maps transaction fields to DocuSign tab labels
     *
     * @return array Field mapping
     */
    private function getFieldMapping(): array {
        // Allow customization via filter
        return apply_filters('ma_deal_docusign_field_mapping', [
            // Property fields
            'Property Address' => 'property_address',
            'Property City' => 'property_city',
            'Property State' => 'property_state',
            'Property ZIP' => 'property_zip',
            'Purchase Price' => 'purchase_price',
            'Sale Price' => 'sale_price',
            'Closing Date' => 'closing_date',
            'Property Type' => 'property_type',

            // Buyer/Seller fields
            'Buyer Name' => 'buyer_name',
            'Buyer Email' => 'buyer_email',
            'Buyer Phone' => 'buyer_phone',
            'Seller Name' => 'seller_name',
            'Seller Email' => 'seller_email',
            'Seller Phone' => 'seller_phone',

            // Agent fields
            'Agent Name' => 'agent_name',
            'Agent Email' => 'agent_email',
            'Agent Phone' => 'agent_phone',
            'Agent License' => 'agent_license',
            'Brokerage Name' => 'brokerage_name',

            // Transaction fields
            'Transaction ID' => 'transaction_id',
            'Transaction Type' => 'transaction_type',
            'Transaction Status' => 'status',
            'Created Date' => 'created_at',

            // Financial fields
            'Earnest Money' => 'earnest_money',
            'Down Payment' => 'down_payment',
            'Loan Amount' => 'loan_amount',
            'Commission' => 'commission_amount',
        ]);
    }

    /**
     * Get transaction field value by field name
     *
     * @param array  $transaction Transaction data
     * @param string $field_name  Field name
     * @return string|null Field value
     */
    private function getTransactionFieldValue(array $transaction, string $field_name): ?string {
        // Handle nested fields (e.g., 'property.address')
        if (strpos($field_name, '.') !== false) {
            $parts = explode('.', $field_name);
            $value = $transaction;

            foreach ($parts as $part) {
                if (!isset($value[$part])) {
                    return null;
                }
                $value = $value[$part];
            }

            return (string) $value;
        }

        // Direct field access
        if (!isset($transaction[$field_name])) {
            return null;
        }

        $value = $transaction[$field_name];

        // Format dates
        if (in_array($field_name, ['closing_date', 'created_at', 'updated_at'])) {
            return date('m/d/Y', strtotime($value));
        }

        // Format currency
        if (in_array($field_name, ['purchase_price', 'sale_price', 'earnest_money', 'down_payment', 'loan_amount', 'commission_amount'])) {
            return '$' . number_format((float) $value, 2);
        }

        return (string) $value;
    }

    /**
     * Get cache key
     *
     * @param array $params Parameters
     * @return string Cache key
     */
    private function getCacheKey(array $params): string {
        return self::CACHE_PREFIX . md5(serialize($params));
    }
}
