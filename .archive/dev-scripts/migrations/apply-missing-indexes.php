<?php
/**
 * Apply missing performance indexes from migration 013
 * This script checks for existing indexes before creating them (idempotent)
 */

// Load WordPress
require_once('/var/www/html/wp-load.php');

echo "=================================================================\n";
echo "APPLYING MISSING PERFORMANCE INDEXES\n";
echo "=================================================================\n\n";

global $wpdb;

// Helper function to check if index exists
function index_exists($table, $index_name) {
    global $wpdb;
    $result = $wpdb->get_results("SHOW INDEX FROM {$table} WHERE Key_name = '{$index_name}'", ARRAY_A);
    return !empty($result);
}

// Helper function to create index
function create_index_safe($table, $index_name, $columns, $comment = '') {
    global $wpdb;

    if (index_exists($table, $index_name)) {
        echo "  - {$index_name}: Already exists (skipped)\n";
        return true;
    }

    $columns_str = '`' . implode('`, `', $columns) . '`';
    $comment_str = $comment ? " COMMENT '{$comment}'" : '';
    $sql = "CREATE INDEX `{$index_name}` ON `{$table}` ({$columns_str}){$comment_str}";

    $result = $wpdb->query($sql);

    if ($result === false) {
        echo "  ✗ {$index_name}: FAILED - " . $wpdb->last_error . "\n";
        return false;
    } else {
        echo "  ✓ {$index_name}: Created successfully\n";
        return true;
    }
}

$total_created = 0;
$total_skipped = 0;
$total_failed = 0;

// TRANSACTION INDEXES
echo "\n[1/14] wp_ma_deal_transactions\n";
create_index_safe('wp_ma_deal_transactions', 'idx_transactions_template_status_closing', ['template_id', 'status', 'closing_date'], 'Optimizes template-based transaction queries with status filter') ? $total_created++ : $total_failed++;
create_index_safe('wp_ma_deal_transactions', 'idx_transactions_agent_status_closing', ['assigned_agent_id', 'status', 'closing_date'], 'Optimizes agent dashboard queries') ? $total_created++ : $total_failed++;
create_index_safe('wp_ma_deal_transactions', 'idx_transactions_location_status', ['property_city', 'property_state', 'status'], 'Optimizes property location searches') ? $total_created++ : $total_failed++;
create_index_safe('wp_ma_deal_transactions', 'idx_transactions_closing_status', ['closing_date', 'status'], 'Optimizes date range queries for reports') ? $total_created++ : $total_failed++;

// TASK INDEXES
echo "\n[2/14] wp_ma_deal_tasks\n";
create_index_safe('wp_ma_deal_tasks', 'idx_tasks_status_overdue', ['status', 'due_at'], 'Optimizes overdue task queries') ? $total_created++ : $total_failed++;
create_index_safe('wp_ma_deal_tasks', 'idx_tasks_assigned_status_due', ['assigned_to_party_id', 'status', 'due_at'], 'Optimizes assigned task queries') ? $total_created++ : $total_failed++;
create_index_safe('wp_ma_deal_tasks', 'idx_tasks_transaction_status_completed', ['transaction_id', 'status', 'completed_at'], 'Optimizes completed task queries') ? $total_created++ : $total_failed++;

// TEMPLATE INDEXES
echo "\n[3/14] wp_ma_deal_templates\n";
create_index_safe('wp_ma_deal_templates', 'idx_templates_active_system_created', ['is_active', 'is_system', 'created_at'], 'Optimizes template listing queries') ? $total_created++ : $total_failed++;
create_index_safe('wp_ma_deal_templates', 'idx_templates_account_active_created', ['account_id', 'is_active', 'created_at'], 'Optimizes account-specific template queries') ? $total_created++ : $total_failed++;
create_index_safe('wp_ma_deal_templates', 'idx_templates_property_active', ['property_type', 'is_active'], 'Optimizes property type template queries') ? $total_created++ : $total_failed++;

// NOTIFICATION INDEXES
echo "\n[4/14] wp_ma_deal_notifications\n";
create_index_safe('wp_ma_deal_notifications', 'idx_notifications_read_cleanup', ['is_read', 'read_at'], 'Optimizes notification cleanup queries') ? $total_created++ : $total_failed++;
create_index_safe('wp_ma_deal_notifications', 'idx_notifications_recipient_read_created', ['recipient_id', 'recipient_type', 'is_read', 'created_at'], 'Optimizes user notification queries') ? $total_created++ : $total_failed++;
create_index_safe('wp_ma_deal_notifications', 'idx_notifications_transaction_type', ['transaction_id', 'type'], 'Optimizes transaction-specific notification queries') ? $total_created++ : $total_failed++;

// PARTY INDEXES
echo "\n[5/14] wp_ma_deal_parties\n";
create_index_safe('wp_ma_deal_parties', 'idx_parties_transaction_role_email', ['transaction_id', 'role', 'email'], 'Optimizes party lookup by role (covering index includes email)') ? $total_created++ : $total_failed++;
create_index_safe('wp_ma_deal_parties', 'idx_parties_users', ['custom_user_id', 'wp_user_id'], 'Optimizes user-based party lookups') ? $total_created++ : $total_failed++;

