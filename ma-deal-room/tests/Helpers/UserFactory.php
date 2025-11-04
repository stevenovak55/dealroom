<?php
/**
 * User Factory
 *
 * Helper class for creating test users.
 *
 * @package MADealRoom\Tests\Helpers
 */

namespace MADealRoom\Tests\Helpers;

/**
 * User Factory Class
 */
class UserFactory
{
    /**
     * Create a test user
     *
     * @param array $attributes User attributes
     * @return array User data
     */
    public static function create(array $attributes = []): array
    {
        $defaults = [
            'account_id' => self::randomUuid(),
            'email' => self::randomEmail(),
            'password_hash' => password_hash('Password123!', PASSWORD_BCRYPT),
            'first_name' => 'Test',
            'last_name' => 'User',
            'phone' => '+1234567890',
            'is_email_verified' => 1,
            'email_verified_at' => self::now(),
            'failed_login_attempts' => 0,
            'locked_until' => null,
            'last_login_at' => null,
            'last_login_ip' => null,
            'preferences' => json_encode([]),
            'created_at' => self::now(),
            'updated_at' => self::now(),
        ];

        return array_merge($defaults, $attributes);
    }

    /**
     * Create multiple test users
     *
     * @param int $count Number of users to create
     * @param array $attributes Common attributes for all users
     * @return array<array> Array of user data
     */
    public static function createMany(int $count, array $attributes = []): array
    {
        $users = [];
        for ($i = 0; $i < $count; $i++) {
            $users[] = self::create($attributes);
        }
        return $users;
    }

    /**
     * Create an admin user
     *
     * @param array $attributes User attributes
     * @return array User data
     */
    public static function createAdmin(array $attributes = []): array
    {
        return self::create(array_merge([
            'email' => 'admin@test.com',
            'first_name' => 'Admin',
            'last_name' => 'User',
        ], $attributes));
    }

    /**
     * Create an unverified user
     *
     * @param array $attributes User attributes
     * @return array User data
     */
    public static function createUnverified(array $attributes = []): array
    {
        return self::create(array_merge([
            'is_email_verified' => 0,
            'email_verified_at' => null,
        ], $attributes));
    }

    /**
     * Create a locked user
     *
     * @param array $attributes User attributes
     * @return array User data
     */
    public static function createLocked(array $attributes = []): array
    {
        return self::create(array_merge([
            'failed_login_attempts' => 5,
            'locked_until' => date('Y-m-d H:i:s', strtotime('+1 hour')),
        ], $attributes));
    }

    /**
     * Create a user with 2FA enabled
     *
     * @param array $attributes User attributes
     * @return array User data with 2FA secret
     */
    public static function createWith2FA(array $attributes = []): array
    {
        $user = self::create($attributes);
        $user['2fa_secret'] = base64_encode(random_bytes(20));
        $user['2fa_enabled_at'] = self::now();
        return $user;
    }

    /**
     * Generate a random UUID
     *
     * @return string UUID
     */
    private static function randomUuid(): string
    {
        return sprintf(
            '%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
            mt_rand(0, 0xffff),
            mt_rand(0, 0xffff),
            mt_rand(0, 0xffff),
            mt_rand(0, 0x0fff) | 0x4000,
            mt_rand(0, 0x3fff) | 0x8000,
            mt_rand(0, 0xffff),
            mt_rand(0, 0xffff),
            mt_rand(0, 0xffff)
        );
    }

    /**
     * Generate a random email
     *
     * @return string Email
     */
    private static function randomEmail(): string
    {
        return 'user' . mt_rand(10000, 99999) . '@test.com';
    }

    /**
     * Get current timestamp
     *
     * @return string Timestamp
     */
    private static function now(): string
    {
        return date('Y-m-d H:i:s');
    }

    /**
     * Create a password reset token
     *
     * @param string $userId User ID
     * @return array Reset token data
     */
    public static function createPasswordResetToken(string $userId): array
    {
        return [
            'user_id' => $userId,
            'token' => bin2hex(random_bytes(32)),
            'expires_at' => date('Y-m-d H:i:s', strtotime('+1 hour')),
            'created_at' => self::now(),
        ];
    }

    /**
     * Create an email verification token
     *
     * @param string $userId User ID
     * @param string $email Email address
     * @return array Verification token data
     */
    public static function createEmailVerificationToken(string $userId, string $email): array
    {
        return [
            'user_id' => $userId,
            'email' => $email,
            'token' => bin2hex(random_bytes(32)),
            'expires_at' => date('Y-m-d H:i:s', strtotime('+24 hours')),
            'created_at' => self::now(),
        ];
    }

    /**
     * Create a user session
     *
     * @param string $userId User ID
     * @param string $refreshToken Refresh token
     * @return array Session data
     */
    public static function createSession(string $userId, string $refreshToken): array
    {
        return [
            'user_id' => $userId,
            'refresh_token_hash' => hash('sha256', $refreshToken),
            'device_info' => 'Test Browser',
            'ip_address' => '127.0.0.1',
            'expires_at' => date('Y-m-d H:i:s', strtotime('+7 days')),
            'created_at' => self::now(),
            'last_used_at' => self::now(),
        ];
    }
}
