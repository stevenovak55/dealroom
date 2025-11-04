<?php
require_once('/var/www/html/wp-load.php');

global $wpdb;
$template = $wpdb->get_row("SELECT * FROM wp_ma_deal_templates WHERE name LIKE '%Septic%' AND is_system = 1 ORDER BY id DESC LIMIT 1");

if ($template) {
    echo "Template ID: {$template->id}\n";
    echo "Name: {$template->name}\n";
    echo "YAML Path: {$template->yaml_path}\n";
    echo "Updated: {$template->updated_at}\n\n";

    $yaml_file = '/var/www/html/wp-content/plugins/ma-deal-room/assets/templates/' . $template->yaml_path;
    if (file_exists($yaml_file)) {
        echo "YAML file exists: YES\n";
        echo "File size: " . filesize($yaml_file) . " bytes\n";
        echo "File modified: " . date('Y-m-d H:i:s', filemtime($yaml_file)) . "\n\n";

        // Check for our specific tasks
        $yaml_content = file_get_contents($yaml_file);
        if (strpos($yaml_content, 'due: LoanCommitment') !== false) {
            echo "✓ YAML contains 'due: LoanCommitment'\n";
        } else {
            echo "✗ YAML does NOT contain 'due: LoanCommitment'\n";
        }

        if (strpos($yaml_content, 'review_loan_terms') !== false) {
            echo "✓ YAML contains 'review_loan_terms'\n";
        } else {
            echo "✗ YAML does NOT contain 'review_loan_terms'\n";
        }
    } else {
        echo "YAML file NOT FOUND at: $yaml_file\n";
    }
} else {
    echo "Template not found in database\n";
}
