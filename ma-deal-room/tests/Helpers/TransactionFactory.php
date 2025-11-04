<?php
/**
 * Transaction Factory
 *
 * Helper class for creating test transactions.
 *
 * @package MADealRoom\Tests\Helpers
 */

namespace MADealRoom\Tests\Helpers;

/**
 * Transaction Factory Class
 */
class TransactionFactory
{
    /**
     * Create a test transaction
     *
     * @param array $attributes Transaction attributes
     * @return array Transaction data
     */
    public static function create(array $attributes = []): array
    {
        $defaults = [
            'transaction_id' => self::randomUuid(),
            'account_id' => self::randomUuid(),
            'property_address' => '123 Main St',
            'property_city' => 'Boston',
            'property_state' => 'MA',
            'property_zip' => '02101',
            'property_type' => 'sfh_city_water',
            'transaction_type' => 'purchase',
            'transaction_side' => 'buyer',
            'has_financing' => 1,
            'purchase_price' => 500000.00,
            'status' => 'active',
            'created_by' => self::randomUuid(),
            'created_at' => self::now(),
            'updated_at' => self::now(),
        ];

        return array_merge($defaults, $attributes);
    }

    /**
     * Create multiple test transactions
     *
     * @param int $count Number of transactions to create
     * @param array $attributes Common attributes for all transactions
     * @return array<array> Array of transaction data
     */
    public static function createMany(int $count, array $attributes = []): array
    {
        $transactions = [];
        for ($i = 0; $i < $count; $i++) {
            $transactions[] = self::create($attributes);
        }
        return $transactions;
    }

    /**
     * Create a buyer transaction
     *
     * @param array $attributes Transaction attributes
     * @return array Transaction data
     */
    public static function createBuyer(array $attributes = []): array
    {
        return self::create(array_merge([
            'transaction_side' => 'buyer',
            'transaction_type' => 'purchase',
            'has_financing' => 1,
        ], $attributes));
    }

    /**
     * Create a seller transaction
     *
     * @param array $attributes Transaction attributes
     * @return array Transaction data
     */
    public static function createSeller(array $attributes = []): array
    {
        return self::create(array_merge([
            'transaction_side' => 'seller',
            'transaction_type' => 'sale',
            'has_financing' => 0,
        ], $attributes));
    }

    /**
     * Create a condo transaction
     *
     * @param array $attributes Transaction attributes
     * @return array Transaction data
     */
    public static function createCondo(array $attributes = []): array
    {
        return self::create(array_merge([
            'property_type' => 'condo',
            'property_address' => '456 Condo Way, Unit 301',
        ], $attributes));
    }

    /**
     * Create a multifamily transaction
     *
     * @param array $attributes Transaction attributes
     * @return array Transaction data
     */
    public static function createMultifamily(array $attributes = []): array
    {
        return self::create(array_merge([
            'property_type' => 'multifamily',
            'property_address' => '789 Multi Family Dr',
            'purchase_price' => 1200000.00,
        ], $attributes));
    }

    /**
     * Create a transaction with dates set
     *
     * @param array $dates Date fields to set
     * @param array $attributes Additional attributes
     * @return array Transaction data
     */
    public static function createWithDates(array $dates = [], array $attributes = []): array
    {
        $dateDefaults = [
            'offer_date' => date('Y-m-d'),
            'offer_accepted_date' => date('Y-m-d', strtotime('+1 day')),
            'p_and_s_date' => date('Y-m-d', strtotime('+14 days')),
            'closing_date' => date('Y-m-d', strtotime('+45 days')),
        ];

        return self::create(array_merge($dateDefaults, $dates, $attributes));
    }

    /**
     * Create a closed transaction
     *
     * @param array $attributes Transaction attributes
     * @return array Transaction data
     */
    public static function createClosed(array $attributes = []): array
    {
        return self::createWithDates([
            'offer_date' => date('Y-m-d', strtotime('-60 days')),
            'offer_accepted_date' => date('Y-m-d', strtotime('-59 days')),
            'p_and_s_date' => date('Y-m-d', strtotime('-45 days')),
            'closing_date' => date('Y-m-d', strtotime('-10 days')),
        ], array_merge(['status' => 'closed'], $attributes));
    }

    /**
     * Create a cancelled transaction
     *
     * @param array $attributes Transaction attributes
     * @return array Transaction data
     */
    public static function createCancelled(array $attributes = []): array
    {
        return self::create(array_merge(['status' => 'cancelled'], $attributes));
    }

    /**
     * Generate a random UUID
     *
     * @return string UUID
     */
    private static function randomUuid(): string
    {
        return sprintf(
            '%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
            mt_rand(0, 0xffff),
            mt_rand(0, 0xffff),
            mt_rand(0, 0xffff),
            mt_rand(0, 0x0fff) | 0x4000,
            mt_rand(0, 0x3fff) | 0x8000,
            mt_rand(0, 0xffff),
            mt_rand(0, 0xffff),
            mt_rand(0, 0xffff)
        );
    }

    /**
     * Get current timestamp
     *
     * @return string Timestamp
     */
    private static function now(): string
    {
        return date('Y-m-d H:i:s');
    }
}
