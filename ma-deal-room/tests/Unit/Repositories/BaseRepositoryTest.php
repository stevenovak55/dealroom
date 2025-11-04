<?php
/**
 * BaseRepository Unit Tests
 *
 * Tests for the base repository class with mocked database operations.
 *
 * @package MADealRoom\Tests\Unit\Repositories
 */

namespace MADealRoom\Tests\Unit\Repositories;

use MADealRoom\Tests\TestCase;
use MADealRoom\Repositories\BaseRepository;

/**
 * Concrete implementation of BaseRepository for testing
 */
class TestRepository extends BaseRepository
{
    protected $table = 'test_table';
    protected $primary_key = 'id';
    protected $allowed_columns = ['id', 'name', 'email', 'status', 'created_at', 'updated_at'];
}

/**
 * BaseRepository Test Class
 */
class BaseRepositoryTest extends TestCase
{
    private $repository;
    private $wpdb_mock;

    protected function setUp(): void
    {
        parent::setUp();

        // Create mock wpdb
        $this->wpdb_mock = $this->getMockBuilder('stdClass')
            ->addMethods(['get_row', 'get_results', 'get_var', 'insert', 'update', 'delete', 'prepare'])
            ->getMock();

        $this->wpdb_mock->prefix = 'wp_';
        $this->wpdb_mock->last_error = '';
        $this->wpdb_mock->insert_id = 0;

        // Set global $wpdb
        global $wpdb;
        $wpdb = $this->wpdb_mock;

        // Create repository instance
        $this->repository = new TestRepository();
    }

    // ========================================
    // get_table_name() Tests
    // ========================================

    public function testGetTableName()
    {
        $reflection = new \ReflectionClass($this->repository);
        $method = $reflection->getMethod('get_table_name');
        $method->setAccessible(true);

        $tableName = $method->invoke($this->repository);

        $this->assertEquals('wp_test_table', $tableName);
    }

    // ========================================
    // validate_column() Tests
    // ========================================

    public function testValidateColumnWithValidColumn()
    {
        $reflection = new \ReflectionClass($this->repository);
        $method = $reflection->getMethod('validate_column');
        $method->setAccessible(true);

        $result = $method->invoke($this->repository, 'name');

        $this->assertEquals('name', $result);
    }

    public function testValidateColumnWithInvalidColumn()
    {
        $reflection = new \ReflectionClass($this->repository);
        $method = $reflection->getMethod('validate_column');
        $method->setAccessible(true);

        $result = $method->invoke($this->repository, 'malicious_column');

        $this->assertNull($result);
    }

    public function testValidateColumnTrimsWhitespace()
    {
        $reflection = new \ReflectionClass($this->repository);
        $method = $reflection->getMethod('validate_column');
        $method->setAccessible(true);

        $result = $method->invoke($this->repository, '  name  ');

        $this->assertEquals('name', $result);
    }

    public function testValidateColumnSQLInjectionAttempt()
    {
        $reflection = new \ReflectionClass($this->repository);
        $method = $reflection->getMethod('validate_column');
        $method->setAccessible(true);

        $malicious = "name; DROP TABLE users--";
        $result = $method->invoke($this->repository, $malicious);

        $this->assertNull($result);
    }

    // ========================================
    // find() Tests
    // ========================================

    public function testFindWithExistingRecord()
    {
        $expected_data = ['id' => 1, 'name' => 'Test User', 'email' => 'test@example.com'];

        $this->wpdb_mock->expects($this->once())
            ->method('prepare')
            ->with(
                $this->stringContains('SELECT * FROM wp_test_table WHERE id = %d'),
                1
            )
            ->willReturn('SELECT * FROM wp_test_table WHERE id = 1');

        $this->wpdb_mock->expects($this->once())
            ->method('get_row')
            ->willReturn($expected_data);

        $result = $this->repository->find(1);

        $this->assertIsObject($result);
        $this->assertEquals(1, $result->id);
        $this->assertEquals('Test User', $result->name);
    }

