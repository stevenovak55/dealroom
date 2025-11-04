# MA Deal Room - Testing Guide

This document provides comprehensive information about the testing infrastructure for the MA Deal Room WordPress plugin.

## Table of Contents

- [Overview](#overview)
- [Test Infrastructure](#test-infrastructure)
- [Running Tests](#running-tests)
- [Writing Tests](#writing-tests)
- [Test Helpers](#test-helpers)
- [Code Coverage](#code-coverage)
- [Continuous Integration](#continuous-integration)

## Overview

MA Deal Room uses PHPUnit 9.x for automated testing. The test suite includes:

- **Unit Tests**: Test individual classes and methods in isolation
- **Integration Tests**: Test REST API endpoints and database interactions
- **Code Coverage**: Target 80%+ coverage for all services and repositories

## Test Infrastructure

### Directory Structure

```
ma-deal-room/
├── phpunit.xml              # PHPUnit configuration
├── tests/
│   ├── bootstrap.php        # Test environment setup
│   ├── TestCase.php         # Base test class
│   ├── Helpers/             # Test helper utilities
│   │   ├── UserFactory.php
│   │   ├── TransactionFactory.php
│   │   └── TaskFactory.php
│   ├── Unit/                # Unit tests
│   │   ├── Services/
│   │   ├── Repositories/
│   │   └── Models/
│   └── Integration/         # Integration tests
│       └── Controllers/
└── coverage/                # Code coverage reports
```

### PHPUnit Configuration

The `phpunit.xml` file configures:
- Test suites (Unit, Integration, All Tests)
- Code coverage settings
- PHP environment variables
- Logging options

### Test Bootstrap

The `tests/bootstrap.php` file:
- Loads Composer autoloader
- Defines test mode constants
- Loads WordPress test environment (if available)
- Provides WordPress function stubs for unit tests

## Running Tests

### Prerequisites

1. Install dependencies:
```bash
composer install
```

2. (Optional) Set up WordPress test environment:
```bash
export WP_TESTS_DIR=/path/to/wordpress-tests-lib
```

### Run All Tests

```bash
composer test
```

Or directly:
```bash
./vendor/bin/phpunit
```

### Run Specific Test Suites

**Unit tests only:**
```bash
composer test:unit
```

**Integration tests only:**
```bash
composer test:integration
```

### Run Specific Test Files

```bash
./vendor/bin/phpunit tests/Unit/Services/AuthServiceTest.php
```

### Run with Code Coverage

**HTML coverage report:**
```bash
composer test:coverage
```

Open `coverage/html/index.html` in your browser.

**Clover XML coverage (for CI):**
```bash
composer test:coverage-clover
```

### Run Specific Test Methods

```bash
./vendor/bin/phpunit --filter testLoginSuccess
```

### Run Tests with Debugging Output

```bash
./vendor/bin/phpunit --testdox
```

## Writing Tests

### Basic Unit Test

Create a test file in `tests/Unit/` that extends `TestCase`:

```php
<?php

namespace MADealRoom\Tests\Unit\Services;

use MADealRoom\Tests\TestCase;
use MADealRoom\Services\AuthService;

class AuthServiceTest extends TestCase
{
    private $authService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->authService = new AuthService();
    }

    public function testLoginWithValidCredentials()
    {
        $result = $this->authService->login('test@example.com', 'password');

        $this->assertIsNotWPError($result);
        $this->assertArrayHasKeys(['access_token', 'refresh_token'], $result);
    }
}
```

### Using Test Factories

The test helpers provide factory methods for creating test data:

```php
use MADealRoom\Tests\Helpers\UserFactory;
use MADealRoom\Tests\Helpers\TransactionFactory;
use MADealRoom\Tests\Helpers\TaskFactory;

// Create a test user
$user = UserFactory::create([
    'email' => 'custom@test.com'
]);

// Create multiple users
$users = UserFactory::createMany(5);

// Create specialized users
$admin = UserFactory::createAdmin();
$unverifiedUser = UserFactory::createUnverified();
$lockedUser = UserFactory::createLocked();

// Create a transaction
$transaction = TransactionFactory::create([
    'property_address' => '123 Test St'
]);

// Create specialized transactions
$buyerTransaction = TransactionFactory::createBuyer();
$condo = TransactionFactory::createCondo();
$closed = TransactionFactory::createClosed();

// Create tasks
$task = TaskFactory::create();
$tasks = TaskFactory::createMany(10);
$overdueTask = TaskFactory::createOverdue();
```

### Integration Tests

Create integration tests in `tests/Integration/Controllers/`:

```php
<?php

namespace MADealRoom\Tests\Integration\Controllers;

use MADealRoom\Tests\TestCase;

class AuthControllerTest extends TestCase
{
    protected function usesDatabase(): bool
    {
        return true;
    }

    public function testRegisterEndpoint()
    {
        $request = $this->createMockRequest([
            'email' => 'newuser@test.com',
            'password' => 'SecurePass123!',
            'first_name' => 'Test',
            'last_name' => 'User',
        ], 'POST');

        $response = rest_do_request($request);

        $this->assertEquals(201, $response->get_status());
        $this->assertArrayHasKey('message', $response->get_data());
    }
}
```

### Custom Assertions

The `TestCase` base class provides custom assertions:

```php
// Assert array has specific keys
$this->assertArrayHasKeys(['id', 'email', 'name'], $user);

// Assert valid UUID
$this->assertIsUuid($userId);

// Assert valid date
$this->assertIsValidDate($createdAt);

// Assert valid email
$this->assertIsValidEmail($email);

// Assert WP_Error
$this->assertIsWPError($result);
$this->assertIsNotWPError($result);
```

### Testing Private Methods

Use the helper methods to test private/protected code:

```php
// Get private property value
$value = $this->getPrivateProperty($object, 'propertyName');

// Set private property value
$this->setPrivateProperty($object, 'propertyName', $value);

// Call private method
$result = $this->callPrivateMethod($object, 'methodName', [$arg1, $arg2]);
```

## Test Helpers

### UserFactory

Create test users with various configurations:

```php
// Basic user
$user = UserFactory::create();

// Admin user
$admin = UserFactory::createAdmin();

// Unverified user
$unverified = UserFactory::createUnverified();

// Locked user
$locked = UserFactory::createLocked();

// User with 2FA
$user2FA = UserFactory::createWith2FA();

// Password reset token
$resetToken = UserFactory::createPasswordResetToken($userId);

// Email verification token
$verifyToken = UserFactory::createEmailVerificationToken($userId, $email);

// User session
$session = UserFactory::createSession($userId, $refreshToken);
```

### TransactionFactory

Create test transactions:

```php
// Basic transaction
$transaction = TransactionFactory::create();

// Buyer transaction
$buyer = TransactionFactory::createBuyer();

// Seller transaction
$seller = TransactionFactory::createSeller();

// Property type specific
$condo = TransactionFactory::createCondo();
$multifamily = TransactionFactory::createMultifamily();

// With dates
$withDates = TransactionFactory::createWithDates([
    'offer_date' => '2025-01-01',
    'closing_date' => '2025-02-15',
]);

// Status specific
$closed = TransactionFactory::createClosed();
$cancelled = TransactionFactory::createCancelled();
```

### TaskFactory

Create test tasks:

```php
// Basic task
$task = TaskFactory::create();

// Multiple tasks
$tasks = TaskFactory::createMany(10);

// Status specific
$pending = TaskFactory::createPending();
$inProgress = TaskFactory::createInProgress();
$completed = TaskFactory::createCompleted();
$overdue = TaskFactory::createOverdue();
$skipped = TaskFactory::createSkipped();

// Party specific
$buyerTask = TaskFactory::createForParty('buyer');
$agentTask = TaskFactory::createForParty('buyer_agent');

// Timeline
$timeline = TaskFactory::createTimeline($transactionId, '2025-01-15');
```

## Code Coverage

### Coverage Goals

- Services: 80%+ coverage
- Repositories: 80%+ coverage
- Controllers: 70%+ coverage
- Models: 60%+ coverage

### Viewing Coverage

After running tests with coverage:

```bash
composer test:coverage
open coverage/html/index.html
```

### Coverage Exclusions

The following are excluded from coverage:
- CLI commands (src/CLI/)
- Plugin initialization (src/Core/Plugin.php)

## Continuous Integration

### GitHub Actions

Tests run automatically on:
- Push to main branch
- Pull requests
- Manual workflow dispatch

See `.github/workflows/test.yml` for CI configuration.

### CI Test Commands

```yaml
- composer install
- composer test
- composer test:coverage-clover
```

## Best Practices

1. **One Assertion Per Test** (when possible): Makes failures easier to diagnose
2. **Test Edge Cases**: Don't just test the happy path
3. **Use Factories**: Leverage test factories for consistent test data
4. **Clean Up**: Use `setUp()` and `tearDown()` to maintain test isolation
5. **Mock External Services**: Don't call real APIs in tests
6. **Test Behavior, Not Implementation**: Focus on what, not how
7. **Descriptive Test Names**: Use clear, descriptive method names
8. **Arrange-Act-Assert**: Structure tests clearly

## Troubleshooting

### Tests Not Running

```bash
# Regenerate autoloader
composer dump-autoload

# Check PHPUnit installation
./vendor/bin/phpunit --version
```

### WordPress Functions Not Found

The bootstrap file provides stubs for common WordPress functions. If you need additional functions, add them to `tests/bootstrap.php`.

### Database Connection Errors

For unit tests, database connections are not required. For integration tests, ensure your database is configured in `phpunit.xml`.

## Additional Resources

- [PHPUnit Documentation](https://phpunit.de/documentation.html)
- [WordPress Unit Tests](https://make.wordpress.org/core/handbook/testing/automated-testing/phpunit/)
- [Test-Driven Development](https://en.wikipedia.org/wiki/Test-driven_development)

## Getting Help

If you encounter issues with testing:

1. Check this documentation
2. Review existing test files for examples
3. Check the PHPUnit documentation
4. Ask in the team Slack channel

---

**Last Updated:** 2025-11-01
**Maintained By:** MA Deal Room Development Team
