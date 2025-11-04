<?php
/**
 * Validation Service
 *
 * Provides comprehensive input validation and sanitization methods.
 *
 * @package    MADealRoom\Services
 * @since      2.0.0
 */

namespace MADealRoom\Services;

use WP_Error;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * ValidationService Class
 *
 * Centralized validation logic for user inputs, data integrity, and security.
 */
class ValidationService {

    /**
     * Password minimum length
     *
     * @var int
     */
    private $password_min_length = 8;

    /**
     * Allowed user types
     *
     * @var array
     */
    private $allowed_user_types = ['custom', 'wordpress'];

    /**
     * Allowed role types
     *
     * @var array
     */
    private $allowed_role_types = [
        'broker',
        'agent',
        'buyer',
        'seller',
        'attorney',
        'lender',
        'inspector',
        'vendor',
        'title_company',
        'escrow',
    ];

    /**
     * Constructor
     */
    public function __construct() {
        // Allow customization via filters
        $this->password_min_length = apply_filters('ma_deal_password_min_length', $this->password_min_length);
        $this->allowed_role_types = apply_filters('ma_deal_allowed_role_types', $this->allowed_role_types);
    }

    /**
     * Validate email address
     *
     * @param string $email Email address
     * @param bool $check_dns Check DNS records
     * @return true|WP_Error True if valid, error otherwise
     */
    public function validate_email(string $email, bool $check_dns = false) {
        // Check basic format
        if (!is_email($email)) {
            return new WP_Error(
                'invalid_email',
                __('Invalid email address format', 'ma-deal-room'),
                ['status' => 400]
            );
        }

        // Check length
        if (strlen($email) > 254) {
            return new WP_Error(
                'email_too_long',
                __('Email address is too long (max 254 characters)', 'ma-deal-room'),
                ['status' => 400]
            );
        }

        // Optional DNS check
        if ($check_dns) {
            $domain = substr(strrchr($email, "@"), 1);
            if (!checkdnsrr($domain, "MX") && !checkdnsrr($domain, "A")) {
                return new WP_Error(
                    'invalid_email_domain',
                    __('Email domain does not exist', 'ma-deal-room'),
                    ['status' => 400]
                );
            }
        }

        // Check for common disposable email domains (optional)
        if (apply_filters('ma_deal_block_disposable_emails', false)) {
            $disposable_domains = $this->get_disposable_email_domains();
            $domain = substr(strrchr($email, "@"), 1);

            if (in_array(strtolower($domain), $disposable_domains)) {
                return new WP_Error(
                    'disposable_email',
                    __('Disposable email addresses are not allowed', 'ma-deal-room'),
                    ['status' => 400]
                );
            }
        }

        return true;
    }

    /**
     * Validate password strength
     *
     * @param string $password Password
     * @return true|WP_Error True if valid, error otherwise
     */
    public function validate_password(string $password) {
        // Check minimum length
        if (strlen($password) < $this->password_min_length) {
            return new WP_Error(
                'password_too_short',
                sprintf(
                    __('Password must be at least %d characters long', 'ma-deal-room'),
                    $this->password_min_length
                ),
                ['status' => 400]
            );
        }

        // Check maximum length (bcrypt limit)
        if (strlen($password) > 72) {
            return new WP_Error(
                'password_too_long',
                __('Password must not exceed 72 characters', 'ma-deal-room'),
                ['status' => 400]
            );
        }

        // Check for required character types
        $require_uppercase = apply_filters('ma_deal_password_require_uppercase', true);
        $require_lowercase = apply_filters('ma_deal_password_require_lowercase', true);
        $require_number = apply_filters('ma_deal_password_require_number', true);
        $require_special = apply_filters('ma_deal_password_require_special', false);

        if ($require_uppercase && !preg_match('/[A-Z]/', $password)) {
            return new WP_Error(
                'password_no_uppercase',
                __('Password must contain at least one uppercase letter', 'ma-deal-room'),
                ['status' => 400]
            );
        }

        if ($require_lowercase && !preg_match('/[a-z]/', $password)) {
            return new WP_Error(
                'password_no_lowercase',
                __('Password must contain at least one lowercase letter', 'ma-deal-room'),
                ['status' => 400]
            );
        }

        if ($require_number && !preg_match('/[0-9]/', $password)) {
            return new WP_Error(
                'password_no_number',
                __('Password must contain at least one number', 'ma-deal-room'),
                ['status' => 400]
            );
        }

        if ($require_special && !preg_match('/[^A-Za-z0-9]/', $password)) {
            return new WP_Error(
                'password_no_special',
                __('Password must contain at least one special character', 'ma-deal-room'),
                ['status' => 400]
            );
        }

        // Check against common passwords
        if ($this->is_common_password($password)) {
            return new WP_Error(
                'password_too_common',
                __('This password is too common. Please choose a more secure password', 'ma-deal-room'),
                ['status' => 400]
            );
        }

        return true;
    }

