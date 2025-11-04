<?php
/**
 * TemplateEngine Unit Tests
 *
 * Comprehensive tests for the TemplateEngine class focusing on
 * YAML parsing, condition evaluation, and date calculations.
 *
 * @package MADealRoom\Tests\Unit\Services
 */

namespace MADealRoom\Tests\Unit\Services;

use MADealRoom\Tests\TestCase;
use MADealRoom\Services\TemplateEngine;
use MADealRoom\Repositories\TemplateRepository;

/**
 * TemplateEngine Test Class
 */
class TemplateEngineTest extends TestCase
{
    private $engine;
    private $templateRepository;

    protected function setUp(): void
    {
        parent::setUp();
        // Create mock for TemplateRepository
        $this->templateRepository = $this->createMock(TemplateRepository::class);

        // TemplateEngine requires TemplateRepository, other repos optional
        $this->engine = new TemplateEngine($this->templateRepository);
    }

    // ========================================
    // YAML Parsing Tests
    // ========================================

    public function testParseYamlWithValidYaml()
    {
        $yaml = "name: Test Template\nversion: 1.0\ntasks:\n  - title: Task 1\n  - title: Task 2";
        $result = $this->engine->parseYaml($yaml);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('name', $result);
        $this->assertEquals('Test Template', $result['name']);
        $this->assertArrayHasKey('tasks', $result);
        $this->assertCount(2, $result['tasks']);
    }

    public function testParseYamlWithInvalidYaml()
    {
        $invalidYaml = "invalid:\n  - unclosed: [bracket";
        $result = $this->engine->parseYaml($invalidYaml);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('error', $result);
        $this->assertStringContainsString('YAML parse error', $result['error']);
    }

    public function testParseYamlWithEmptyString()
    {
        $result = $this->engine->parseYaml('');
        // Symfony YAML parser returns null for empty strings, converted to empty array
        $this->assertIsArray($result);
        $this->assertEmpty($result);
    }

    public function testParseYamlWithComplexStructure()
    {
        $yaml = <<<YAML
name: Complex Template
version: 2.0
workflows:
  - name: Workflow 1
    tasks:
      - title: Task A
        description: First task
      - title: Task B
        description: Second task
conditional_tasks:
  - condition: property.has_financing == true
    tasks:
      - title: Financing Task
YAML;

        $result = $this->engine->parseYaml($yaml);

        $this->assertIsArray($result);
        $this->assertEquals('Complex Template', $result['name']);
        $this->assertArrayHasKey('workflows', $result);
        $this->assertArrayHasKey('conditional_tasks', $result);
    }

    // ========================================
    // Condition Evaluation Tests
    // ========================================

    public function testEvaluateConditionEquality()
    {
        $context = ['property_type' => 'condo', 'has_financing' => true];

        // Test property.field == value
        $this->assertTrue($this->engine->evaluateCondition('property.property_type == condo', $context));
        $this->assertFalse($this->engine->evaluateCondition('property.property_type == sfh', $context));

        // Test transaction.field == value
        $this->assertTrue($this->engine->evaluateCondition('transaction.property_type == condo', $context));
    }

    public function testEvaluateConditionBooleanTrue()
    {
        $context = ['has_financing' => true, 'has_septic' => 1];

        $this->assertTrue($this->engine->evaluateCondition('property.has_financing == true', $context));
        $this->assertTrue($this->engine->evaluateCondition('property.has_septic == true', $context));
    }

    public function testEvaluateConditionBooleanFalse()
    {
        $context = ['has_financing' => false, 'has_septic' => 0, 'missing_field' => null];

        $this->assertTrue($this->engine->evaluateCondition('property.has_financing == false', $context));
        $this->assertTrue($this->engine->evaluateCondition('property.has_septic == false', $context));
        $this->assertTrue($this->engine->evaluateCondition('property.missing_field == false', $context));
    }

    public function testEvaluateConditionNotEqual()
    {
        $context = ['property_type' => 'condo'];

        $this->assertTrue($this->engine->evaluateCondition('property.property_type != sfh', $context));
        $this->assertFalse($this->engine->evaluateCondition('property.property_type != condo', $context));

        // Missing field should satisfy !=
        $this->assertTrue($this->engine->evaluateCondition('property.missing_field != anything', $context));
    }

    public function testEvaluateConditionLessThan()
    {
        $context = ['bedrooms' => 3, 'units' => 5];

        $this->assertTrue($this->engine->evaluateCondition('property.bedrooms < 5', $context));
        $this->assertFalse($this->engine->evaluateCondition('property.bedrooms < 3', $context));
        $this->assertFalse($this->engine->evaluateCondition('property.bedrooms < 2', $context));
    }

