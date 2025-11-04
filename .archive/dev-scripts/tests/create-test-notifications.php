<?php
/**
 * Create Test In-App Notifications
 *
 * This script creates various types of in-app notifications with test data
 * so you can see how the notification system looks.
 */

// Load WordPress
require_once __DIR__ . '/wp-load.php';

use MADealRoom\Repositories\NotificationRepository;

echo "========================================\n";
echo "Creating Test In-App Notifications\n";
echo "========================================\n\n";

// Initialize repository
$notification_repo = new NotificationRepository();

// Get or create test user
$test_email = 'demo@example.com';
$user = get_user_by('email', $test_email);
if (!$user) {
    $user_id = wp_create_user('demouser', 'demopass123', $test_email);
    $user = get_user_by('id', $user_id);
    echo "✓ Created test user: {$test_email} (ID: {$user->ID})\n";
} else {
    echo "✓ Using test user: {$test_email} (ID: {$user->ID})\n";
}

echo "\nCreating notifications for user ID: {$user->ID}\n\n";

// Clear existing notifications for this user (optional)
global $wpdb;
$table = $wpdb->prefix . 'ma_deal_notifications';
$deleted = $wpdb->delete($table, ['user_id' => $user->ID]);
echo "Cleared {$deleted} existing notifications for this user.\n\n";

$notifications_created = 0;

// Notification 1: Task Assigned
echo "1. Creating 'Task Assigned' notification...\n";
$notif1 = $notification_repo->create([
    'user_id' => $user->ID,
    'type' => 'task_assigned',
    'title' => 'New Task Assigned',
    'message' => 'You have been assigned: Review Purchase Agreement for 123 Main Street, Boston',
    'link' => '/transactions/123/tasks/456',
    'entity_type' => 'task',
    'entity_id' => 456,
    'is_read' => 0,
]);
echo $notif1 ? "✓ Created (ID: {$notif1})\n" : "✗ Failed\n";
if ($notif1) $notifications_created++;

// Notification 2: Task Reminder (unread)
echo "\n2. Creating 'Task Reminder' notification...\n";
$notif2 = $notification_repo->create([
    'user_id' => $user->ID,
    'type' => 'task_reminder',
    'title' => 'Task Due Soon',
    'message' => 'URGENT: Upload Inspection Report is due in 1 day',
    'link' => '/transactions/123/tasks/457',
    'entity_type' => 'task',
    'entity_id' => 457,
    'is_read' => 0,
]);
echo $notif2 ? "✓ Created (ID: {$notif2})\n" : "✗ Failed\n";
if ($notif2) $notifications_created++;

// Notification 3: Task Status Updated
echo "\n3. Creating 'Task Status Updated' notification...\n";
$notif3 = $notification_repo->create([
    'user_id' => $user->ID,
    'type' => 'task_status_updated',
    'title' => 'Task Completed',
    'message' => 'Sign Purchase Agreement has been marked as completed',
    'link' => '/transactions/123/tasks/458',
    'entity_type' => 'task',
    'entity_id' => 458,
    'is_read' => 0,
]);
echo $notif3 ? "✓ Created (ID: {$notif3})\n" : "✗ Failed\n";
if ($notif3) $notifications_created++;

// Notification 4: Document Uploaded
echo "\n4. Creating 'Document Uploaded' notification...\n";
$notif4 = $notification_repo->create([
    'user_id' => $user->ID,
    'type' => 'document_uploaded',
    'title' => 'New Document Uploaded',
    'message' => 'Inspection Report has been uploaded by Agent Smith',
    'link' => '/transactions/123/documents/101',
    'entity_type' => 'document',
    'entity_id' => 101,
    'is_read' => 0,
]);
echo $notif4 ? "✓ Created (ID: {$notif4})\n" : "✗ Failed\n";
if ($notif4) $notifications_created++;

