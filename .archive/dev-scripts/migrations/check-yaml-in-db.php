<?php
require_once('/var/www/html/wp-load.php');

global $wpdb;
$template = $wpdb->get_row("SELECT * FROM wp_ma_deal_templates WHERE name LIKE '%Septic%Enhanced%' ORDER BY id DESC LIMIT 1");

if ($template) {
    echo "Template: {$template->name} (ID: {$template->id})\n\n";

    $yaml = $template->template_yaml;

    echo "YAML length: " . strlen($yaml) . " bytes\n\n";

    // Check for new anchors
    $checks = [
        'due: LoanCommitment' => strpos($yaml, 'due: LoanCommitment') !== false,
        'due: Offer' => strpos($yaml, 'due: Offer') !== false,
        'review_loan_terms' => strpos($yaml, 'review_loan_terms') !== false,
        'clear_loan_conditions' => strpos($yaml, 'clear_loan_conditions') !== false,
    ];

    echo "Content checks:\n";
    foreach ($checks as $search => $found) {
        echo "  " . ($found ? "✓" : "✗") . " {$search}\n";
    }

    // Extract the review_loan_terms section
    if ($checks['review_loan_terms']) {
        echo "\n=== review_loan_terms task in DB ===\n";
        $start = strpos($yaml, '- id: review_loan_terms');
        if ($start !== false) {
            $section = substr($yaml, $start, 500);
            $lines = explode("\n", $section);
            for ($i = 0; $i < min(12, count($lines)); $i++) {
                echo $lines[$i] . "\n";
            }
        }
    }
} else {
    echo "Template not found\n";
}
