<?php
/**
 * Verify Notifications Script
 */

require_once __DIR__ . '/wp-load.php';

global $wpdb;

$user_id = 4; // demo@example.com user
$table = $wpdb->prefix . 'ma_deal_notifications';

echo "===========================================\n";
echo "Notification Verification\n";
echo "===========================================\n\n";

// Get counts
$total = $wpdb->get_var($wpdb->prepare(
    "SELECT COUNT(*) FROM {$table} WHERE user_id = %d",
    $user_id
));

$unread = $wpdb->get_var($wpdb->prepare(
    "SELECT COUNT(*) FROM {$table} WHERE user_id = %d AND is_read = 0",
    $user_id
));

echo "User ID: {$user_id}\n";
echo "Total notifications: {$total}\n";
echo "Unread: {$unread}\n";
echo "Read: " . ($total - $unread) . "\n\n";

// Get all notifications
$notifications = $wpdb->get_results($wpdb->prepare(
    "SELECT id, type, title, message, is_read, created_at
     FROM {$table}
     WHERE user_id = %d
     ORDER BY created_at DESC",
    $user_id
));

echo "Notifications:\n";
echo str_repeat("-", 120) . "\n";
printf("%-4s | %-25s | %-35s | %-8s | %s\n", "ID", "Type", "Title", "Status", "Created");
echo str_repeat("-", 120) . "\n";

foreach ($notifications as $n) {
    printf("%-4d | %-25s | %-35s | %-8s | %s\n",
        $n->id,
        $n->type,
        substr($n->title, 0, 35),
        $n->is_read ? 'Read' : 'Unread',
        $n->created_at
    );
}

echo str_repeat("-", 120) . "\n";
echo "\n✓ Verification complete!\n";
