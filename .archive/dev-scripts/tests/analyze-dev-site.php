<?php
/**
 * Comprehensive analysis of dev site to capture exact state
 * This will be used to create a perfect reset/reinstall system
 */

// Load WordPress
define('WP_USE_THEMES', false);
require_once(__DIR__ . '/wp-load.php');

header('Content-Type: text/plain; charset=utf-8');

echo "=== MA Deal Room Dev Site Analysis ===\n";
echo "Date: " . date('Y-m-d H:i:s') . "\n\n";

global $wpdb;

// 1. Database Tables Structure
echo "1. DATABASE TABLES STRUCTURE\n";
echo str_repeat('=', 80) . "\n\n";

$tables = $wpdb->get_results("SHOW TABLES LIKE 'wp_ma_deal_%'", ARRAY_N);

foreach ($tables as $table) {
    $table_name = $table[0];
    echo "TABLE: {$table_name}\n";
    echo str_repeat('-', 80) . "\n";

    // Get CREATE TABLE statement
    $create = $wpdb->get_row("SHOW CREATE TABLE {$table_name}", ARRAY_N);
    echo $create[1] . ";\n\n";

    // Get row count
    $count = $wpdb->get_var("SELECT COUNT(*) FROM {$table_name}");
    echo "Row count: {$count}\n";

    // Get sample data (first 3 rows)
    if ($count > 0 && $count < 100) {
        echo "\nSample data:\n";
        $sample = $wpdb->get_results("SELECT * FROM {$table_name} LIMIT 3", ARRAY_A);
        echo json_encode($sample, JSON_PRETTY_PRINT) . "\n";
    }

    echo "\n" . str_repeat('-', 80) . "\n\n";
}

// 2. WordPress Options
echo "\n2. WORDPRESS OPTIONS\n";
echo str_repeat('=', 80) . "\n\n";

$options = $wpdb->get_results(
    "SELECT option_name, option_value FROM {$wpdb->options}
     WHERE option_name LIKE 'ma_deal_room%'
     ORDER BY option_name",
    ARRAY_A
);

foreach ($options as $option) {
    echo "{$option['option_name']}: {$option['option_value']}\n";
}

// 3. Key Data Counts
echo "\n3. KEY DATA COUNTS\n";
echo str_repeat('=', 80) . "\n\n";

$counts = [
    'Accounts' => "SELECT COUNT(*) FROM wp_ma_deal_accounts",
    'Transactions' => "SELECT COUNT(*) FROM wp_ma_deal_transactions",
    'Tasks' => "SELECT COUNT(*) FROM wp_ma_deal_tasks",
    'Task Definitions' => "SELECT COUNT(*) FROM wp_ma_deal_task_definitions",
    'Templates' => "SELECT COUNT(*) FROM wp_ma_deal_templates",
    'Documents' => "SELECT COUNT(*) FROM wp_ma_deal_documents",
    'Notifications' => "SELECT COUNT(*) FROM wp_ma_deal_notifications",
    'Users' => "SELECT COUNT(*) FROM wp_ma_deal_users",
    'MLS Configs' => "SELECT COUNT(*) FROM wp_ma_deal_mls_config",
    'MLS Sync Logs' => "SELECT COUNT(*) FROM wp_ma_deal_mls_sync_log",
];

foreach ($counts as $label => $query) {
    $count = $wpdb->get_var($query);
    echo sprintf("%-20s: %d\n", $label, $count);
}

// 4. Task Definitions Analysis
echo "\n4. TASK DEFINITIONS ANALYSIS\n";
echo str_repeat('=', 80) . "\n\n";

$task_defs = $wpdb->get_results(
    "SELECT transaction_type, COUNT(*) as count
     FROM wp_ma_deal_task_definitions
     GROUP BY transaction_type
     ORDER BY transaction_type",
    ARRAY_A
);

echo "Task definitions by transaction type:\n";
foreach ($task_defs as $def) {
    echo sprintf("  %-30s: %d tasks\n", $def['transaction_type'], $def['count']);
}

// Get unique categories
$categories = $wpdb->get_results(
    "SELECT DISTINCT category FROM wp_ma_deal_task_definitions ORDER BY category",
    ARRAY_A
);

echo "\nUnique categories:\n";
foreach ($categories as $cat) {
    $category = $cat['category'] ?: '(null)';
    $count = $wpdb->get_var($wpdb->prepare(
        "SELECT COUNT(*) FROM wp_ma_deal_task_definitions WHERE category = %s OR (category IS NULL AND %s = '(null)')",
        $cat['category'],
        $category
    ));
    echo sprintf("  %-30s: %d tasks\n", $category, $count);
}

// 5. Templates Analysis
echo "\n5. TEMPLATES ANALYSIS\n";
echo str_repeat('=', 80) . "\n\n";

