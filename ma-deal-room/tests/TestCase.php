<?php
/**
 * Base Test Case
 *
 * Provides common testing utilities and setup/teardown for all tests.
 *
 * @package MADealRoom\Tests
 */

namespace MADealRoom\Tests;

use PHPUnit\Framework\TestCase as PHPUnitTestCase;

/**
 * Base TestCase Class
 *
 * All test classes should extend this class to inherit common functionality.
 */
abstract class TestCase extends PHPUnitTestCase
{
    /**
     * Set up before each test
     *
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();

        // Clear any cached data
        $this->clearCache();

        // Reset database transaction if available
        if ($this->usesDatabase()) {
            $this->beginDatabaseTransaction();
        }
    }

    /**
     * Tear down after each test
     *
     * @return void
     */
    protected function tearDown(): void
    {
        // Rollback database transaction if available
        if ($this->usesDatabase()) {
            $this->rollbackDatabaseTransaction();
        }

        // Clear cache
        $this->clearCache();

        parent::tearDown();
    }

    /**
     * Check if this test uses database
     *
     * Override in subclasses that need database access
     *
     * @return bool
     */
    protected function usesDatabase(): bool
    {
        return false;
    }

    /**
     * Begin database transaction
     *
     * @return void
     */
    protected function beginDatabaseTransaction(): void
    {
        global $wpdb;
        if (isset($wpdb) && method_exists($wpdb, 'query')) {
            $wpdb->query('START TRANSACTION');
        }
    }

    /**
     * Rollback database transaction
     *
     * @return void
     */
    protected function rollbackDatabaseTransaction(): void
    {
        global $wpdb;
        if (isset($wpdb) && method_exists($wpdb, 'query')) {
            $wpdb->query('ROLLBACK');
        }
    }

    /**
     * Clear all caches
     *
     * @return void
     */
    protected function clearCache(): void
    {
        if (function_exists('wp_cache_flush')) {
            wp_cache_flush();
        }
    }

    /**
     * Assert that an array has specific keys
     *
     * @param array<string> $keys Expected keys
     * @param array $array Array to check
     * @param string $message Optional message
     * @return void
     */
    protected function assertArrayHasKeys(array $keys, array $array, string $message = ''): void
    {
        foreach ($keys as $key) {
            $this->assertArrayHasKey($key, $array, $message ?: "Array should have key: {$key}");
        }
    }

    /**
     * Assert that a value is a valid UUID
     *
     * @param mixed $value Value to check
     * @param string $message Optional message
     * @return void
     */
    protected function assertIsUuid($value, string $message = ''): void
    {
        $pattern = '/^[0-9a-f]{8}-[0-9a-f]{4}-4[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/i';
        $this->assertMatchesRegularExpression(
            $pattern,
            $value,
            $message ?: "Value should be a valid UUID v4"
        );
    }

    /**
     * Assert that a value is a valid date string
     *
     * @param mixed $value Value to check
     * @param string $message Optional message
     * @return void
     */
    protected function assertIsValidDate($value, string $message = ''): void
    {
        $this->assertIsString($value, $message ?: "Date should be a string");
        $timestamp = strtotime($value);
        $this->assertNotFalse($timestamp, $message ?: "Value should be a valid date: {$value}");
    }

    /**
     * Assert that a value is a valid email
     *
     * @param mixed $value Value to check
     * @param string $message Optional message
     * @return void
     */
    protected function assertIsValidEmail($value, string $message = ''): void
    {
        $this->assertIsString($value, $message ?: "Email should be a string");
        $this->assertMatchesRegularExpression(
            '/^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/',
            $value,
            $message ?: "Value should be a valid email: {$value}"
        );
    }

    /**
     * Assert that an object is a WP_Error
     *
     * @param mixed $value Value to check
     * @param string $message Optional message
     * @return void
     */
    protected function assertIsWPError($value, string $message = ''): void
    {
        $this->assertInstanceOf(
            \WP_Error::class,
            $value,
            $message ?: "Value should be a WP_Error instance"
        );
    }

    /**
     * Assert that an object is not a WP_Error
     *
     * @param mixed $value Value to check
     * @param string $message Optional message
     * @return void
     */
    protected function assertIsNotWPError($value, string $message = ''): void
    {
        $this->assertNotInstanceOf(
            \WP_Error::class,
            $value,
            $message ?: "Value should not be a WP_Error instance"
        );
    }

    /**
     * Create a mock request object
     *
     * @param array $params Request parameters
     * @param string $method HTTP method
     * @return \WP_REST_Request|object
     */
    protected function createMockRequest(array $params = [], string $method = 'GET')
    {
        if (class_exists('\WP_REST_Request')) {
            $request = new \WP_REST_Request($method, '/test');
            $request->set_body_params($params);
            return $request;
        }

        // Mock for unit tests without WordPress
        return (object) [
            'method' => $method,
            'params' => $params,
        ];
    }

    /**
     * Get a private or protected property value
     *
     * @param object $object Object instance
     * @param string $propertyName Property name
     * @return mixed Property value
     * @throws \ReflectionException
     */
    protected function getPrivateProperty(object $object, string $propertyName)
    {
        $reflection = new \ReflectionClass($object);
        $property = $reflection->getProperty($propertyName);
        $property->setAccessible(true);
        return $property->getValue($object);
    }

    /**
     * Set a private or protected property value
     *
     * @param object $object Object instance
     * @param string $propertyName Property name
     * @param mixed $value Value to set
     * @return void
     * @throws \ReflectionException
     */
    protected function setPrivateProperty(object $object, string $propertyName, $value): void
    {
        $reflection = new \ReflectionClass($object);
        $property = $reflection->getProperty($propertyName);
        $property->setAccessible(true);
        $property->setValue($object, $value);
    }

    /**
     * Call a private or protected method
     *
     * @param object $object Object instance
     * @param string $methodName Method name
     * @param array $args Method arguments
     * @return mixed Method return value
     * @throws \ReflectionException
     */
    protected function callPrivateMethod(object $object, string $methodName, array $args = [])
    {
        $reflection = new \ReflectionClass($object);
        $method = $reflection->getMethod($methodName);
        $method->setAccessible(true);
        return $method->invokeArgs($object, $args);
    }

    /**
     * Generate a random string
     *
     * @param int $length String length
     * @return string Random string
     */
    protected function randomString(int $length = 10): string
    {
        $characters = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
        $string = '';
        for ($i = 0; $i < $length; $i++) {
            $string .= $characters[rand(0, strlen($characters) - 1)];
        }
        return $string;
    }

    /**
     * Generate a random email
     *
     * @return string Random email
     */
    protected function randomEmail(): string
    {
        return strtolower($this->randomString(8)) . '@test.com';
    }

    /**
     * Generate a random UUID
     *
     * @return string Random UUID
     */
    protected function randomUuid(): string
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
     * Create a temporary file
     *
     * @param string $content File content
     * @param string $extension File extension
     * @return string File path
     */
    protected function createTempFile(string $content, string $extension = 'txt'): string
    {
        $tempFile = tempnam(sys_get_temp_dir(), 'test_') . '.' . $extension;
        file_put_contents($tempFile, $content);
        return $tempFile;
    }

    /**
     * Clean up temporary files
     *
     * @param array<string> $files File paths
     * @return void
     */
    protected function cleanupTempFiles(array $files): void
    {
        foreach ($files as $file) {
            if (file_exists($file)) {
                unlink($file);
            }
        }
    }
}