    /**
     * Validate phone number
     *
     * @param string $phone Phone number
     * @param string $country_code Country code (default: US)
     * @return true|WP_Error True if valid, error otherwise
     */
    public function validate_phone(string $phone, string $country_code = 'US') {
        // Remove common formatting characters
        $cleaned = preg_replace('/[\s\-\(\)\.]/', '', $phone);

        // Check if only numbers and + remain
        if (!preg_match('/^\+?[0-9]+$/', $cleaned)) {
            return new WP_Error(
                'invalid_phone',
                __('Phone number contains invalid characters', 'ma-deal-room'),
                ['status' => 400]
            );
        }

        // US phone validation
        if ($country_code === 'US') {
            // Remove leading +1 or 1
            $cleaned = preg_replace('/^\+?1/', '', $cleaned);

            // Must be exactly 10 digits
            if (strlen($cleaned) !== 10) {
                return new WP_Error(
                    'invalid_phone_length',
                    __('US phone number must be 10 digits', 'ma-deal-room'),
                    ['status' => 400]
                );
            }

            // First digit cannot be 0 or 1
            if (in_array($cleaned[0], ['0', '1'])) {
                return new WP_Error(
                    'invalid_phone_format',
                    __('US phone number cannot start with 0 or 1', 'ma-deal-room'),
                    ['status' => 400]
                );
            }
        } else {
            // International: minimum 7 digits, maximum 15
            $length = strlen($cleaned);
            if ($length < 7 || $length > 15) {
                return new WP_Error(
                    'invalid_phone_length',
                    __('Phone number must be between 7 and 15 digits', 'ma-deal-room'),
                    ['status' => 400]
                );
            }
        }

        return true;
    }

    /**
     * Validate name (first or last)
     *
     * @param string $name Name
     * @param string $type Type (first_name|last_name|full_name)
     * @return true|WP_Error True if valid, error otherwise
     */
    public function validate_name(string $name, string $type = 'first_name') {
        $trimmed = trim($name);

        // Check if empty
        if (empty($trimmed)) {
            return new WP_Error(
                'name_required',
                sprintf(__('%s is required', 'ma-deal-room'), ucwords(str_replace('_', ' ', $type))),
                ['status' => 400]
            );
        }

        // Check minimum length
        if (strlen($trimmed) < 2) {
            return new WP_Error(
                'name_too_short',
                sprintf(__('%s must be at least 2 characters', 'ma-deal-room'), ucwords(str_replace('_', ' ', $type))),
                ['status' => 400]
            );
        }

        // Check maximum length
        if (strlen($trimmed) > 100) {
            return new WP_Error(
                'name_too_long',
                sprintf(__('%s must not exceed 100 characters', 'ma-deal-room'), ucwords(str_replace('_', ' ', $type))),
                ['status' => 400]
            );
        }

        // Check for invalid characters (allow letters, spaces, hyphens, apostrophes, periods)
        if (!preg_match("/^[a-zA-Z\s\-'\.]+$/u", $trimmed)) {
            return new WP_Error(
                'name_invalid_characters',
                sprintf(__('%s contains invalid characters', 'ma-deal-room'), ucwords(str_replace('_', ' ', $type))),
                ['status' => 400]
            );
        }

        return true;
    }

