<?php
/**
 * TemplateController Integration Tests
 *
 * Tests REST API endpoints for template management.
 *
 * @package MADealRoom\Tests\Integration\Controllers
 */

namespace MADealRoom\Tests\Integration\Controllers;

use MADealRoom\Tests\IntegrationTestCase;

/**
 * TemplateController Integration Test Class
 *
 * NOTE: These tests require WordPress test environment.
 * Run: export WP_TESTS_DIR=/tmp/wordpress-tests-lib
 */
class TemplateControllerTest extends IntegrationTestCase
{
    /**
     * Test GET /ma-deal-room/v1/templates - List templates
     */
    public function testListTemplates()
    {
        // Arrange: Login as test user
        $this->loginAsTestUser();

        // Act: Create request and execute
        $request = $this->createAuthenticatedRequest('GET', '/ma-deal-room/v1/templates');
        $response = $this->executeRequest($request);

        // Assert: Check response
        $this->assertResponseStatus(200, $response);
        $this->assertResponseHasKeys(['templates', 'total'], $response);

        $data = $response->get_data();
        $this->assertIsArray($data['templates']);
        $this->assertIsInt($data['total']);
    }

    /**
     * Test GET /ma-deal-room/v1/templates with filters
     */
    public function testListTemplatesWithFilters()
    {
        $this->loginAsTestUser();

        // Test filtering by property type
        $request = $this->createAuthenticatedRequest('GET', '/ma-deal-room/v1/templates', [
            'property_type' => 'condo',
        ]);
        $response = $this->executeRequest($request);

        $this->assertResponseStatus(200, $response);
        $data = $response->get_data();

        // Verify only condo templates returned
        foreach ($data['templates'] as $template) {
            $this->assertContains($template['property_type'], ['condo', 'Any']);
        }
    }

    /**
     * Test GET /ma-deal-room/v1/templates/{id} - Get single template
     */
    public function testGetTemplate()
    {
        $this->loginAsTestUser();

        // Assume template with ID 1 exists (system template)
        $request = $this->createAuthenticatedRequest('GET', '/ma-deal-room/v1/templates/1');
        $response = $this->executeRequest($request);

        $this->assertResponseStatus(200, $response);
        $this->assertResponseHasKeys([
            'id',
            'name',
            'description',
            'property_type',
            'template_yaml',
            'is_system'
        ], $response);
    }

    /**
     * Test GET /ma-deal-room/v1/templates/{id} - Template not found
     */
    public function testGetTemplateNotFound()
    {
        $this->loginAsTestUser();

        $request = $this->createAuthenticatedRequest('GET', '/ma-deal-room/v1/templates/99999');
        $response = $this->executeRequest($request);

        $this->assertResponseStatus(404, $response);
    }

    /**
     * Test POST /ma-deal-room/v1/templates - Create template
     */
    public function testCreateTemplate()
    {
        $this->loginAsTestUser();

        $template_data = [
            'name' => 'Test Template',
            'description' => 'A test template',
            'property_type' => 'sfh',
            'transaction_side' => 'buyer',
            'template_yaml' => "name: Test\nversion: 1.0\ntasks:\n  - title: Task 1",
        ];

        $request = $this->createAuthenticatedRequest('POST', '/ma-deal-room/v1/templates', $template_data);
        $response = $this->executeRequest($request);

        $this->assertResponseStatus(201, $response);
        $this->assertResponseHasKeys(['id', 'name', 'template_yaml'], $response);

        $data = $response->get_data();
        $this->assertEquals('Test Template', $data['name']);
        $this->assertEquals('sfh', $data['property_type']);
    }

    /**
     * Test POST /ma-deal-room/v1/templates - Validation errors
     */
    public function testCreateTemplateValidationError()
    {
        $this->loginAsTestUser();

        // Missing required field 'name'
        $template_data = [
            'property_type' => 'sfh',
        ];

        $request = $this->createAuthenticatedRequest('POST', '/ma-deal-room/v1/templates', $template_data);
        $response = $this->executeRequest($request);

        $this->assertResponseStatus(400, $response);
        $this->assertResponseIsError($response, 'rest_missing_callback_param');
    }

