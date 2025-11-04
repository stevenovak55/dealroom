<?php
/**
 * PHPUnit Bootstrap File
 *
 * Sets up the WordPress test environment and loads the MA Deal Room plugin.
 *
 * @package MADealRoom\Tests
 */

// Composer autoloader
require_once __DIR__ . '/../vendor/autoload.php';

// Define WordPress constants
if (!defined('ABSPATH')) {
    define('ABSPATH', __DIR__ . '/../');
}

// Define test mode constants
if (!defined('MA_DEAL_ROOM_TESTING')) {
    define('MA_DEAL_ROOM_TESTING', true);
}
if (!defined('MA_DEAL_ROOM_TEST_MODE')) {
    define('MA_DEAL_ROOM_TEST_MODE', true);
}
if (!defined('MA_DEAL_ROOM_VERSION')) {
    define('MA_DEAL_ROOM_VERSION', '1.0.0');
}
if (!defined('MA_DEAL_ROOM_PLUGIN_FILE')) {
    define('MA_DEAL_ROOM_PLUGIN_FILE', __DIR__ . '/../ma-deal-room.php');
}
if (!defined('MA_DEAL_ROOM_PLUGIN_DIR')) {
    define('MA_DEAL_ROOM_PLUGIN_DIR', __DIR__ . '/../');
}
if (!defined('MA_DEAL_ROOM_PLUGIN_URL')) {
    define('MA_DEAL_ROOM_PLUGIN_URL', 'http://localhost/wp-content/plugins/ma-deal-room/');
}

// Load WordPress test environment if available
// This is optional - tests can run without full WordPress when testing isolated logic
$wp_tests_dir = getenv('WP_TESTS_DIR');
if ($wp_tests_dir && file_exists($wp_tests_dir . '/includes/functions.php')) {
    // Load WordPress test functions
    require_once $wp_tests_dir . '/includes/functions.php';

    // Load the plugin after WordPress is loaded
    tests_add_filter('muplugins_loaded', function() {
        require_once MA_DEAL_ROOM_PLUGIN_FILE;
    });

    // Start up the WP testing environment
    require $wp_tests_dir . '/includes/bootstrap.php';
} else {
    // Running tests without WordPress - stub WordPress functions for unit tests
    if (!function_exists('add_action')) {
        function add_action($hook, $callback, $priority = 10, $accepted_args = 1) {
            // Stub for testing
        }
    }
    if (!function_exists('add_filter')) {
        function add_filter($hook, $callback, $priority = 10, $accepted_args = 1) {
            // Stub for testing
        }
    }
    if (!function_exists('apply_filters')) {
        function apply_filters($hook, $value, ...$args) {
            // Stub for testing - just return the value
            return $value;
        }
    }
    if (!function_exists('is_email')) {
        function is_email($email) {
            return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
        }
    }
    if (!function_exists('__')) {
        function __($text, $domain = 'default') {
            return $text;
        }
    }
    if (!function_exists('size_format')) {
        function size_format($bytes, $decimals = 0) {
            $units = ['B', 'KB', 'MB', 'GB', 'TB'];
            $bytes = max($bytes, 0);
            $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
            $pow = min($pow, count($units) - 1);
            $bytes /= (1 << (10 * $pow));
            return round($bytes, $decimals) . ' ' . $units[$pow];
        }
    }
    if (!function_exists('sanitize_email')) {
        function sanitize_email($email) {
            return filter_var($email, FILTER_SANITIZE_EMAIL);
        }
    }
    if (!function_exists('esc_url_raw')) {
        function esc_url_raw($url) {
            return filter_var($url, FILTER_SANITIZE_URL);
        }
    }
    if (!function_exists('is_wp_error')) {
        function is_wp_error($thing) {
            return ($thing instanceof WP_Error);
        }
    }
    if (!function_exists('register_activation_hook')) {
        function register_activation_hook($file, $callback) {
            // Stub for testing
        }
    }
    if (!function_exists('register_deactivation_hook')) {
        function register_deactivation_hook($file, $callback) {
            // Stub for testing
        }
    }
    if (!function_exists('wp_cache_get')) {
        function wp_cache_get($key, $group = '') {
            return false;
        }
    }
    if (!function_exists('wp_cache_set')) {
        function wp_cache_set($key, $value, $group = '', $expire = 0) {
            return true;
        }
    }
    if (!function_exists('wp_cache_delete')) {
        function wp_cache_delete($key, $group = '') {
            return true;
        }
    }
    if (!function_exists('wp_cache_flush')) {
        function wp_cache_flush() {
            return true;
        }
    }
    if (!function_exists('esc_html')) {
        function esc_html($text) {
            return htmlspecialchars($text, ENT_QUOTES, 'UTF-8');
        }
    }
    if (!function_exists('esc_attr')) {
        function esc_attr($text) {
            return htmlspecialchars($text, ENT_QUOTES, 'UTF-8');
        }
    }
    if (!function_exists('esc_url')) {
        function esc_url($url) {
            return filter_var($url, FILTER_SANITIZE_URL);
        }
    }
    if (!function_exists('sanitize_text_field')) {
        function sanitize_text_field($str) {
            return strip_tags($str);
        }
    }
    if (!function_exists('wp_json_encode')) {
        function wp_json_encode($data, $options = 0, $depth = 512) {
            return json_encode($data, $options, $depth);
        }
    }
    if (!function_exists('current_time')) {
        function current_time($type, $gmt = 0) {
            return $gmt ? gmdate('Y-m-d H:i:s') : date('Y-m-d H:i:s');
        }
    }
    if (!class_exists('WP_Error')) {
        class WP_Error {
            public $errors = [];
            public $error_data = [];

            public function __construct($code = '', $message = '', $data = '') {
                if (!empty($code)) {
                    $this->errors[$code][] = $message;
                    if (!empty($data)) {
                        $this->error_data[$code] = $data;
                    }
                }
            }

            public function get_error_codes() {
                return array_keys($this->errors);
            }

            public function get_error_code() {
                $codes = $this->get_error_codes();
                return empty($codes) ? '' : $codes[0];
            }

            public function get_error_messages($code = '') {
                if (empty($code)) {
                    $all_messages = [];
                    foreach ($this->errors as $code => $messages) {
                        $all_messages = array_merge($all_messages, $messages);
                    }
                    return $all_messages;
                }
                return isset($this->errors[$code]) ? $this->errors[$code] : [];
            }

            public function get_error_message($code = '') {
                if (empty($code)) {
                    $code = $this->get_error_code();
                }
                $messages = $this->get_error_messages($code);
                return empty($messages) ? '' : $messages[0];
            }

            public function get_error_data($code = '') {
                if (empty($code)) {
                    $code = $this->get_error_code();
                }
                return isset($this->error_data[$code]) ? $this->error_data[$code] : null;
            }

            public function has_errors() {
                return !empty($this->errors);
            }

            public function add($code, $message, $data = '') {
                $this->errors[$code][] = $message;
                if (!empty($data)) {
                    $this->error_data[$code] = $data;
                }
            }
        }
    }
}