    public function testFindWithNonExistentRecord()
    {
        $this->wpdb_mock->expects($this->once())
            ->method('prepare')
            ->willReturn('SELECT * FROM wp_test_table WHERE id = 999');

        $this->wpdb_mock->expects($this->once())
            ->method('get_row')
            ->willReturn(null);

        $result = $this->repository->find(999);

        $this->assertNull($result);
    }

    // ========================================
    // findAll() Tests
    // ========================================

    public function testFindAllReturnsArray()
    {
        $expected_data = [
            ['id' => 1, 'name' => 'User 1'],
            ['id' => 2, 'name' => 'User 2'],
        ];

        $this->wpdb_mock->expects($this->once())
            ->method('prepare')
            ->willReturn('SELECT * FROM wp_test_table ORDER BY id DESC LIMIT 100 OFFSET 0');

        $this->wpdb_mock->expects($this->once())
            ->method('get_results')
            ->willReturn($expected_data);

        $results = $this->repository->findAll();

        $this->assertIsArray($results);
        $this->assertCount(2, $results);
        $this->assertEquals(1, $results[0]->id);
        $this->assertEquals(2, $results[1]->id);
    }

    public function testFindAllWithLimitAndOffset()
    {
        $this->wpdb_mock->expects($this->once())
            ->method('prepare')
            ->with(
                $this->anything(),
                10,
                20
            )
            ->willReturn('SELECT * FROM wp_test_table ORDER BY id DESC LIMIT 10 OFFSET 20');

        $this->wpdb_mock->expects($this->once())
            ->method('get_results')
            ->willReturn([]);

        $results = $this->repository->findAll(10, 20);

        $this->assertIsArray($results);
    }

    // ========================================
    // create() Tests
    // ========================================

    public function testCreateInsertsRecord()
    {
        $data = ['name' => 'New User', 'email' => 'new@example.com'];

        $this->wpdb_mock->expects($this->once())
            ->method('insert')
            ->with('wp_test_table', $data)
            ->willReturn(1);

        $this->wpdb_mock->insert_id = 123;

        $id = $this->repository->create($data);

        $this->assertEquals(123, $id);
    }

    public function testCreateReturnsFalseOnFailure()
    {
        $data = ['name' => 'New User'];

        $this->wpdb_mock->expects($this->once())
            ->method('insert')
            ->willReturn(false);

        $result = $this->repository->create($data);

        $this->assertFalse($result);
    }

    // ========================================
    // update() Tests
    // ========================================

    public function testUpdateModifiesRecord()
    {
        $data = ['name' => 'Updated Name'];

        $this->wpdb_mock->expects($this->once())
            ->method('update')
            ->with('wp_test_table', $data, ['id' => 1])
            ->willReturn(1);

        $result = $this->repository->update(1, $data);

        $this->assertTrue($result);
    }

    public function testUpdateReturnsFalseOnFailure()
    {
        $data = ['name' => 'Updated Name'];

        $this->wpdb_mock->expects($this->once())
            ->method('update')
            ->willReturn(false);

        $result = $this->repository->update(1, $data);

        $this->assertFalse($result);
    }

    // ========================================
    // delete() Tests
    // ========================================

    public function testDeleteRemovesRecord()
    {
        $this->wpdb_mock->expects($this->once())
            ->method('delete')
            ->with('wp_test_table', ['id' => 1])
            ->willReturn(1);

        $result = $this->repository->delete(1);

        $this->assertTrue($result);
    }

    public function testDeleteReturnsFalseOnFailure()
    {
        $this->wpdb_mock->expects($this->once())
            ->method('delete')
            ->willReturn(false);

        $result = $this->repository->delete(1);

        $this->assertFalse($result);
    }

    // ========================================
    // query() Tests
    // ========================================

