#!/usr/bin/env php
<?php
/**
 * Sync Task Definitions from YAML Templates
 *
 * This script extracts task definitions from YAML templates and syncs them to the database.
 */

// Load WordPress
require_once __DIR__ . '/wp-load.php';

echo "\n=== Syncing Task Definitions ===\n\n";

// Get the plugin instance
$plugin = \MADealRoom\Core\Plugin::instance();
$task_def_repo = $plugin->container()->get('task_definition_repository');

// Try multiple possible paths
$possible_paths = [
    __DIR__ . '/ma-deal-room/assets/templates/',
    __DIR__ . '/wp-content/plugins/ma-deal-room/assets/templates/',
    '/var/www/html/wp-content/plugins/ma-deal-room/assets/templates/',
];

$templates_dir = null;
foreach ($possible_paths as $path) {
    if (is_dir($path)) {
        $templates_dir = $path;
        break;
    }
}

if (!$templates_dir) {
    die("Templates directory not found. Tried:\n" . implode("\n", $possible_paths) . "\n");
}

if (!is_dir($templates_dir)) {
    die("Templates directory not found: {$templates_dir}\n");
}

$template_files = glob($templates_dir . '*.yaml');

if (empty($template_files)) {
    die("No template files found in {$templates_dir}\n");
}

echo "Found " . count($template_files) . " template files.\n\n";

$created = 0;
$updated = 0;
$skipped = 0;

function extractTasks($parsed) {
    $tasks = [];

    // Check for tasks in 'tasks' key
    if (isset($parsed['tasks']) && is_array($parsed['tasks'])) {
        $tasks = array_merge($tasks, $parsed['tasks']);
    }

    // Check for tasks in 'conditional_tasks' key
    if (isset($parsed['conditional_tasks']) && is_array($parsed['conditional_tasks'])) {
        foreach ($parsed['conditional_tasks'] as $conditional_group) {
            if (isset($conditional_group['tasks']) && is_array($conditional_group['tasks'])) {
                $tasks = array_merge($tasks, $conditional_group['tasks']);
            }
        }
    }

    return $tasks;
}

function prepareTaskDefinition($task) {
    // Calculate due_calculation from anchor_date and due_date_offset
    $due_calculation = null;
    if (isset($task['anchor_date']) && isset($task['due_date_offset'])) {
        $anchor = $task['anchor_date'];
        $offset = (int)$task['due_date_offset'];
        $sign = $offset >= 0 ? '+' : '';
        $due_calculation = "{$anchor}{$sign}{$offset}d";
    }

    return [
        'task_key' => $task['id'] ?? '',
        'title' => $task['title'] ?? '',
        'description' => $task['description'] ?? '',
        'category' => $task['category'] ?? 'general',
        'owner_role' => $task['responsible_party'] ?? 'agent',
        'priority' => $task['priority'] ?? 'normal',
        'estimated_duration' => isset($task['estimated_duration']) ? (int)$task['estimated_duration'] : null,
        'due_calculation' => $due_calculation,
        'applies_if' => !empty($task['conditions']) ? json_encode($task['conditions']) : null,
        'depends_on' => !empty($task['depends_on']) ? json_encode($task['depends_on']) : null,
        'metadata' => json_encode($task),
        'is_system' => true,
        'is_milestone' => !empty($task['is_milestone']),
        'is_required' => !empty($task['is_required']),
        'account_id' => null,  // NULL for system tasks
        'created_by_user_id' => null,
        'created_at' => current_time('mysql'),
        'updated_at' => current_time('mysql'),
    ];
}

foreach ($template_files as $file) {
    $filename = basename($file);
    echo "Processing: {$filename}\n";

    try {
        // Read and parse YAML
        $yaml_content = file_get_contents($file);
        if ($yaml_content === false) {
            echo "  ✗ Failed to read file\n";
            continue;
        }

        $parsed = \Symfony\Component\Yaml\Yaml::parse($yaml_content);

        // Extract tasks
        $tasks = extractTasks($parsed);

        if (empty($tasks)) {
            echo "  No tasks found\n";
            continue;
        }

        echo "  Found " . count($tasks) . " tasks\n";

        foreach ($tasks as $task) {
            $task_key = $task['id'] ?? null;

            if (!$task_key) {
                $skipped++;
                continue;
            }

            // Prepare task definition data
            $task_data = prepareTaskDefinition($task);

            // Check if task definition already exists
            $existing = $task_def_repo->findByTaskKey($task_key);

            if ($existing) {
                // Update existing
                $success = $task_def_repo->update($existing->id, $task_data);
                if ($success) {
                    $updated++;
                    echo "    ✓ Updated: {$task_key}\n";
                } else {
                    echo "    ✗ Failed to update: {$task_key}\n";
                    global $wpdb;
                    if ($wpdb->last_error) {
                        echo "      Error: {$wpdb->last_error}\n";
                    }
                }
            } else {
                // Create new
                $id = $task_def_repo->create($task_data);
                if ($id) {
                    $created++;
                    echo "    ✓ Created: {$task_key}\n";
                } else {
                    echo "    ✗ Failed to create: {$task_key}\n";
                    global $wpdb;
                    if ($wpdb->last_error) {
                        echo "      Error: {$wpdb->last_error}\n";
                    }
                }
            }
        }
    } catch (\Exception $e) {
        echo "  ✗ Error: " . $e->getMessage() . "\n";
    }
}

echo "\n=== Sync Complete ===\n";
echo "Created: {$created}\n";
echo "Updated: {$updated}\n";
echo "Skipped: {$skipped}\n";

// Verify count
$total = $task_def_repo->query([]);
echo "\nTotal task definitions in database: " . count($total) . "\n\n";