// Load database configuration for testing
global $wpdb;
if (!isset($wpdb)) {
    // Mock wpdb for unit tests
    class MockWPDB {
        public $prefix = 'wp_';
        public $insert_id = 1;
        public $last_error = '';
        public $num_queries = 0;

        public function prepare($query, ...$args) {
            return vsprintf($query, $args);
        }

        public function get_results($query, $output = OBJECT) {
            return [];
        }

        public function get_row($query, $output = OBJECT, $y = 0) {
            return null;
        }

        public function get_var($query, $x = 0, $y = 0) {
            return null;
        }

        public function query($query) {
            return true;
        }

        public function insert($table, $data, $format = null) {
            $this->insert_id++;
            return 1;
        }

        public function update($table, $data, $where, $format = null, $where_format = null) {
            return 1;
        }

        public function delete($table, $where, $where_format = null) {
            return 1;
        }
    }

    $wpdb = new MockWPDB();

    // Define WPDB constants
    if (!defined('OBJECT')) {
        define('OBJECT', 'OBJECT');
    }
    if (!defined('ARRAY_A')) {
        define('ARRAY_A', 'ARRAY_A');
    }
    if (!defined('ARRAY_N')) {
        define('ARRAY_N', 'ARRAY_N');
    }
}

echo "\nMA Deal Room Test Suite Bootstrap Complete\n";
echo "Testing Mode: " . (defined('MA_DEAL_ROOM_TESTING') ? 'ENABLED' : 'DISABLED') . "\n";
echo "WordPress Environment: " . (isset($wp_tests_dir) && $wp_tests_dir ? 'LOADED' : 'STUBBED (Unit Tests Only)') . "\n\n";
