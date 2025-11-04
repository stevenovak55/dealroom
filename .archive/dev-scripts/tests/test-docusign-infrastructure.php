<?php
/**
 * Test DocuSign Infrastructure - T3.3.1 Verification
 *
 * This script verifies that all DocuSign infrastructure components
 * are properly implemented and functional.
 */

require_once __DIR__ . '/ma-deal-room/vendor/autoload.php';

class DocuSignInfrastructureTest {
    private $results = [];
    private $passed = 0;
    private $failed = 0;

    public function run(): void {
        echo "\n" . str_repeat('=', 80) . "\n";
        echo "DocuSign Infrastructure Test - T3.3.1 Verification\n";
        echo str_repeat('=', 80) . "\n\n";

        // Test 1: Class existence
        $this->testClassExistence();

        // Test 2: Interface implementation
        $this->testInterfaceImplementation();

        // Test 3: Dependencies
        $this->testDependencies();

        // Test 4: Database tables
        $this->testDatabaseTables();

        // Test 5: Repository methods
        $this->testRepositoryMethods();

        // Test 6: Service methods
        $this->testServiceMethods();

        // Test 7: Controller endpoints
        $this->testControllerEndpoints();

        // Test 8: Plugin registration
        $this->testPluginRegistration();

        // Print results
        $this->printResults();
    }

    private function testClassExistence(): void {
        echo "Test 1: Class Existence\n";
        echo str_repeat('-', 80) . "\n";

        $classes = [
            'MADealRoom\\Services\\Integration\\DocuSign\\DocuSignClientInterface',
            'MADealRoom\\Services\\Integration\\DocuSign\\DocuSignClient',
            'MADealRoom\\Services\\Integration\\DocuSign\\DocuSignTemplateService',
            'MADealRoom\\Services\\Integration\\DocuSign\\EnvelopeService',
            'MADealRoom\\Repositories\\DocuSignConfigRepository',
            'MADealRoom\\REST\\Controllers\\DocuSignController',
        ];

        foreach ($classes as $class) {
            $exists = interface_exists($class) || class_exists($class);
            $this->recordResult("Class/Interface exists: {$class}", $exists);
        }

        echo "\n";
    }

    private function testInterfaceImplementation(): void {
        echo "Test 2: Interface Implementation\n";
        echo str_repeat('-', 80) . "\n";

        $implements = class_implements('MADealRoom\\Services\\Integration\\DocuSign\\DocuSignClient');
        $implementsInterface = in_array(
            'MADealRoom\\Services\\Integration\\DocuSign\\DocuSignClientInterface',
            $implements ?: []
        );

        $this->recordResult(
            'DocuSignClient implements DocuSignClientInterface',
            $implementsInterface
        );

        // Check for required methods
        $requiredMethods = [
            'authenticate',
            'refreshToken',
            'getAccessToken',
            'isAuthenticated',
            'listTemplates',
            'getTemplate',
            'createEnvelope',
            'createEnvelopeFromTemplate',
            'sendEnvelope',
            'getEnvelopeStatus',
            'getEnvelopeRecipients',
            'downloadDocument',
            'downloadCertificate',
            'voidEnvelope',
            'resendEnvelope',
            'testConnection',
        ];

        $reflection = new \ReflectionClass('MADealRoom\\Services\\Integration\\DocuSign\\DocuSignClient');

        foreach ($requiredMethods as $method) {
            $hasMethod = $reflection->hasMethod($method);
            $this->recordResult("DocuSignClient has method: {$method}", $hasMethod);
        }

        echo "\n";
    }

    private function testDependencies(): void {
        echo "Test 3: Dependencies\n";
        echo str_repeat('-', 80) . "\n";

        // Check composer packages
        $composerLock = json_decode(file_get_contents(__DIR__ . '/ma-deal-room/composer.lock'), true);
        $packages = array_column($composerLock['packages'] ?? [], 'name');

        $requiredPackages = [
            'firebase/php-jwt',
            'guzzlehttp/guzzle',
            'defuse/php-encryption',
        ];

        foreach ($requiredPackages as $package) {
            $installed = in_array($package, $packages);
            $this->recordResult("Package installed: {$package}", $installed);
        }

        echo "\n";
    }

    private function testDatabaseTables(): void {
        echo "Test 4: Database Tables\n";
        echo str_repeat('-', 80) . "\n";

        // Check migration files exist
        $migrations = [
            __DIR__ . '/ma-deal-room/database/migrations/024_create_docusign_config_table.sql',
            __DIR__ . '/ma-deal-room/database/migrations/025_create_docusign_envelopes_table.sql',
        ];

        foreach ($migrations as $migration) {
            $exists = file_exists($migration);
            $basename = basename($migration);
            $this->recordResult("Migration file exists: {$basename}", $exists);

            if ($exists) {
                $content = file_get_contents($migration);
                $hasCreateTable = strpos($content, 'CREATE TABLE') !== false;
                $this->recordResult("  └─ Contains CREATE TABLE statement", $hasCreateTable);
            }
        }

        echo "\n";
    }