// Notification 5: Transaction Status Updated
echo "\n5. Creating 'Transaction Status Updated' notification...\n";
$notif5 = $notification_repo->create([
    'user_id' => $user->ID,
    'type' => 'transaction_updated',
    'title' => 'Transaction Status Changed',
    'message' => '123 Main Street, Boston status changed from "Pending" to "Under Contract"',
    'link' => '/transactions/123',
    'entity_type' => 'transaction',
    'entity_id' => 123,
    'is_read' => 0,
]);
echo $notif5 ? "✓ Created (ID: {$notif5})\n" : "✗ Failed\n";
if ($notif5) $notifications_created++;

// Notification 6: Party Added (read notification)
echo "\n6. Creating 'Party Added' notification (marked as read)...\n";
$notif6 = $notification_repo->create([
    'user_id' => $user->ID,
    'type' => 'party_added',
    'title' => 'Added to Transaction',
    'message' => 'You have been added to transaction: 456 Oak Avenue, Cambridge',
    'link' => '/transactions/456',
    'entity_type' => 'transaction',
    'entity_id' => 456,
    'is_read' => 1,
]);
echo $notif6 ? "✓ Created (ID: {$notif6})\n" : "✗ Failed\n";
if ($notif6) {
    $notifications_created++;
    // Mark as read
    $notification_repo->markAsRead($notif6);
    echo "  ✓ Marked as read\n";
}

// Notification 7: Vendor Request
echo "\n7. Creating 'Vendor Request' notification...\n";
$notif7 = $notification_repo->create([
    'user_id' => $user->ID,
    'type' => 'vendor_request',
    'title' => 'Vendor Action Required',
    'message' => 'Please provide home inspection services for 789 Elm Street, Somerville',
    'link' => '/vendor/requests/202',
    'entity_type' => 'vendor_request',
    'entity_id' => 202,
    'is_read' => 0,
]);
echo $notif7 ? "✓ Created (ID: {$notif7})\n" : "✗ Failed\n";
if ($notif7) $notifications_created++;

// Notification 8: Comment Added
echo "\n8. Creating 'Comment Added' notification...\n";
$notif8 = $notification_repo->create([
    'user_id' => $user->ID,
    'type' => 'comment_added',
    'title' => 'New Comment on Task',
    'message' => 'Jane Doe commented on "Review Purchase Agreement": Please review ASAP',
    'link' => '/transactions/123/tasks/456#comments',
    'entity_type' => 'task',
    'entity_id' => 456,
    'is_read' => 0,
]);
echo $notif8 ? "✓ Created (ID: {$notif8})\n" : "✗ Failed\n";
if ($notif8) $notifications_created++;

// Notification 9: Deadline Approaching
echo "\n9. Creating 'Deadline Approaching' notification...\n";
$notif9 = $notification_repo->create([
    'user_id' => $user->ID,
    'type' => 'deadline_approaching',
    'title' => 'Deadline Alert',
    'message' => 'Transaction 123 Main Street has 3 tasks due within 24 hours',
    'link' => '/transactions/123/tasks',
    'entity_type' => 'transaction',
    'entity_id' => 123,
    'is_read' => 0,
]);
echo $notif9 ? "✓ Created (ID: {$notif9})\n" : "✗ Failed\n";
if ($notif9) $notifications_created++;

// Notification 10: System Alert
echo "\n10. Creating 'System Alert' notification...\n";
$notif10 = $notification_repo->create([
    'user_id' => $user->ID,
    'type' => 'system',
    'title' => 'Welcome to MA Deal Room',
    'message' => 'Thank you for joining! Complete your profile to get started.',
    'link' => '/profile/edit',
    'entity_type' => null,
    'entity_id' => null,
    'is_read' => 0,
]);
echo $notif10 ? "✓ Created (ID: {$notif10})\n" : "✗ Failed\n";
if ($notif10) $notifications_created++;

// Create some older notifications (with different timestamps)
echo "\n11. Creating older notifications with backdated timestamps...\n";