    public function testEvaluateConditionGreaterThan()
    {
        $context = ['bedrooms' => 4, 'units' => 10];

        $this->assertTrue($this->engine->evaluateCondition('property.bedrooms > 2', $context));
        $this->assertFalse($this->engine->evaluateCondition('property.bedrooms > 4', $context));
        $this->assertFalse($this->engine->evaluateCondition('property.bedrooms > 5', $context));
    }

    public function testEvaluateConditionWithQuotedValues()
    {
        $context = ['property_type' => 'residential'];

        // The regex pattern ["\']?(\w+)["\']? only captures word characters (no hyphens)
        // So we test with simple alphanumeric values
        $this->assertTrue($this->engine->evaluateCondition('property.property_type == "residential"', $context));
        $this->assertTrue($this->engine->evaluateCondition("property.property_type == 'residential'", $context));
    }

    public function testEvaluateConditionMissingField()
    {
        $context = ['existing_field' => 'value'];

        // Missing field with == should be false
        $this->assertFalse($this->engine->evaluateCondition('property.missing_field == value', $context));

        // Missing field with != should be true
        $this->assertTrue($this->engine->evaluateCondition('property.missing_field != value', $context));
    }

    public function testEvaluateConditionNumericComparisons()
    {
        $context = ['units' => '6', 'price' => '500000'];

        $this->assertTrue($this->engine->evaluateCondition('property.units > 4', $context));
        $this->assertTrue($this->engine->evaluateCondition('property.units < 10', $context));
        $this->assertFalse($this->engine->evaluateCondition('property.units > 10', $context));
        $this->assertFalse($this->engine->evaluateCondition('property.units < 4', $context));
    }

    public function testEvaluateConditionEdgeCases()
    {
        $context = ['zero' => 0, 'empty_string' => '', 'null_value' => null];

        // Zero should be treated as false for boolean comparisons
        $this->assertTrue($this->engine->evaluateCondition('property.zero == false', $context));

        // Empty string should be treated as false
        $this->assertTrue($this->engine->evaluateCondition('property.empty_string == false', $context));

        // Null should be treated as false
        $this->assertTrue($this->engine->evaluateCondition('property.null_value == false', $context));
    }

    // ========================================
    // Date Calculation Tests
    // ========================================

    public function testCalculateDueDateWithExactAnchor()
    {
        $key_dates = [
            'closing_date' => '2025-03-01',
            'ps_date' => '2025-02-15',
            'listing_date' => '2025-01-15',
        ];

        // Exact date anchor
        $result = $this->engine->calculateDueDate('Closing', $key_dates);
        $this->assertNotNull($result);
        $this->assertStringStartsWith('2025-03-01', $result);

        $result = $this->engine->calculateDueDate('PS', $key_dates);
        $this->assertNotNull($result);
        $this->assertStringStartsWith('2025-02-15', $result);
    }

    public function testCalculateDueDateWithDaysBeforeClosing()
    {
        $key_dates = ['closing_date' => '2025-03-15'];

        // 7 days before closing
        $result = $this->engine->calculateDueDate('Closing-7d', $key_dates);
        $this->assertNotNull($result);
        $this->assertStringStartsWith('2025-03-08', $result);

        // 21 days before closing
        $result = $this->engine->calculateDueDate('Closing-21d', $key_dates);
        $this->assertNotNull($result);
        $this->assertStringStartsWith('2025-02-22', $result);
    }

    public function testCalculateDueDateWithDaysAfterPS()
    {
        $key_dates = ['ps_date' => '2025-02-01'];

        // 7 days after P&S
        $result = $this->engine->calculateDueDate('PS+7d', $key_dates);
        $this->assertNotNull($result);
        $this->assertStringStartsWith('2025-02-08', $result);

        // 14 days after P&S
        $result = $this->engine->calculateDueDate('PS+14d', $key_dates);
        $this->assertNotNull($result);
        $this->assertStringStartsWith('2025-02-15', $result);
    }

    public function testCalculateDueDateWithZeroDays()
    {
        $key_dates = ['listing_date' => '2025-01-20'];

        // Zero days offset (same as exact anchor)
        $result = $this->engine->calculateDueDate('Listing+0d', $key_dates);
        $this->assertNotNull($result);
        $this->assertStringStartsWith('2025-01-20', $result);
    }

    public function testCalculateDueDateWithMissingAnchor()
    {
        $key_dates = ['closing_date' => '2025-03-01'];

        // Referencing missing anchor date
        $result = $this->engine->calculateDueDate('PS+7d', $key_dates);
        $this->assertNull($result);

        $result = $this->engine->calculateDueDate('LoanCommitment-7d', $key_dates);
        $this->assertNull($result);
    }