    /**
     * Test PUT /ma-deal-room/v1/templates/{id} - Update template
     */
    public function testUpdateTemplate()
    {
        $this->loginAsTestUser();

        // First create a template
        $create_request = $this->createAuthenticatedRequest('POST', '/ma-deal-room/v1/templates', [
            'name' => 'Original Name',
            'property_type' => 'condo',
            'template_yaml' => "name: Test\nversion: 1.0",
        ]);
        $create_response = $this->executeRequest($create_request);
        $template_id = $create_response->get_data()['id'];

        // Now update it
        $update_data = [
            'name' => 'Updated Name',
            'description' => 'Updated description',
        ];

        $update_request = $this->createAuthenticatedRequest(
            'PUT',
            "/ma-deal-room/v1/templates/{$template_id}",
            $update_data
        );
        $update_response = $this->executeRequest($update_request);

        $this->assertResponseStatus(200, $update_response);
        $data = $update_response->get_data();
        $this->assertEquals('Updated Name', $data['name']);
        $this->assertEquals('Updated description', $data['description']);
    }

    /**
     * Test DELETE /ma-deal-room/v1/templates/{id} - Delete template
     */
    public function testDeleteTemplate()
    {
        $this->loginAsTestUser();

        // Create a template to delete
        $create_request = $this->createAuthenticatedRequest('POST', '/ma-deal-room/v1/templates', [
            'name' => 'To Delete',
            'property_type' => 'sfh',
            'template_yaml' => "name: Test\nversion: 1.0",
        ]);
        $create_response = $this->executeRequest($create_request);
        $template_id = $create_response->get_data()['id'];

        // Delete it
        $delete_request = $this->createAuthenticatedRequest(
            'DELETE',
            "/ma-deal-room/v1/templates/{$template_id}"
        );
        $delete_response = $this->executeRequest($delete_request);

        $this->assertResponseStatus(200, $delete_response);

        // Verify it's deleted
        $get_request = $this->createAuthenticatedRequest(
            'GET',
            "/ma-deal-room/v1/templates/{$template_id}"
        );
        $get_response = $this->executeRequest($get_request);
        $this->assertResponseStatus(404, $get_response);
    }

    /**
     * Test DELETE /ma-deal-room/v1/templates/{id} - Cannot delete system template
     */
    public function testCannotDeleteSystemTemplate()
    {
        $this->loginAsTestUser();

        // Try to delete system template (ID 1)
        $request = $this->createAuthenticatedRequest('DELETE', '/ma-deal-room/v1/templates/1');
        $response = $this->executeRequest($request);

        $this->assertResponseStatus(403, $response);
        $this->assertResponseIsError($response, 'cannot_delete_system_template');
    }

    /**
     * Test authentication required
     */
    public function testAuthenticationRequired()
    {
        // Don't login - make unauthenticated request
        $request = $this->createAuthenticatedRequest('GET', '/ma-deal-room/v1/templates');
        // Don't set auth token
        $this->auth_token = null;

        $response = $this->executeRequest($request);

        $this->assertResponseStatus(401, $response);
        $this->assertResponseIsError($response, 'rest_forbidden');
    }

    /**
     * Test authorization - user can only access their account's templates
     */
    public function testAuthorizationAccountIsolation()
    {
        // Create first user's template
        $this->loginAsTestUser();
        $create_request = $this->createAuthenticatedRequest('POST', '/ma-deal-room/v1/templates', [
            'name' => 'User 1 Template',
            'property_type' => 'sfh',
            'template_yaml' => "name: Test\nversion: 1.0",
        ]);
        $create_response = $this->executeRequest($create_request);
        $template_id = $create_response->get_data()['id'];

        // Create second user (different account)
        $this->createTestUser('subscriber');
        $this->createTestAccount();
        $this->loginAsTestUser();

        // Try to access first user's template
        $get_request = $this->createAuthenticatedRequest('GET', "/ma-deal-room/v1/templates/{$template_id}");
        $get_response = $this->executeRequest($get_request);

        // Should be forbidden or not found
        $this->assertGreaterThanOrEqual(403, $get_response->get_status());
    }
}