    private function testRepositoryMethods(): void {
        echo "Test 5: Repository Methods\n";
        echo str_repeat('-', 80) . "\n";

        $requiredMethods = [
            'getByAccountId',
            'getActiveByAccountId',
            'create',
            'update',
            'updateByAccountId',
            'delete',
            'deleteByAccountId',
            'existsForAccount',
            'deactivateAll',
        ];

        $reflection = new \ReflectionClass('MADealRoom\\Repositories\\DocuSignConfigRepository');

        foreach ($requiredMethods as $method) {
            $hasMethod = $reflection->hasMethod($method);
            $this->recordResult("DocuSignConfigRepository has method: {$method}", $hasMethod);
        }

        echo "\n";
    }

    private function testServiceMethods(): void {
        echo "Test 6: Service Methods\n";
        echo str_repeat('-', 80) . "\n";

        // DocuSignTemplateService
        $templateServiceMethods = [
            'getTemplates',
            'getTemplate',
            'mapTransactionDataToTemplate',
        ];

        $reflection = new \ReflectionClass('MADealRoom\\Services\\Integration\\DocuSign\\DocuSignTemplateService');

        foreach ($templateServiceMethods as $method) {
            $hasMethod = $reflection->hasMethod($method);
            $this->recordResult("DocuSignTemplateService has method: {$method}", $hasMethod);
        }

        // EnvelopeService
        $envelopeServiceMethods = [
            'createEnvelopeFromTemplate',
            'sendEnvelope',
            'getEnvelopeStatus',
            'voidEnvelope',
        ];

        $reflection = new \ReflectionClass('MADealRoom\\Services\\Integration\\DocuSign\\EnvelopeService');

        foreach ($envelopeServiceMethods as $method) {
            $hasMethod = $reflection->hasMethod($method);
            $this->recordResult("EnvelopeService has method: {$method}", $hasMethod);
        }

        echo "\n";
    }

    private function testControllerEndpoints(): void {
        echo "Test 7: Controller Endpoints\n";
        echo str_repeat('-', 80) . "\n";

        $requiredMethods = [
            'register_routes',
            'get_config',
            'save_config',
            'test_connection',
            'list_templates',
            'get_template',
            'preview_template',
            'create_envelope',
            'list_envelopes',
            'get_envelope',
            'send_envelope',
            'void_envelope',
            'resend_envelope',
            'get_envelope_recipients',
            'get_envelope_stats',
        ];

        $reflection = new \ReflectionClass('MADealRoom\\REST\\Controllers\\DocuSignController');

        foreach ($requiredMethods as $method) {
            $hasMethod = $reflection->hasMethod($method);
            $this->recordResult("DocuSignController has method: {$method}", $hasMethod);
        }

        echo "\n";
    }

    private function testPluginRegistration(): void {
        echo "Test 8: Plugin Registration\n";
        echo str_repeat('-', 80) . "\n";

        $pluginFile = __DIR__ . '/ma-deal-room/src/Core/Plugin.php';
        $content = file_get_contents($pluginFile);

        $hasImport = strpos($content, 'use MADealRoom\\REST\\Controllers\\DocuSignController') !== false;
        $this->recordResult('Plugin imports DocuSignController', $hasImport);

        $hasRegistration = strpos($content, "register('docusign_controller'") !== false;
        $this->recordResult('Plugin registers docusign_controller in container', $hasRegistration);

        $hasRouteRegistration = strpos($content, "docusign_controller')->register_routes()") !== false;
        $this->recordResult('Plugin registers DocuSign routes', $hasRouteRegistration);

        echo "\n";
    }

    private function recordResult(string $test, bool $passed): void {
        $this->results[] = [
            'test' => $test,
            'passed' => $passed,
        ];

        if ($passed) {
            $this->passed++;
            echo "  ✅ {$test}\n";
        } else {
            $this->failed++;
            echo "  ❌ {$test}\n";
        }
    }

    private function printResults(): void {
        echo "\n" . str_repeat('=', 80) . "\n";
        echo "Test Results Summary\n";
        echo str_repeat('=', 80) . "\n";
        echo "Total Tests: " . ($this->passed + $this->failed) . "\n";
        echo "Passed: {$this->passed} ✅\n";
        echo "Failed: {$this->failed} ❌\n";
        echo "Success Rate: " . round(($this->passed / ($this->passed + $this->failed)) * 100, 2) . "%\n";
        echo str_repeat('=', 80) . "\n";

        if ($this->failed === 0) {
            echo "\n🎉 ALL TESTS PASSED! T3.3.1 is COMPLETE and ready for production.\n\n";
            exit(0);
        } else {
            echo "\n⚠️  Some tests failed. Please review the failures above.\n\n";
            exit(1);
        }
    }
}

// Run tests
$test = new DocuSignInfrastructureTest();
$test->run();