    public function testCalculateDueDateWithInvalidFormat()
    {
        $key_dates = ['closing_date' => '2025-03-01'];

        // Invalid formats should return null
        $this->assertNull($this->engine->calculateDueDate('InvalidFormat', $key_dates));
        $this->assertNull($this->engine->calculateDueDate('Closing+', $key_dates));
        $this->assertNull($this->engine->calculateDueDate('+7d', $key_dates));
        $this->assertNull($this->engine->calculateDueDate('Closing7', $key_dates));
    }

    public function testCalculateDueDateWithAllAnchors()
    {
        $key_dates = [
            'closing_date' => '2025-03-01',
            'ps_date' => '2025-02-15',
            'listing_date' => '2025-01-01',
            'offer_accepted_date' => '2025-01-10',  // Correct key name
            'loan_commitment_date' => '2025-02-20',
        ];

        // Test each anchor type
        $anchors = ['Closing', 'PS', 'Listing', 'Offer', 'LoanCommitment', 'FirstMeeting'];

        foreach ($anchors as $anchor) {
            $result = $this->engine->calculateDueDate($anchor, $key_dates);
            $this->assertNotNull($result, "Anchor {$anchor} should have a valid date");
            $this->assertMatchesRegularExpression('/^\d{4}-\d{2}-\d{2}/', $result);
        }
    }

    public function testCalculateDueDateWithLargeDayOffsets()
    {
        $key_dates = ['closing_date' => '2025-06-01'];

        // 90 days before
        $result = $this->engine->calculateDueDate('Closing-90d', $key_dates);
        $this->assertNotNull($result);
        $this->assertStringStartsWith('2025-03-03', $result);

        // 180 days after
        $result = $this->engine->calculateDueDate('Closing+180d', $key_dates);
        $this->assertNotNull($result);
        $this->assertStringStartsWith('2025-11-28', $result);
    }

    public function testCalculateDueDateDateFormat()
    {
        $key_dates = ['closing_date' => '2025-03-01'];

        $result = $this->engine->calculateDueDate('Closing-7d', $key_dates);

        // Should return Y-m-d H:i:s format
        $this->assertMatchesRegularExpression('/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}$/', $result);
    }

    public function testCalculateDueDateCrossMonthBoundary()
    {
        $key_dates = ['closing_date' => '2025-03-05'];

        // Should correctly handle month boundaries
        $result = $this->engine->calculateDueDate('Closing-10d', $key_dates);
        $this->assertNotNull($result);
        $this->assertStringStartsWith('2025-02-23', $result);
    }

    public function testCalculateDueDateCrossYearBoundary()
    {
        $key_dates = ['closing_date' => '2025-01-05'];

        // Should correctly handle year boundaries
        $result = $this->engine->calculateDueDate('Closing-10d', $key_dates);
        $this->assertNotNull($result);
        $this->assertStringStartsWith('2024-12-26', $result);
    }

    public function testCalculateDueDateWithLeapYear()
    {
        $key_dates = ['closing_date' => '2024-03-01']; // 2024 is a leap year

        // Should handle leap year February correctly
        $result = $this->engine->calculateDueDate('Closing-7d', $key_dates);
        $this->assertNotNull($result);
        $this->assertStringStartsWith('2024-02-23', $result);
    }

    // ========================================
    // Edge Cases and Integration
    // ========================================

    public function testParseYamlPreservesDataTypes()
    {
        $yaml = <<<YAML
integer_value: 42
float_value: 3.14
boolean_true: true
boolean_false: false
null_value: null
string_value: "text"
YAML;

        $result = $this->engine->parseYaml($yaml);

        $this->assertIsInt($result['integer_value']);
        $this->assertIsFloat($result['float_value']);
        $this->assertTrue($result['boolean_true']);
        $this->assertFalse($result['boolean_false']);
        $this->assertNull($result['null_value']);
        $this->assertIsString($result['string_value']);
    }

    public function testEvaluateConditionCaseInsensitivity()
    {
        $context = ['property_type' => 'Condo'];

        // The implementation uses == which is case-sensitive in PHP
        $this->assertFalse($this->engine->evaluateCondition('property.property_type == condo', $context));
        $this->assertTrue($this->engine->evaluateCondition('property.property_type == Condo', $context));
    }

    public function testCalculateDueDateConsistency()
    {
        $key_dates = ['closing_date' => '2025-03-15'];

        // Multiple calls should return same result
        $result1 = $this->engine->calculateDueDate('Closing-7d', $key_dates);
        $result2 = $this->engine->calculateDueDate('Closing-7d', $key_dates);

        $this->assertEquals($result1, $result2);
    }
}
