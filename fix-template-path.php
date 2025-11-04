<?php
require_once('/var/www/html/wp-load.php');

global $wpdb;

echo "Fixing template yaml_path...\n\n";

// Find templates with empty yaml_path
$templates = $wpdb->get_results("SELECT * FROM wp_ma_deal_templates WHERE (yaml_path IS NULL OR yaml_path = '') AND is_system = 1");

foreach ($templates as $template) {
    echo "Template: {$template->name} (ID: {$template->id})\n";
    echo "Current yaml_path: '{$template->yaml_path}'\n";

    // Determine correct YAML file based on name
    $yaml_file = null;
    if (strpos($template->name, 'Septic') !== false) {
        $yaml_file = 'sfh_septic.yaml';
    } elseif (strpos($template->name, 'City Water') !== false) {
        $yaml_file = 'sfh_city_water.yaml';
    } elseif (strpos($template->name, 'Condo') !== false) {
        $yaml_file = 'condo.yaml';
    } elseif (strpos($template->name, 'Multifamily') !== false || strpos($template->name, 'Multi-Family') !== false) {
        $yaml_file = 'multifamily.yaml';
    }

    if ($yaml_file) {
        // Check if file exists
        $full_path = '/var/www/html/wp-content/plugins/ma-deal-room/assets/templates/' . $yaml_file;
        if (file_exists($full_path)) {
            echo "Updating to: $yaml_file\n";

            $wpdb->update(
                'wp_ma_deal_templates',
                ['yaml_path' => $yaml_file],
                ['id' => $template->id]
            );

            echo "✓ Updated successfully\n\n";
        } else {
            echo "✗ File not found: $full_path\n\n";
        }
    } else {
        echo "Could not determine YAML file for this template\n\n";
    }
}

// Verify updates
echo "═══ VERIFICATION ═══\n\n";
$templates = $wpdb->get_results("SELECT id, name, yaml_path FROM wp_ma_deal_templates WHERE is_system = 1");
foreach ($templates as $template) {
    echo "ID {$template->id}: {$template->name}\n";
    echo "  YAML: {$template->yaml_path}\n";

    if ($template->yaml_path) {
        $full_path = '/var/www/html/wp-content/plugins/ma-deal-room/assets/templates/' . $template->yaml_path;
        $size = file_exists($full_path) ? filesize($full_path) : 0;
        echo "  Size: " . number_format($size) . " bytes\n";

        if ($size > 10000) {
            $content = file_get_contents($full_path);
            if (strpos($content, 'LoanCommitment') !== false) {
                echo "  ✓ Contains LoanCommitment anchor\n";
            }
        }
    }
    echo "\n";
}