    /**
     * Validate user type
     *
     * @param string $user_type User type
     * @return true|WP_Error True if valid, error otherwise
     */
    public function validate_user_type(string $user_type) {
        if (!in_array($user_type, $this->allowed_user_types)) {
            return new WP_Error(
                'invalid_user_type',
                sprintf(
                    __('Invalid user type. Allowed: %s', 'ma-deal-room'),
                    implode(', ', $this->allowed_user_types)
                ),
                ['status' => 400]
            );
        }

        return true;
    }

    /**
     * Validate role type
     *
     * @param string $role_type Role type
     * @return true|WP_Error True if valid, error otherwise
     */
    public function validate_role_type(string $role_type) {
        if (!in_array($role_type, $this->allowed_role_types)) {
            return new WP_Error(
                'invalid_role_type',
                sprintf(
                    __('Invalid role type. Allowed: %s', 'ma-deal-room'),
                    implode(', ', $this->allowed_role_types)
                ),
                ['status' => 400]
            );
        }

        return true;
    }

    /**
     * Validate transaction ID
     *
     * @param mixed $transaction_id Transaction ID
     * @return true|WP_Error True if valid, error otherwise
     */
    public function validate_transaction_id($transaction_id) {
        global $wpdb;

        if ($transaction_id === null) {
            return true; // NULL is valid for global roles
        }

        // Must be positive integer
        if (!is_numeric($transaction_id) || $transaction_id <= 0) {
            return new WP_Error(
                'invalid_transaction_id',
                __('Transaction ID must be a positive integer', 'ma-deal-room'),
                ['status' => 400]
            );
        }

        // Check if transaction exists
        $table = $wpdb->prefix . 'ma_deal_transactions';
        $exists = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$table} WHERE id = %d AND deleted_at IS NULL",
            $transaction_id
        ));

        if (!$exists) {
            return new WP_Error(
                'transaction_not_found',
                __('Transaction not found', 'ma-deal-room'),
                ['status' => 404]
            );
        }

        return true;
    }

    /**
     * Validate account ID
     *
     * @param mixed $account_id Account ID
     * @return true|WP_Error True if valid, error otherwise
     */
    public function validate_account_id($account_id) {
        global $wpdb;

        // Must be positive integer
        if (!is_numeric($account_id) || $account_id <= 0) {
            return new WP_Error(
                'invalid_account_id',
                __('Account ID must be a positive integer', 'ma-deal-room'),
                ['status' => 400]
            );
        }

        // Check if account exists
        $table = $wpdb->prefix . 'ma_deal_accounts';
        $exists = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$table} WHERE id = %d AND deleted_at IS NULL",
            $account_id
        ));

        if (!$exists) {
            return new WP_Error(
                'account_not_found',
                __('Account not found', 'ma-deal-room'),
                ['status' => 404]
            );
        }

        return true;
    }

    /**
     * Validate URL
     *
     * @param string $url URL
     * @param array $allowed_schemes Allowed URL schemes
     * @return true|WP_Error True if valid, error otherwise
     */
    public function validate_url(string $url, array $allowed_schemes = ['http', 'https']) {
        // Check basic format
        if (!filter_var($url, FILTER_VALIDATE_URL)) {
            return new WP_Error(
                'invalid_url',
                __('Invalid URL format', 'ma-deal-room'),
                ['status' => 400]
            );
        }

        // Check scheme
        $scheme = parse_url($url, PHP_URL_SCHEME);
        if (!in_array($scheme, $allowed_schemes)) {
            return new WP_Error(
                'invalid_url_scheme',
                sprintf(
                    __('URL scheme must be one of: %s', 'ma-deal-room'),
                    implode(', ', $allowed_schemes)
                ),
                ['status' => 400]
            );
        }

        return true;
    }

    /**
     * Validate file upload
     *
     * @param array $file $_FILES array element
     * @param array $options Validation options
     * @return true|WP_Error True if valid, error otherwise
     */
    public function validate_file_upload(array $file, array $options = []) {
        // Default options
        $defaults = [
            'max_size' => 10 * 1024 * 1024, // 10MB
            'allowed_types' => ['jpg', 'jpeg', 'png', 'pdf', 'doc', 'docx'],
            'required' => true,
        ];
        $options = array_merge($defaults, $options);

        // Check if file was uploaded
        if (empty($file['tmp_name'])) {
            if ($options['required']) {
                return new WP_Error(
                    'file_required',
                    __('File upload is required', 'ma-deal-room'),
                    ['status' => 400]
                );
            }
            return true;
        }

        // Check for upload errors
        if ($file['error'] !== UPLOAD_ERR_OK) {
            return new WP_Error(
                'upload_error',
                $this->get_upload_error_message($file['error']),
                ['status' => 400]
            );
        }

        // Check file size
        if ($file['size'] > $options['max_size']) {
            return new WP_Error(
                'file_too_large',
                sprintf(
                    __('File size must not exceed %s', 'ma-deal-room'),
                    size_format($options['max_size'])
                ),
                ['status' => 400]
            );
        }

        // Check file type
        $file_ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if (!in_array($file_ext, $options['allowed_types'])) {
            return new WP_Error(
                'invalid_file_type',
                sprintf(
                    __('File type must be one of: %s', 'ma-deal-room'),
                    implode(', ', $options['allowed_types'])
                ),
                ['status' => 400]
            );
        }

        // Verify MIME type matches extension
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime_type = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);

        $allowed_mimes = [
            'jpg' => 'image/jpeg',
            'jpeg' => 'image/jpeg',
            'png' => 'image/png',
            'pdf' => 'application/pdf',
            'doc' => 'application/msword',
            'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        ];

        $expected_mime = $allowed_mimes[$file_ext] ?? null;
        if ($expected_mime && $mime_type !== $expected_mime) {
            return new WP_Error(
                'mime_type_mismatch',
                __('File type does not match file extension', 'ma-deal-room'),
                ['status' => 400]
            );
        }

        return true;
    }

    /**
     * Sanitize and validate array of data
     *
     * @param array $data Data to validate
     * @param array $rules Validation rules
     * @return array|WP_Error Sanitized data or error
     */
    public function validate_data(array $data, array $rules) {
        $sanitized = [];
        $errors = [];

        foreach ($rules as $field => $rule) {
            $value = $data[$field] ?? null;
            $field_rules = is_array($rule) ? $rule : ['type' => $rule];
            $required = $field_rules['required'] ?? true;

            // Check required
            if ($required && ($value === null || $value === '')) {
                $errors[] = sprintf(__('Field %s is required', 'ma-deal-room'), $field);
                continue;
            }

            // Skip validation if not required and empty
            if (!$required && ($value === null || $value === '')) {
                $sanitized[$field] = null;
                continue;
            }

            // Validate by type
            $type = $field_rules['type'] ?? 'text';
            $validation_result = $this->validate_field_by_type($value, $type, $field_rules);

            if (is_wp_error($validation_result)) {
                $errors[] = sprintf(__('%s: %s', 'ma-deal-room'), $field, $validation_result->get_error_message());
            } else {
                $sanitized[$field] = $validation_result;
            }
        }

        if (!empty($errors)) {
            return new WP_Error(
                'validation_failed',
                implode('; ', $errors),
                ['status' => 400, 'errors' => $errors]
            );
        }

        return $sanitized;
    }

    /**
     * Validate field by type
     *
     * @param mixed $value Value
     * @param string $type Type
     * @param array $options Options
     * @return mixed|WP_Error Sanitized value or error
     */
    private function validate_field_by_type($value, string $type, array $options = []) {
        switch ($type) {
            case 'email':
                $result = $this->validate_email($value);
                return is_wp_error($result) ? $result : sanitize_email($value);

            case 'phone':
                $result = $this->validate_phone($value, $options['country'] ?? 'US');
                return is_wp_error($result) ? $result : sanitize_text_field($value);

            case 'url':
                $result = $this->validate_url($value, $options['schemes'] ?? ['http', 'https']);
                return is_wp_error($result) ? $result : esc_url_raw($value);

            case 'int':
            case 'integer':
                return is_numeric($value) ? (int) $value : new WP_Error('invalid_int', __('Must be an integer', 'ma-deal-room'));

            case 'float':
            case 'decimal':
                return is_numeric($value) ? (float) $value : new WP_Error('invalid_float', __('Must be a number', 'ma-deal-room'));

            case 'bool':
            case 'boolean':
                return filter_var($value, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);

            case 'date':
                $timestamp = strtotime($value);
                return $timestamp !== false ? date('Y-m-d', $timestamp) : new WP_Error('invalid_date', __('Invalid date format', 'ma-deal-room'));

            case 'datetime':
                $timestamp = strtotime($value);
                return $timestamp !== false ? date('Y-m-d H:i:s', $timestamp) : new WP_Error('invalid_datetime', __('Invalid datetime format', 'ma-deal-room'));

            case 'text':
            default:
                return sanitize_text_field($value);
        }
    }

    /**
     * Check if password is commonly used
     *
     * @param string $password Password
     * @return bool
     */
    private function is_common_password(string $password): bool {
        // Common passwords list (top 50 most common)
        $common = [
            '123456', 'password', '12345678', 'qwerty', '123456789',
            '12345', '1234', '111111', '1234567', 'dragon',
            '123123', 'baseball', 'iloveyou', 'trustno1', '1234567890',
            'sunshine', 'master', '123321', '666666', 'photoshop',
            '1111111', '2000', 'princess', 'azerty', 'pussy',
            'admin', 'password1', 'welcome', 'abc123', 'football',
            'monkey', '!@#$%^&*', 'charlie', 'aa123456', 'donald',
            'qwerty123', 'zxcvbnm', '121212', 'bailey', 'freedom',
            'shadow', 'passw0rd', 'baseball', 'michael', 'superman',
            'password123', 'qwertyuiop', 'letmein', 'starwars',
        ];

        return in_array(strtolower($password), $common);
    }

    /**
     * Get disposable email domains
     *
     * @return array
     */
    private function get_disposable_email_domains(): array {
        // Sample list - in production, use a maintained list
        return [
            'tempmail.com', 'throwaway.email', '10minutemail.com',
            'guerrillamail.com', 'mailinator.com', 'maildrop.cc',
        ];
    }

    /**
     * Get upload error message
     *
     * @param int $error_code Error code
     * @return string Error message
     */
    private function get_upload_error_message(int $error_code): string {
        switch ($error_code) {
            case UPLOAD_ERR_INI_SIZE:
            case UPLOAD_ERR_FORM_SIZE:
                return __('File is too large', 'ma-deal-room');
            case UPLOAD_ERR_PARTIAL:
                return __('File was only partially uploaded', 'ma-deal-room');
            case UPLOAD_ERR_NO_FILE:
                return __('No file was uploaded', 'ma-deal-room');
            case UPLOAD_ERR_NO_TMP_DIR:
                return __('Missing temporary folder', 'ma-deal-room');
            case UPLOAD_ERR_CANT_WRITE:
                return __('Failed to write file to disk', 'ma-deal-room');
            case UPLOAD_ERR_EXTENSION:
                return __('File upload stopped by extension', 'ma-deal-room');
            default:
                return __('Unknown upload error', 'ma-deal-room');
        }
    }
}
