#!/usr/bin/env php
<?php
/**
 * Fix Migration Tracking - Manually mark migrations as applied
 * if their changes already exist in the database
 */

require_once '/var/www/html/wp-load.php';

global $wpdb;

echo "========================================\n";
echo "Fix Migration Tracking\n";
echo "========================================\n\n";

// Define migrations to check and mark as applied
$migrations_to_check = [
    '013' => [
        'name' => 'Add Performance Indexes',
        'check' => function() use ($wpdb) {
            // Check if the first index from this migration exists
            $result = $wpdb->get_var("
                SELECT COUNT(*) FROM INFORMATION_SCHEMA.STATISTICS
                WHERE TABLE_SCHEMA = DATABASE()
                AND TABLE_NAME = '{$wpdb->prefix}ma_deal_transactions'
                AND INDEX_NAME = 'idx_transactions_template_status_closing'
            ");
            return $result > 0;
        }
    ],
    '014' => [
        'name' => 'Create Rate Limits Table',
        'check' => function() use ($wpdb) {
            return $wpdb->get_var("SHOW TABLES LIKE '{$wpdb->prefix}ma_deal_rate_limits'") !== null;
        }
    ],
    '015' => [
        'name' => 'Add File Security Columns',
        'check' => function() use ($wpdb) {
            $columns = $wpdb->get_col("DESCRIBE {$wpdb->prefix}ma_deal_documents");
            return in_array('encrypted', $columns) || in_array('encryption_key', $columns);
        }
    ],
    '016' => [
        'name' => 'Verify Existing Users',
        'check' => function() use ($wpdb) {
            // This migration is data-only, check if custom_users table exists
            return $wpdb->get_var("SHOW TABLES LIKE '{$wpdb->prefix}ma_deal_custom_users'") !== null;
        }
    ],
    '017' => [
        'name' => 'Enhance User Sessions Table',
        'check' => function() use ($wpdb) {
            $columns = $wpdb->get_col("DESCRIBE {$wpdb->prefix}ma_deal_user_sessions");
            return in_array('suspicious_activity_count', $columns);
        }
    ],
    '021' => [
        'name' => 'Create MLS Config Table',
        'check' => function() use ($wpdb) {
            return $wpdb->get_var("SHOW TABLES LIKE '{$wpdb->prefix}ma_deal_mls_config'") !== null;
        }
    ],
    '022' => [
        'name' => 'Add MLS Number to Transactions',
        'check' => function() use ($wpdb) {
            $columns = $wpdb->get_col("DESCRIBE {$wpdb->prefix}ma_deal_transactions");
            return in_array('mls_number', $columns);
        }
    ],
    '023' => [
        'name' => 'Create CRM Config Table',
        'check' => function() use ($wpdb) {
            return $wpdb->get_var("SHOW TABLES LIKE '{$wpdb->prefix}ma_deal_crm_config'") !== null;
        }
    ],
    '024' => [
        'name' => 'Add CRM Sync Fields',
        'check' => function() use ($wpdb) {
            $columns = $wpdb->get_col("DESCRIBE {$wpdb->prefix}ma_deal_transactions");
            return in_array('crm_id', $columns) || in_array('crm_deal_id', $columns);
        }
    ],
    '025' => [
        'name' => 'Add CRM Deal Sync Fields',
        'check' => function() use ($wpdb) {
            $columns = $wpdb->get_col("DESCRIBE {$wpdb->prefix}ma_deal_transactions");
            return in_array('crm_deal_stage', $columns);
        }
    ],
    '026' => [
        'name' => 'Create Contacts Table',
        'check' => function() use ($wpdb) {
            return $wpdb->get_var("SHOW TABLES LIKE '{$wpdb->prefix}ma_deal_contacts'") !== null;
        }
    ],
    '027' => [
        'name' => 'Create DocuSign Config Table',
        'check' => function() use ($wpdb) {
            return $wpdb->get_var("SHOW TABLES LIKE '{$wpdb->prefix}ma_deal_docusign_config'") !== null;
        }
    ],
    '028' => [
        'name' => 'Create DocuSign Envelopes Table',
        'check' => function() use ($wpdb) {
            return $wpdb->get_var("SHOW TABLES LIKE '{$wpdb->prefix}ma_deal_docusign_envelopes'") !== null;
        }
    ],
    '029' => [
        'name' => 'Create DocuSign Webhook Log Table',
        'check' => function() use ($wpdb) {
            return $wpdb->get_var("SHOW TABLES LIKE '{$wpdb->prefix}ma_deal_docusign_webhook_log'") !== null;
        }
    ],
];

$migrations_table = $wpdb->prefix . 'ma_deal_migrations';
$marked = 0;
$skipped = 0;

foreach ($migrations_to_check as $number => $migration) {
    echo "Checking migration {$number}: {$migration['name']}...\n";

    // Check if already recorded
    $exists = $wpdb->get_var($wpdb->prepare(
        "SELECT COUNT(*) FROM {$migrations_table} WHERE migration_number = %s",
        $number
    ));

    if ($exists > 0) {
        echo "  Already recorded - skipping\n";
        $skipped++;
        continue;
    }

    // Check if changes exist
    if ($migration['check']()) {
        echo "  Changes exist in database - marking as applied\n";

        // Record migration
        $wpdb->insert(
            $migrations_table,
            [
                'migration_number' => $number,
                'migration_name' => $migration['name'],
                'applied_at' => current_time('mysql'),
                'rollback_available' => file_exists(
                    WP_PLUGIN_DIR . '/ma-deal-room/database/migrations/rollback_' . $number . '.sql'
                ),
            ],
            ['%s', '%s', '%s', '%d']
        );

        if ($wpdb->last_error) {
            echo "  ERROR: " . $wpdb->last_error . "\n";
        } else {
            echo "  Successfully marked as applied\n";
            $marked++;
        }
    } else {
        echo "  Changes NOT found - will need to run migration\n";
    }
}

echo "\n";
echo "Summary:\n";
echo "  Marked as applied: {$marked}\n";
echo "  Already recorded:  {$skipped}\n";
echo "  Total checked:     " . count($migrations_to_check) . "\n";

echo "\nDone!\n";
