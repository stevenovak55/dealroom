<?php
/**
 * TemplateRepository Unit Tests
 *
 * Tests for the template repository with mocked database operations.
 *
 * @package MADealRoom\Tests\Unit\Repositories
 */

namespace MADealRoom\Tests\Unit\Repositories;

use MADealRoom\Tests\TestCase;
use MADealRoom\Repositories\TemplateRepository;

/**
 * TemplateRepository Test Class
 */
class TemplateRepositoryTest extends TestCase
{
    private $repository;
    private $wpdb_mock;

    protected function setUp(): void
    {
        parent::setUp();

        // Create mock wpdb
        $this->wpdb_mock = $this->getMockBuilder('stdClass')
            ->addMethods(['get_row', 'get_results', 'get_var', 'prepare'])
            ->getMock();

        $this->wpdb_mock->prefix = 'wp_';

        // Set global $wpdb
        global $wpdb;
        $wpdb = $this->wpdb_mock;

        // Create repository instance
        $this->repository = new TemplateRepository();
    }

    // ========================================
    // findActive() Tests
    // ========================================

    public function testFindActiveWithoutAccountId()
    {
        $this->wpdb_mock->expects($this->once())
            ->method('get_results')
            ->with($this->stringContains('WHERE is_active = 1'))
            ->willReturn([
                ['id' => 1, 'name' => 'System Template', 'is_system' => 1],
            ]);

        $results = $this->repository->findActive();

        $this->assertIsArray($results);
        $this->assertCount(1, $results);
    }

    public function testFindActiveWithAccountId()
    {
        $this->wpdb_mock->expects($this->once())
            ->method('prepare')
            ->with(
                $this->stringContains('WHERE is_active = 1'),
                123
            )
            ->willReturn('SELECT * FROM wp_ma_deal_templates WHERE is_active = 1 AND (is_system = 1 OR account_id = 123)');

        $this->wpdb_mock->expects($this->once())
            ->method('get_results')
            ->willReturn([
                ['id' => 1, 'name' => 'System Template', 'is_system' => 1],
                ['id' => 2, 'name' => 'Account Template', 'account_id' => 123],
            ]);

        $results = $this->repository->findActive(123);

        $this->assertIsArray($results);
        $this->assertCount(2, $results);
    }

    public function testFindActiveReturnsEmptyArrayWhenNoResults()
    {
        $this->wpdb_mock->expects($this->once())
            ->method('get_results')
            ->willReturn(null);

        $results = $this->repository->findActive();

        $this->assertIsArray($results);
        $this->assertEmpty($results);
    }

    // ========================================
    // findSystem() Tests
    // ========================================

    public function testFindSystemTemplates()
    {
        $this->wpdb_mock->expects($this->once())
            ->method('prepare')
            ->willReturn('SELECT * FROM wp_ma_deal_templates WHERE is_system = 1 AND is_active = 1');

        $this->wpdb_mock->expects($this->once())
            ->method('get_results')
            ->willReturn([
                ['id' => 1, 'name' => 'System Template 1', 'is_system' => 1],
                ['id' => 2, 'name' => 'System Template 2', 'is_system' => 1],
            ]);

        $results = $this->repository->findSystem();

        $this->assertIsArray($results);
        $this->assertCount(2, $results);
    }

    // ========================================
    // findByPropertyType() Tests
    // ========================================

    public function testFindByPropertyTypeWithoutAccount()
    {
        $this->wpdb_mock->expects($this->once())
            ->method('prepare')
            ->with(
                $this->stringContains("WHERE is_active = 1"),
                'condo'
            )
            ->willReturn('SELECT * FROM wp_ma_deal_templates WHERE property_type = \'condo\' OR property_type = \'Any\'');

        $this->wpdb_mock->expects($this->once())
            ->method('get_results')
            ->willReturn([
                ['id' => 1, 'property_type' => 'condo'],
                ['id' => 2, 'property_type' => 'Any'],
            ]);

        $results = $this->repository->findByPropertyType('condo');

        $this->assertIsArray($results);
        $this->assertCount(2, $results);
    }

    public function testFindByPropertyTypeWithAccount()
    {
        // The method makes two prepare calls: one for account_filter, one for main query
        $this->wpdb_mock->expects($this->exactly(2))
            ->method('prepare')
            ->willReturnOnConsecutiveCalls(
                'AND account_id = 123',
                'SELECT * FROM wp_ma_deal_templates WHERE property_type = \'sfh\' AND account_id = 123'
            );

        $this->wpdb_mock->expects($this->once())
            ->method('get_results')
            ->willReturn([
                ['id' => 1, 'property_type' => 'sfh', 'account_id' => 123],
            ]);

        $results = $this->repository->findByPropertyType('sfh', 123);

        $this->assertIsArray($results);
        $this->assertCount(1, $results);
    }

    // ========================================
    // findByFilters() Tests
    // ========================================

    public function testFindByFiltersWithPropertyTypeOnly()
    {
        $this->wpdb_mock->expects($this->once())
            ->method('prepare')
            ->with(
                $this->stringContains('(property_type = %s OR property_type = \'Any\')'),
                'condo'
            )
            ->willReturn('SELECT * FROM wp_ma_deal_templates WHERE is_active = 1 AND property_type = \'condo\'');

        $this->wpdb_mock->expects($this->once())
            ->method('get_results')
            ->willReturn([]);

        $results = $this->repository->findByFilters(['property_type' => 'condo']);

        $this->assertIsArray($results);
    }