    public function testQueryWithNoConditions()
    {
        $this->wpdb_mock->expects($this->once())
            ->method('prepare')
            ->willReturn('SELECT * FROM wp_test_table');

        $this->wpdb_mock->expects($this->once())
            ->method('get_results')
            ->willReturn([]);

        $results = $this->repository->query();

        $this->assertIsArray($results);
    }

    public function testQueryWithSingleCondition()
    {
        $this->wpdb_mock->expects($this->once())
            ->method('prepare')
            ->willReturn('SELECT * FROM wp_test_table WHERE status = \'active\'');

        $this->wpdb_mock->expects($this->once())
            ->method('get_results')
            ->willReturn([]);

        $results = $this->repository->query(['status' => 'active']);

        $this->assertIsArray($results);
    }

    public function testQueryWithMultipleConditions()
    {
        $this->wpdb_mock->expects($this->once())
            ->method('prepare')
            ->willReturn('SELECT * FROM wp_test_table WHERE status = \'active\' AND name = \'Test\'');

        $this->wpdb_mock->expects($this->once())
            ->method('get_results')
            ->willReturn([]);

        $results = $this->repository->query(['status' => 'active', 'name' => 'Test']);

        $this->assertIsArray($results);
    }

    public function testQueryWithInCondition()
    {
        $this->wpdb_mock->expects($this->once())
            ->method('prepare')
            ->willReturn('SELECT * FROM wp_test_table WHERE status IN (\'active\', \'pending\')');

        $this->wpdb_mock->expects($this->once())
            ->method('get_results')
            ->willReturn([]);

        $results = $this->repository->query(['status' => ['active', 'pending']]);

        $this->assertIsArray($results);
    }

    public function testQueryWithNullCondition()
    {
        $this->wpdb_mock->expects($this->once())
            ->method('prepare')
            ->willReturn('SELECT * FROM wp_test_table WHERE email IS NULL');

        $this->wpdb_mock->expects($this->once())
            ->method('get_results')
            ->willReturn([]);

        $results = $this->repository->query(['email' => null]);

        $this->assertIsArray($results);
    }

    public function testQueryWithOrderBy()
    {
        $this->wpdb_mock->expects($this->once())
            ->method('prepare')
            ->willReturn('SELECT * FROM wp_test_table ORDER BY name ASC');

        $this->wpdb_mock->expects($this->once())
            ->method('get_results')
            ->willReturn([]);

        $results = $this->repository->query([], ['order_by' => 'name', 'order' => 'ASC']);

        $this->assertIsArray($results);
    }

    public function testQueryWithLimitAndOffset()
    {
        $this->wpdb_mock->expects($this->once())
            ->method('prepare')
            ->willReturn('SELECT * FROM wp_test_table LIMIT 10 OFFSET 5');

        $this->wpdb_mock->expects($this->once())
            ->method('get_results')
            ->willReturn([]);

        $results = $this->repository->query([], ['limit' => 10, 'offset' => 5]);

        $this->assertIsArray($results);
    }

    public function testQueryIgnoresInvalidColumns()
    {
        // Query with invalid column should be ignored
        $this->wpdb_mock->expects($this->once())
            ->method('prepare')
            ->willReturn('SELECT * FROM wp_test_table');

        $this->wpdb_mock->expects($this->once())
            ->method('get_results')
            ->willReturn([]);

        $results = $this->repository->query(['invalid_column' => 'value']);

        $this->assertIsArray($results);
    }

    // ========================================
    // count() Tests
    // ========================================

    public function testCountWithNoConditions()
    {
        $this->wpdb_mock->expects($this->once())
            ->method('prepare')
            ->willReturn('SELECT COUNT(*) FROM wp_test_table');

        $this->wpdb_mock->expects($this->once())
            ->method('get_var')
            ->willReturn('42');

        $count = $this->repository->count();

        $this->assertEquals(42, $count);
    }

