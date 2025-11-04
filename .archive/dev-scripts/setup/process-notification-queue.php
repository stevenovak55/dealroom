<?php
/**
 * Manually Process Notification Queue
 *
 * This script processes pending notifications in the queue
 * and sends bundled emails.
 */

require_once __DIR__ . '/wp-load.php';

use MADealRoom\Services\NotificationQueueService;

echo "========================================\n";
echo "Processing Notification Queue\n";
echo "========================================\n\n";

$queue_service = new NotificationQueueService();

// Get current stats
echo "Queue Statistics (Before Processing):\n";
$stats_before = $queue_service->getStats();
echo "  Pending: {$stats_before['pending']}\n";
echo "  Ready to Process: {$stats_before['ready_to_process']}\n";
echo "  Already Sent: {$stats_before['sent']}\n";
echo "  Failed: {$stats_before['failed']}\n\n";

if ($stats_before['pending'] == 0) {
    echo "No pending notifications in queue.\n\n";
    echo "To test:\n";
    echo "1. Create a new transaction in the web interface\n";
    echo "2. Add a party (seller agent) with an email\n";
    echo "3. This will queue notifications for all assigned tasks\n";
    echo "4. Run this script again to process the queue\n\n";
    exit(0);
}

// Process the queue
echo "Processing queue...\n\n";
$results = $queue_service->processPending();

echo "========================================\n";
echo "Processing Results\n";
echo "========================================\n";
echo "Processed: {$results['processed']} notifications\n";
echo "Sent: {$results['sent']} emails\n";
echo "Bundled: {$results['bundled']} bundled emails\n";
echo "Failed: {$results['failed']} failures\n\n";

// Get stats after processing
echo "Queue Statistics (After Processing):\n";
$stats_after = $queue_service->getStats();
echo "  Pending: {$stats_after['pending']}\n";
echo "  Sent: {$stats_after['sent']}\n";
echo "  Failed: {$stats_after['failed']}\n\n";

echo "========================================\n";
if ($results['bundled'] > 0) {
    echo "✓ SUCCESS! Bundled emails were sent!\n";
    echo "\nCheck MailHog at http://localhost:8025 to see:\n";
    echo "- Bundled task assignment emails (instead of individual ones)\n";
    echo "- Professional templates with task summaries\n";
} elseif ($results['sent'] > 0) {
    echo "✓ Emails sent (but none were bundled)\n";
    echo "  This might mean there was only 1 notification per user/type\n";
} else {
    echo "No emails were sent.\n";
}
echo "========================================\n";