$templates = $wpdb->get_results(
    "SELECT id, name, transaction_type, is_system
     FROM wp_ma_deal_templates
     ORDER BY is_system DESC, name",
    ARRAY_A
);

echo "Templates:\n";
foreach ($templates as $template) {
    $type = $template['is_system'] ? 'SYSTEM' : 'CUSTOM';
    echo sprintf("  [%s] %s (%s)\n", $type, $template['name'], $template['transaction_type']);
}

// 6. Default Account
echo "\n6. DEFAULT ACCOUNT\n";
echo str_repeat('=', 80) . "\n\n";

$default_account = $wpdb->get_row(
    "SELECT * FROM wp_ma_deal_accounts WHERE id = 1",
    ARRAY_A
);

if ($default_account) {
    echo "Default account exists:\n";
    foreach ($default_account as $key => $value) {
        if ($key !== 'settings') {
            echo sprintf("  %-20s: %s\n", $key, $value);
        }
    }
} else {
    echo "No default account found\n";
}

// 7. REST API Routes
echo "\n7. REST API ROUTES\n";
echo str_repeat('=', 80) . "\n\n";

$rest_server = rest_get_server();
$routes = $rest_server->get_routes('ma-deal-room/v1');

echo "Registered MA Deal Room routes (" . count($routes) . "):\n";
foreach (array_keys($routes) as $route) {
    echo "  {$route}\n";
}

// 8. Plugin Files
echo "\n8. CRITICAL PLUGIN FILES\n";
echo str_repeat('=', 80) . "\n\n";

$critical_files = [
    'Main plugin' => 'wp-content/plugins/ma-deal-room/ma-deal-room.php',
    'Uninstall script' => 'wp-content/plugins/ma-deal-room/uninstall.php',
    'Plugin class' => 'wp-content/plugins/ma-deal-room/src/Core/Plugin.php',
    'Migrator' => 'wp-content/plugins/ma-deal-room/src/Database/Migrator.php',
    'AnalyticsController' => 'wp-content/plugins/ma-deal-room/src/REST/Controllers/AnalyticsController.php',
    'MLSController' => 'wp-content/plugins/ma-deal-room/src/REST/Controllers/MLSController.php',
    'Composer autoload' => 'wp-content/plugins/ma-deal-room/vendor/autoload.php',
    'JS bundle' => 'wp-content/plugins/ma-deal-room/assets/admin/dist/main.js',
    'CSS bundle' => 'wp-content/plugins/ma-deal-room/assets/admin/dist/style.css',
];

foreach ($critical_files as $label => $path) {
    $full_path = ABSPATH . $path;
    if (file_exists($full_path)) {
        $size = filesize($full_path);
        $modified = date('Y-m-d H:i:s', filemtime($full_path));
        echo sprintf("  ✓ %-25s: %s (%s)\n", $label, formatBytes($size), $modified);
    } else {
        echo sprintf("  ✗ %-25s: MISSING\n", $label);
    }
}

// 9. Migrations Status
echo "\n9. MIGRATIONS STATUS\n";
echo str_repeat('=', 80) . "\n\n";

$migration_files = glob(WP_PLUGIN_DIR . '/ma-deal-room/database/migrations/*.sql');
$migration_files = array_filter($migration_files, function($file) {
    return strpos($file, 'rollback') === false;
});

sort($migration_files);

echo "Migration files found: " . count($migration_files) . "\n\n";

foreach ($migration_files as $file) {
    $filename = basename($file);
    echo "  {$filename}\n";
}

// 10. Service Container
echo "\n10. SERVICE CONTAINER\n";
echo str_repeat('=', 80) . "\n\n";

try {
    $plugin = \MADealRoom\Core\Plugin::instance();
    $container = $plugin->container();

    $services = [
        'cache_service',
        'analytics_service',
        'transaction_repository',
        'task_repository',
        'task_definition_repository',
        'template_repository',
        'document_repository',
        'notification_repository',
        'user_repository',
        'account_repository',
        'mls_config_repository',
    ];

    echo "Service container services:\n";
    foreach ($services as $service) {
        try {
            $instance = $container->get($service);
            echo sprintf("  ✓ %-30s: %s\n", $service, get_class($instance));
        } catch (Exception $e) {
            echo sprintf("  ✗ %-30s: NOT REGISTERED\n", $service);
        }
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}

echo "\n" . str_repeat('=', 80) . "\n";
echo "Analysis complete\n";

// Helper function
function formatBytes($bytes, $precision = 2) {
    $units = ['B', 'KB', 'MB', 'GB'];
    $bytes = max($bytes, 0);
    $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
    $pow = min($pow, count($units) - 1);
    $bytes /= pow(1024, $pow);
    return round($bytes, $precision) . ' ' . $units[$pow];
}