    public function testFindByFiltersWithTransactionSide()
    {
        $this->wpdb_mock->expects($this->once())
            ->method('prepare')
            ->with(
                $this->stringContains('(transaction_side = %s OR transaction_side = \'both\')'),
                'buyer'
            )
            ->willReturn('SELECT * FROM wp_ma_deal_templates WHERE is_active = 1 AND transaction_side = \'buyer\'');

        $this->wpdb_mock->expects($this->once())
            ->method('get_results')
            ->willReturn([]);

        $results = $this->repository->findByFilters(['transaction_side' => 'buyer']);

        $this->assertIsArray($results);
    }

    public function testFindByFiltersWithAccountId()
    {
        $this->wpdb_mock->expects($this->once())
            ->method('prepare')
            ->with(
                $this->stringContains('(is_system = 1 OR account_id = %d)'),
                123
            )
            ->willReturn('SELECT * FROM wp_ma_deal_templates WHERE is_active = 1 AND (is_system = 1 OR account_id = 123)');

        $this->wpdb_mock->expects($this->once())
            ->method('get_results')
            ->willReturn([]);

        $results = $this->repository->findByFilters(['account_id' => 123]);

        $this->assertIsArray($results);
    }

    public function testFindByFiltersWithAllFilters()
    {
        $this->wpdb_mock->expects($this->once())
            ->method('prepare')
            ->with(
                $this->anything(),
                'condo',
                'buyer',
                123
            )
            ->willReturn('SELECT * FROM wp_ma_deal_templates WHERE all filters applied');

        $this->wpdb_mock->expects($this->once())
            ->method('get_results')
            ->willReturn([
                ['id' => 1, 'property_type' => 'condo', 'transaction_side' => 'buyer'],
            ]);

        $results = $this->repository->findByFilters([
            'property_type' => 'condo',
            'transaction_side' => 'buyer',
            'account_id' => 123
        ]);

        $this->assertIsArray($results);
        $this->assertCount(1, $results);
    }

    public function testFindByFiltersWithNoAccountShowsSystemOnly()
    {
        // When no account_id provided, the query should include "is_system = 1"
        $this->wpdb_mock->expects($this->once())
            ->method('prepare')
            ->with($this->anything(), 'condo')
            ->willReturn('SELECT * FROM wp_ma_deal_templates WHERE is_active = 1 AND is_system = 1');

        $this->wpdb_mock->expects($this->once())
            ->method('get_results')
            ->willReturn([]);

        $results = $this->repository->findByFilters(['property_type' => 'condo']);

        $this->assertIsArray($results);
    }

    public function testFindByFiltersWithEmptyFilters()
    {
        $this->wpdb_mock->expects($this->once())
            ->method('get_results')
            ->with($this->stringContains('is_active = 1'))
            ->willReturn([]);

        $results = $this->repository->findByFilters([]);

        $this->assertIsArray($results);
    }

    // ========================================
    // countByFilters() Tests
    // ========================================

    public function testCountByFiltersWithPropertyType()
    {
        $this->wpdb_mock->expects($this->once())
            ->method('prepare')
            ->with(
                $this->stringContains('SELECT COUNT(*)'),
                'condo'
            )
            ->willReturn('SELECT COUNT(*) FROM wp_ma_deal_templates WHERE property_type = \'condo\'');

        $this->wpdb_mock->expects($this->once())
            ->method('get_var')
            ->willReturn('5');

        $count = $this->repository->countByFilters(['property_type' => 'condo']);

        $this->assertEquals(5, $count);
    }

    public function testCountByFiltersWithMultipleFilters()
    {
        $this->wpdb_mock->expects($this->once())
            ->method('prepare')
            ->with(
                $this->anything(),
                'sfh',
                'listing',
                123
            )
            ->willReturn('SELECT COUNT(*) FROM wp_ma_deal_templates WHERE filters applied');

        $this->wpdb_mock->expects($this->once())
            ->method('get_var')
            ->willReturn('3');

        $count = $this->repository->countByFilters([
            'property_type' => 'sfh',
            'transaction_side' => 'listing',
            'account_id' => 123
        ]);

        $this->assertEquals(3, $count);
    }

    public function testCountByFiltersReturnsInteger()
    {
        $this->wpdb_mock->expects($this->once())
            ->method('get_var')
            ->willReturn('42');

        $count = $this->repository->countByFilters([]);

        $this->assertIsInt($count);
        $this->assertEquals(42, $count);
    }

    // ========================================
    // Integration with BaseRepository
    // ========================================

    public function testInheritsBaseRepositoryMethods()
    {
        // Verify that TemplateRepository has inherited methods from BaseRepository
        $this->assertTrue(method_exists($this->repository, 'find'));
        $this->assertTrue(method_exists($this->repository, 'findAll'));
        $this->assertTrue(method_exists($this->repository, 'create'));
        $this->assertTrue(method_exists($this->repository, 'update'));
        $this->assertTrue(method_exists($this->repository, 'delete'));
        $this->assertTrue(method_exists($this->repository, 'query'));
        $this->assertTrue(method_exists($this->repository, 'count'));
    }
}