// Backdate by 2 days
$old_date1 = date('Y-m-d H:i:s', strtotime('-2 days'));
$wpdb->insert(
    $table,
    [
        'user_id' => $user->ID,
        'user_type' => 'wordpress',
        'type' => 'task_assigned',
        'title' => 'Old Task Assigned',
        'message' => 'You were assigned: Schedule Final Walkthrough (2 days ago)',
        'link' => '/transactions/123/tasks/455',
        'entity_type' => 'task',
        'entity_id' => 455,
        'is_read' => 1,
        'read_at' => $old_date1,
        'created_at' => $old_date1,
    ],
    ['%d', '%s', '%s', '%s', '%s', '%s', '%s', '%d', '%d', '%s', '%s']
);
echo "✓ Created old notification (2 days ago)\n";
$notifications_created++;

// Backdate by 5 days
$old_date2 = date('Y-m-d H:i:s', strtotime('-5 days'));
$wpdb->insert(
    $table,
    [
        'user_id' => $user->ID,
        'user_type' => 'wordpress',
        'type' => 'transaction_updated',
        'title' => 'Transaction Created',
        'message' => 'New transaction created: 123 Main Street, Boston (5 days ago)',
        'link' => '/transactions/123',
        'entity_type' => 'transaction',
        'entity_id' => 123,
        'is_read' => 1,
        'read_at' => $old_date2,
        'created_at' => $old_date2,
    ],
    ['%d', '%s', '%s', '%s', '%s', '%s', '%s', '%d', '%d', '%s', '%s']
);
echo "✓ Created old notification (5 days ago)\n";
$notifications_created++;

// Get notification counts
$unread_count = $notification_repo->getUnreadCount($user->ID);
$total_count = $wpdb->get_var($wpdb->prepare(
    "SELECT COUNT(*) FROM {$table} WHERE user_id = %d",
    $user->ID
));

echo "\n========================================\n";
echo "Summary\n";
echo "========================================\n";
echo "✓ Created {$notifications_created} notifications\n";
echo "✓ Total notifications for user: {$total_count}\n";
echo "✓ Unread notifications: {$unread_count}\n";
echo "✓ Read notifications: " . ($total_count - $unread_count) . "\n";
echo "\n";

echo "Notification Types Created:\n";
echo "- task_assigned (2)\n";
echo "- task_reminder (1)\n";
echo "- task_status_updated (1)\n";
echo "- document_uploaded (1)\n";
echo "- transaction_updated (2)\n";
echo "- party_added (1)\n";
echo "- vendor_request (1)\n";
echo "- comment_added (1)\n";
echo "- deadline_approaching (1)\n";
echo "- system (1)\n";
echo "\n";

echo "How to View Notifications:\n";
echo "============================\n";
echo "1. REST API Endpoint:\n";
echo "   GET /wp-json/ma-deal-room/v1/notifications?user_id={$user->ID}\n";
echo "\n";
echo "2. Database Query:\n";
echo "   SELECT * FROM {$table} WHERE user_id = {$user->ID} ORDER BY created_at DESC;\n";
echo "\n";
echo "3. Using NotificationRepository:\n";
echo "   \$repo = new NotificationRepository();\n";
echo "   \$notifications = \$repo->getByUserId({$user->ID});\n";
echo "\n";

echo "Test the REST API:\n";
echo "==================\n";
echo "curl -X GET 'http://localhost/wp-json/ma-deal-room/v1/notifications?user_id={$user->ID}' \\\n";
echo "  -H 'Content-Type: application/json'\n";
echo "\n";

echo "Mark notification as read:\n";
echo "==========================\n";
echo "curl -X POST 'http://localhost/wp-json/ma-deal-room/v1/notifications/{$notif1}/read' \\\n";
echo "  -H 'Content-Type: application/json'\n";
echo "\n";

echo "========================================\n";
echo "✓ Test notifications created successfully!\n";
echo "========================================\n";
