<?php
require_once('/var/www/html/wp-load.php');

global $wpdb;
$templates = $wpdb->get_results("SELECT id, name, yaml_path, is_system FROM wp_ma_deal_templates ORDER BY id");

echo "Total templates: " . count($templates) . "\n\n";

foreach ($templates as $t) {
    echo "ID: {$t->id}\n";
    echo "Name: {$t->name}\n";
    echo "System: {$t->is_system}\n";
    echo "YAML Path: \"{$t->yaml_path}\"\n";
    echo "---\n";
}
