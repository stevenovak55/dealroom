<?php
/**
 * Data Migration Script: Parties to Contacts
 *
 * This script migrates existing party data to the new global contacts table.
 * It creates unique contact records from ma_deal_parties and links them back.
 *
 * Usage: php migrate-parties-to-contacts.php [--dry-run]
 *
 * @package MADealRoom
 */

// Parse command line arguments
$dry_run = in_array('--dry-run', $argv);

if ($dry_run) {
    echo "🔍 DRY RUN MODE - No changes will be made\n";
    echo "================================================\n\n";
}

// Load WordPress
require_once __DIR__ . '/wp-load.php';

global $wpdb;

// Table names
$parties_table = $wpdb->prefix . 'ma_deal_parties';
$contacts_table = $wpdb->prefix . 'ma_deal_contacts';
$transactions_table = $wpdb->prefix . 'ma_deal_transactions';

echo "MA Deal Room - Parties to Contacts Migration\n";
echo "================================================\n\n";

// Check if contacts table exists
$table_exists = $wpdb->get_var("SHOW TABLES LIKE '$contacts_table'");
if (!$table_exists) {
    echo "❌ Error: {$contacts_table} table does not exist\n";
    echo "   Please run migration 026 first\n";
    exit(1);
}

// Check if contact_id column exists in parties table
$column_exists = $wpdb->get_var("SHOW COLUMNS FROM {$parties_table} LIKE 'contact_id'");
if (!$column_exists) {
    echo "❌ Error: contact_id column missing from {$parties_table}\n";
    echo "   Please run migration 026 first\n";
    exit(1);
}

echo "✅ Prerequisites verified\n\n";

// Get all parties grouped by email and account
echo "📊 Analyzing existing parties...\n";

$parties = $wpdb->get_results("
    SELECT
        p.*,
        t.account_id
    FROM {$parties_table} p
    LEFT JOIN {$transactions_table} t ON p.transaction_id = t.id
    WHERE p.contact_id IS NULL
    AND p.email IS NOT NULL
    AND p.email != ''
    ORDER BY p.email, p.created_at
", ARRAY_A);

if (empty($parties)) {
    echo "✅ No parties to migrate (all parties either have contact_id or no email)\n";
    exit(0);
}

$total_parties = count($parties);
echo "   Found {$total_parties} parties to migrate\n\n";

// Group parties by email and account for deduplication
$grouped_parties = [];
foreach ($parties as $party) {
    $key = $party['account_id'] . '|' . strtolower($party['email']);
    if (!isset($grouped_parties[$key])) {
        $grouped_parties[$key] = [];
    }
    $grouped_parties[$key][] = $party;
}

$unique_contacts = count($grouped_parties);
echo "   Will create {$unique_contacts} unique contacts\n";
echo "   Average: " . round($total_parties / $unique_contacts, 1) . " parties per contact\n\n";

// Migration statistics
$stats = [
    'contacts_created' => 0,
    'parties_linked' => 0,
    'errors' => 0,
];

echo "🔄 Starting migration...\n\n";

foreach ($grouped_parties as $key => $party_group) {
    list($account_id, $email) = explode('|', $key, 2);

    // Use the most recent party data as the source
    $source_party = $party_group[0]; // First one (oldest by created_at)

    // Check if contact already exists
    $existing_contact = $wpdb->get_var($wpdb->prepare(
        "SELECT id FROM {$contacts_table} WHERE account_id = %d AND email = %s",
        $account_id,
        $email
    ));

    if ($existing_contact) {
        echo "   ℹ️  Contact already exists for {$email} (ID: {$existing_contact})\n";
        $contact_id = $existing_contact;
    } else {
        // Create new contact
        $contact_data = [
            'account_id' => $account_id,
            'contact_type' => 'person',
            'email' => $source_party['email'],
            'phone' => $source_party['phone'],
            'address' => $source_party['address'],
            'notes' => 'Migrated from party #' . $source_party['id'],
            'is_active' => true,
            'created_at' => $source_party['created_at'],
            'updated_at' => current_time('mysql'),
        ];

        // Parse name from contact_name (assuming "First Last" format)
        $name_parts = explode(' ', trim($source_party['contact_name']), 2);
        $contact_data['first_name'] = $name_parts[0] ?? '';
        $contact_data['last_name'] = $name_parts[1] ?? '';

        // If there's a company name, store it
        if (!empty($source_party['company_name'])) {
            $contact_data['company_name'] = $source_party['company_name'];
            $contact_data['contact_type'] = 'company';
        }

        if (!$dry_run) {
            $inserted = $wpdb->insert($contacts_table, $contact_data);
            if ($inserted === false) {
                echo "   ❌ Failed to create contact for {$email}: {$wpdb->last_error}\n";
                $stats['errors']++;
                continue;
            }
            $contact_id = $wpdb->insert_id;
        } else {
            $contact_id = 'DRY_RUN_' . $stats['contacts_created'];
        }

        echo "   ✅ Created contact #{$contact_id} for {$email}\n";
        $stats['contacts_created']++;
    }

    // Link all parties in this group to the contact
    foreach ($party_group as $party) {
        if (!$dry_run) {
            $updated = $wpdb->update(
                $parties_table,
                ['contact_id' => $contact_id],
                ['id' => $party['id']]
            );
            if ($updated === false) {
                echo "      ❌ Failed to link party #{$party['id']}\n";
                $stats['errors']++;
                continue;
            }
        }
        $stats['parties_linked']++;
    }

    echo "      Linked " . count($party_group) . " parties to contact #{$contact_id}\n";
}

echo "\n";
echo "================================================\n";
echo "Migration Complete!\n";
echo "================================================\n";
echo "Contacts created: {$stats['contacts_created']}\n";
echo "Parties linked:   {$stats['parties_linked']}\n";
echo "Errors:           {$stats['errors']}\n";
echo "\n";

if ($dry_run) {
    echo "ℹ️  This was a DRY RUN - no changes were made\n";
    echo "   Run without --dry-run to apply changes\n";
} else {
    echo "✅ Migration successful!\n";
    echo "\n";
    echo "Next steps:\n";
    echo "1. Verify contacts in wp_ma_deal_contacts table\n";
    echo "2. Check that parties are linked (contact_id not NULL)\n";
    echo "3. Test CRM sync with new contacts structure\n";
}

exit($stats['errors'] > 0 ? 1 : 0);
