<?php
/**
 * Example Test
 *
 * Simple test to verify PHPUnit configuration.
 *
 * @package MADealRoom\Tests\Unit
 */

namespace MADealRoom\Tests\Unit;

use MADealRoom\Tests\TestCase;
use MADealRoom\Tests\Helpers\UserFactory;
use MADealRoom\Tests\Helpers\TransactionFactory;
use MADealRoom\Tests\Helpers\TaskFactory;

/**
 * Example Test Class
 */
class ExampleTest extends TestCase
{
    /**
     * Test that true is true
     *
     * @return void
     */
    public function testTrueIsTrue()
    {
        $this->assertTrue(true);
    }

    /**
     * Test that basic math works
     *
     * @return void
     */
    public function testBasicMath()
    {
        $this->assertEquals(4, 2 + 2);
        $this->assertNotEquals(5, 2 + 2);
    }

    /**
     * Test UserFactory creates valid user data
     *
     * @return void
     */
    public function testUserFactoryCreatesValidData()
    {
        $user = UserFactory::create();

        $this->assertIsArray($user);
        $this->assertArrayHasKeys([
            'account_id',
            'email',
            'first_name',
            'last_name',
            'created_at'
        ], $user);
        $this->assertIsValidEmail($user['email']);
        $this->assertIsUuid($user['account_id']);
    }

    /**
     * Test TransactionFactory creates valid transaction data
     *
     * @return void
     */
    public function testTransactionFactoryCreatesValidData()
    {
        $transaction = TransactionFactory::create();

        $this->assertIsArray($transaction);
        $this->assertArrayHasKeys([
            'transaction_id',
            'property_address',
            'property_type',
            'transaction_side',
            'created_at'
        ], $transaction);
        $this->assertIsUuid($transaction['transaction_id']);
    }

    /**
     * Test TaskFactory creates valid task data
     *
     * @return void
     */
    public function testTaskFactoryCreatesValidData()
    {
        $task = TaskFactory::create();

        $this->assertIsArray($task);
        $this->assertArrayHasKeys([
            'task_id',
            'transaction_id',
            'title',
            'status',
            'created_at'
        ], $task);
        $this->assertIsUuid($task['task_id']);
        $this->assertEquals('Test Task', $task['title']);
    }

    /**
     * Test helper methods
     *
     * @return void
     */
    public function testHelperMethods()
    {
        $randomString = $this->randomString(10);
        $this->assertIsString($randomString);
        $this->assertEquals(10, strlen($randomString));

        $randomEmail = $this->randomEmail();
        $this->assertIsValidEmail($randomEmail);

        $randomUuid = $this->randomUuid();
        $this->assertIsUuid($randomUuid);
    }

    /**
     * Test custom assertions
     *
     * @return void
     */
    public function testCustomAssertions()
    {
        // Test UUID assertion (v4)
        $uuid = '550e8400-e29b-41d4-a716-446655440000';
        $this->assertIsUuid($uuid);

        // Test date assertion
        $date = '2025-01-15 10:30:00';
        $this->assertIsValidDate($date);

        // Test email assertion
        $email = 'test@example.com';
        $this->assertIsValidEmail($email);
    }
}
