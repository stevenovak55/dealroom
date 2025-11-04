# Integration Tests

Integration tests for MA Deal Room WordPress plugin REST API endpoints.

## Requirements

Integration tests require a WordPress test environment with database access:

1. **WordPress Test Suite**: Install via `install-wp-tests.sh`
2. **Test Database**: MySQL/MariaDB database for testing
3. **WordPress Core**: Full WordPress installation for WP_REST_Request/Response
4. **Environment Variable**: `WP_TESTS_DIR` pointing to WordPress test suite

## Setup WordPress Test Environment

### 1. Install WordPress Test Suite

```bash
# Download WordPress test installation script
curl -O https://raw.githubusercontent.com/wp-cli/scaffold-command/master/templates/install-wp-tests.sh

# Make it executable
chmod +x install-wp-tests.sh

# Run installation (replace with your database credentials)
./install-wp-tests.sh wordpress_test root 'password' localhost latest

# This creates:
# - /tmp/wordpress-tests-lib/ (test framework)
# - /tmp/wordpress/ (WordPress core)
# - wp-tests-config.php (database config)
```

### 2. Set Environment Variable

Add to your `.bashrc` or `.zshrc`:

```bash
export WP_TESTS_DIR=/tmp/wordpress-tests-lib
```

### 3. Configure Test Database

Create `wp-tests-config.php` with test database credentials:

```php
define('DB_NAME', 'wordpress_test');
define('DB_USER', 'root');
define('DB_PASSWORD', 'password');
define('DB_HOST', 'localhost');
```

## Running Integration Tests

```bash
# Run all integration tests
./vendor/bin/phpunit --testsuite Integration

# Run specific controller tests
./vendor/bin/phpunit tests/Integration/Controllers/AuthControllerTest.php

# With coverage
XDEBUG_MODE=coverage ./vendor/bin/phpunit --testsuite Integration --coverage-html coverage/integration
```

## Test Structure

Integration tests extend `IntegrationTestCase` which provides:

- WordPress test environment bootstrap
- Database transaction management
- REST API request helpers
- Authentication helpers
- Test user/account creation
- Cleanup after tests

## Writing Integration Tests

### Example: Testing REST API Endpoint

```php
<?php
namespace MADealRoom\Tests\Integration\Controllers;

use MADealRoom\Tests\IntegrationTestCase;

class ExampleControllerTest extends IntegrationTestCase
{
    public function testGetEndpoint()
    {
        // Create authenticated request
        $request = $this->createAuthenticatedRequest('GET', '/ma-deal-room/v1/resource');

        // Execute request
        $response = $this->executeRequest($request);

        // Assert response
        $this->assertEquals(200, $response->get_status());
        $this->assertArrayHasKey('data', $response->get_data());
    }

    public function testPostEndpoint()
    {
        // Create request with data
        $request = $this->createAuthenticatedRequest('POST', '/ma-deal-room/v1/resource', [
            'name' => 'Test Resource',
            'status' => 'active'
        ]);

        // Execute
        $response = $this->executeRequest($request);

        // Assert
        $this->assertEquals(201, $response->get_status());
        $this->assertEquals('Test Resource', $response->get_data()['name']);
    }
}
```

## Current Status

⚠️ **Integration tests require WordPress test environment setup**

Currently, our test suite runs unit tests with WordPress function stubs. To run integration tests:

1. Follow setup instructions above
2. Set `WP_TESTS_DIR` environment variable
3. Uncomment integration test bootstrap in `tests/bootstrap.php`
4. Run integration test suite

## Test Coverage Goals

- **AuthController**: Registration, login, logout, password reset, email verification
- **TransactionController**: CRUD operations, filtering, status updates
- **TaskController**: CRUD operations, assignment, dependencies, bulk updates
- **TemplateController**: CRUD operations, filtering by property type
- **DocumentController**: Upload, download, delete with signed URLs
- **Security**: Authentication, authorization, CSRF protection, rate limiting

## Benefits of Integration Tests

- Test actual REST API routes
- Verify database interactions
- Test authentication flows
- Catch integration bugs
- Validate request/response formats
- Test error handling
- Verify business logic end-to-end

## vs Unit Tests

| Aspect | Unit Tests | Integration Tests |
|--------|------------|-------------------|
| Speed | Fast (< 100ms) | Slower (database I/O) |
| Isolation | High (mocked dependencies) | Low (real database) |
| Setup | Minimal | WordPress + Database |
| Coverage | Logic/algorithms | Full workflows |
| Best For | Services, repositories | Controllers, APIs |

Both are valuable and complement each other!