// DOCUMENT INDEXES
echo "\n[6/14] wp_ma_deal_documents\n";
create_index_safe('wp_ma_deal_documents', 'idx_documents_transaction_status_uploaded', ['transaction_id', 'status', 'uploaded_at'], 'Optimizes document listing queries') ? $total_created++ : $total_failed++;
create_index_safe('wp_ma_deal_documents', 'idx_documents_transaction_type', ['transaction_id', 'document_type'], 'Optimizes document type filtering') ? $total_created++ : $total_failed++;

// REMINDER INDEXES
echo "\n[7/14] wp_ma_deal_reminders\n";
create_index_safe('wp_ma_deal_reminders', 'idx_reminders_status_scheduled', ['status', 'scheduled_at'], 'Optimizes pending reminder queries for cron jobs') ? $total_created++ : $total_failed++;
create_index_safe('wp_ma_deal_reminders', 'idx_reminders_task_status', ['task_id', 'status'], 'Optimizes task-specific reminder queries') ? $total_created++ : $total_failed++;

// CUSTOM USER INDEXES
echo "\n[8/14] wp_ma_deal_custom_users\n";
create_index_safe('wp_ma_deal_custom_users', 'idx_custom_users_status_deleted_created', ['status', 'deleted_at', 'created_at'], 'Optimizes active user listing queries') ? $total_created++ : $total_failed++;
create_index_safe('wp_ma_deal_custom_users', 'idx_custom_users_verified_deleted', ['email_verified', 'deleted_at'], 'Optimizes email verification status queries') ? $total_created++ : $total_failed++;

// USER SESSION INDEXES
echo "\n[9/14] wp_ma_deal_user_sessions\n";
create_index_safe('wp_ma_deal_user_sessions', 'idx_sessions_expires_revoked', ['expires_at', 'revoked_at'], 'Optimizes session cleanup queries') ? $total_created++ : $total_failed++;

// ACCOUNT INDEXES
echo "\n[10/14] wp_ma_deal_accounts\n";
create_index_safe('wp_ma_deal_accounts', 'idx_accounts_subscription_status_expires', ['subscription_status', 'subscription_expires_at'], 'Optimizes subscription expiration queries') ? $total_created++ : $total_failed++;
create_index_safe('wp_ma_deal_accounts', 'idx_accounts_owner_status', ['owner_user_id', 'status'], 'Optimizes owner account lookups') ? $total_created++ : $total_failed++;

// USER INVITATION INDEXES
echo "\n[11/14] wp_ma_deal_user_invitations\n";
create_index_safe('wp_ma_deal_user_invitations', 'idx_invitations_email_expires_accepted', ['email', 'expires_at', 'accepted_at'], 'Optimizes pending invitation queries') ? $total_created++ : $total_failed++;

// TASK DEFINITION INDEXES
echo "\n[12/14] wp_ma_deal_task_definitions\n";
create_index_safe('wp_ma_deal_task_definitions', 'idx_task_definitions_category_account_title', ['category', 'account_id', 'title'], 'Optimizes category-based task definition queries') ? $total_created++ : $total_failed++;
create_index_safe('wp_ma_deal_task_definitions', 'idx_task_definitions_system_account', ['is_system', 'account_id'], 'Optimizes system task definition queries') ? $total_created++ : $total_failed++;

// VENDOR REQUEST INDEXES
echo "\n[13/14] wp_ma_deal_vendor_requests\n";
create_index_safe('wp_ma_deal_vendor_requests', 'idx_vendor_requests_transaction_status_created', ['transaction_id', 'status', 'created_at'], 'Optimizes vendor request queries') ? $total_created++ : $total_failed++;

// EVENT LOG INDEXES
echo "\n[14/14] wp_ma_deal_events\n";
create_index_safe('wp_ma_deal_events', 'idx_events_entity_created', ['entity_type', 'entity_id', 'created_at'], 'Optimizes entity event log queries') ? $total_created++ : $total_failed++;

echo "\n=================================================================\n";
echo "SUMMARY\n";
echo "=================================================================\n";
echo "Indexes created: {$total_created}\n";
echo "Indexes skipped (already exist): " . (29 - $total_created - $total_failed) . "\n";
echo "Indexes failed: {$total_failed}\n";
echo "Total expected: 29\n";

// Record migration 013 in migrations table
$migration_recorded = $wpdb->get_var(
    "SELECT migration_number FROM {$wpdb->prefix}ma_deal_migrations WHERE migration_number = '013'"
);

if (!$migration_recorded && $total_failed === 0) {
    echo "\nRecording migration 013 in migrations table...\n";
    $result = $wpdb->insert(
        $wpdb->prefix . 'ma_deal_migrations',
        [
            'migration_number' => '013',
            'migration_name' => 'Add Performance Indexes',
            'applied_at' => current_time('mysql'),
            'rollback_available' => 1,
        ]
    );

    if ($wpdb->insert_id) {
        echo "✓ Migration 013 recorded successfully!\n";
    } else {
        echo "✗ Failed to record migration 013: " . $wpdb->last_error . "\n";
    }
} elseif ($migration_recorded) {
    echo "\n✓ Migration 013 already recorded in migrations table\n";
}

echo "\n=================================================================\n";
echo $total_failed === 0 ? "SUCCESS: All indexes applied!\n" : "FAILED: Some indexes could not be created\n";
echo "=================================================================\n";

exit($total_failed === 0 ? 0 : 1);