    public function testCountWithConditions()
    {
        $this->wpdb_mock->expects($this->once())
            ->method('prepare')
            ->willReturn('SELECT COUNT(*) FROM wp_test_table WHERE status = \'active\'');

        $this->wpdb_mock->expects($this->once())
            ->method('get_var')
            ->willReturn('10');

        $count = $this->repository->count(['status' => 'active']);

        $this->assertEquals(10, $count);
    }

    // ========================================
    // build_where_clause() Tests
    // ========================================

    public function testBuildWhereClauseEmpty()
    {
        $reflection = new \ReflectionClass($this->repository);
        $method = $reflection->getMethod('build_where_clause');
        $method->setAccessible(true);

        list($clause, $values) = $method->invoke($this->repository, []);

        $this->assertEquals('', $clause);
        $this->assertEmpty($values);
    }

    public function testBuildWhereClauseSingleCondition()
    {
        $reflection = new \ReflectionClass($this->repository);
        $method = $reflection->getMethod('build_where_clause');
        $method->setAccessible(true);

        list($clause, $values) = $method->invoke($this->repository, ['status' => 'active']);

        $this->assertEquals('WHERE status = %s', $clause);
        $this->assertEquals(['active'], $values);
    }

    public function testBuildWhereClauseMultipleConditions()
    {
        $reflection = new \ReflectionClass($this->repository);
        $method = $reflection->getMethod('build_where_clause');
        $method->setAccessible(true);

        list($clause, $values) = $method->invoke($this->repository, [
            'status' => 'active',
            'name' => 'Test'
        ]);

        $this->assertStringContainsString('WHERE', $clause);
        $this->assertStringContainsString('status = %s', $clause);
        $this->assertStringContainsString('name = %s', $clause);
        $this->assertStringContainsString('AND', $clause);
        $this->assertEquals(['active', 'Test'], $values);
    }

    // ========================================
    // build_order_clause() Tests
    // ========================================

    public function testBuildOrderClauseNoOption()
    {
        $reflection = new \ReflectionClass($this->repository);
        $method = $reflection->getMethod('build_order_clause');
        $method->setAccessible(true);

        $clause = $method->invoke($this->repository, []);

        $this->assertEquals('', $clause);
    }

    public function testBuildOrderClauseAscending()
    {
        $reflection = new \ReflectionClass($this->repository);
        $method = $reflection->getMethod('build_order_clause');
        $method->setAccessible(true);

        $clause = $method->invoke($this->repository, ['order_by' => 'name', 'order' => 'ASC']);

        $this->assertEquals('ORDER BY name ASC', $clause);
    }

    public function testBuildOrderClauseDescending()
    {
        $reflection = new \ReflectionClass($this->repository);
        $method = $reflection->getMethod('build_order_clause');
        $method->setAccessible(true);

        $clause = $method->invoke($this->repository, ['order_by' => 'created_at', 'order' => 'DESC']);

        $this->assertEquals('ORDER BY created_at DESC', $clause);
    }

    public function testBuildOrderClauseDefaultsToDesc()
    {
        $reflection = new \ReflectionClass($this->repository);
        $method = $reflection->getMethod('build_order_clause');
        $method->setAccessible(true);

        $clause = $method->invoke($this->repository, ['order_by' => 'id']);

        $this->assertEquals('ORDER BY id DESC', $clause);
    }

    public function testBuildOrderClauseInvalidColumn()
    {
        $reflection = new \ReflectionClass($this->repository);
        $method = $reflection->getMethod('build_order_clause');
        $method->setAccessible(true);

        // Should fall back to primary key
        $clause = $method->invoke($this->repository, ['order_by' => 'invalid_column']);

        $this->assertEquals('ORDER BY id DESC', $clause);
    }

    // ========================================
    // get_last_error() Tests
    // ========================================

    public function testGetLastError()
    {
        $this->wpdb_mock->last_error = 'Test error message';

        $error = $this->repository->get_last_error();

        $this->assertEquals('Test error message', $error);
    }
}
