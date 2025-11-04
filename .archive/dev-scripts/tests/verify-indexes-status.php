<?php
/**
 * Verify all T1.5 performance indexes status
 */

// Load WordPress
require_once('/var/www/html/wp-load.php');

echo "=================================================================\n";
echo "VERIFYING T1.5 PERFORMANCE INDEXES STATUS\n";
echo "=================================================================\n\n";

global $wpdb;

// Expected indexes from migration 013
$expected_indexes = [
    'wp_ma_deal_transactions' => [
        'idx_transactions_template_status_closing',
        'idx_transactions_agent_status_closing',
        'idx_transactions_location_status',
        'idx_transactions_closing_status',
    ],
    'wp_ma_deal_tasks' => [
        'idx_tasks_status_overdue',
        'idx_tasks_assigned_status_due',
        'idx_tasks_transaction_status_completed',
    ],
    'wp_ma_deal_templates' => [
        'idx_templates_active_system_created',
        'idx_templates_account_active_created',
        'idx_templates_property_active',
    ],
    'wp_ma_deal_notifications' => [
        'idx_notifications_read_cleanup',
        'idx_notifications_recipient_read_created',
        'idx_notifications_transaction_type',
    ],
    'wp_ma_deal_parties' => [
        'idx_parties_transaction_role_email',
        'idx_parties_users',
    ],
    'wp_ma_deal_documents' => [
        'idx_documents_transaction_status_uploaded',
        'idx_documents_transaction_type',
    ],
    'wp_ma_deal_reminders' => [
        'idx_reminders_status_scheduled',
        'idx_reminders_task_status',
    ],
    'wp_ma_deal_custom_users' => [
        'idx_custom_users_status_deleted_created',
        'idx_custom_users_verified_deleted',
    ],
    'wp_ma_deal_user_sessions' => [
        'idx_sessions_expires_revoked',
    ],
    'wp_ma_deal_accounts' => [
        'idx_accounts_subscription_status_expires',
        'idx_accounts_owner_status',
    ],
    'wp_ma_deal_user_invitations' => [
        'idx_invitations_email_expires_accepted',
    ],
    'wp_ma_deal_task_definitions' => [
        'idx_task_definitions_category_account_title',
        'idx_task_definitions_system_account',
    ],
    'wp_ma_deal_vendor_requests' => [
        'idx_vendor_requests_transaction_status_created',
    ],
    'wp_ma_deal_events' => [
        'idx_events_entity_created',
    ],
];

$total_expected = 0;
$total_found = 0;
$tables_complete = 0;
$tables_partial = 0;
$tables_missing = 0;

foreach ($expected_indexes as $table => $indexes) {
    $total_expected += count($indexes);

    // Get existing indexes
    $result = $wpdb->get_results("SHOW INDEX FROM {$table}", ARRAY_A);
    if (!$result) {
        echo "✗ Table {$table} not found\n";
        $tables_missing++;
        continue;
    }

    $existing_indexes = array_unique(array_column($result, 'Key_name'));

    $found_count = 0;
    $missing = [];

    foreach ($indexes as $expected_index) {
        if (in_array($expected_index, $existing_indexes)) {
            $found_count++;
            $total_found++;
        } else {
            $missing[] = $expected_index;
        }
    }

    if ($found_count === count($indexes)) {
        echo "✓ {$table}: All " . count($indexes) . " indexes present\n";
        $tables_complete++;
    } elseif ($found_count > 0) {
        echo "⚠ {$table}: {$found_count}/" . count($indexes) . " indexes present\n";
        echo "  Missing: " . implode(', ', $missing) . "\n";
        $tables_partial++;
    } else {
        echo "✗ {$table}: No indexes present\n";
        $tables_missing++;
    }
}

echo "\n=================================================================\n";
echo "SUMMARY\n";
echo "=================================================================\n";
echo "Total expected indexes: {$total_expected}\n";
echo "Total found indexes: {$total_found}\n";
echo "Completion rate: " . round(($total_found / $total_expected) * 100, 1) . "%\n\n";
echo "Tables with all indexes: {$tables_complete}\n";
echo "Tables with partial indexes: {$tables_partial}\n";
echo "Tables with no indexes: {$tables_missing}\n";

// Check migration record
$migration_recorded = $wpdb->get_var(
    "SELECT migration_number FROM {$wpdb->prefix}ma_deal_migrations WHERE migration_number = '013'"
);

echo "\nMigration 013 recorded: " . ($migration_recorded ? "YES" : "NO") . "\n";

if ($total_found === $total_expected && !$migration_recorded) {
    echo "\n⚠ All indexes are present but migration 013 is not recorded in migrations table.\n";
    echo "   This suggests the indexes were created manually or by a previous run.\n";
    echo "   Recording migration 013 now...\n";

    $wpdb->insert(
        $wpdb->prefix . 'ma_deal_migrations',
        [
            'migration_number' => '013',
            'migration_name' => 'Add Performance Indexes',
            'applied_at' => current_time('mysql'),
            'rollback_available' => 1,
        ]
    );

    if ($wpdb->insert_id) {
        echo "   ✓ Migration 013 recorded successfully!\n";
    } else {
        echo "   ✗ Failed to record migration 013: " . $wpdb->last_error . "\n";
    }
}

echo "\n=================================================================\n";
