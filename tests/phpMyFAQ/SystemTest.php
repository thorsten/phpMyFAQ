<?php

namespace phpMyFAQ;

use phpMyFAQ\Database\DatabaseDriver;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\TestCase;

#[AllowMockObjectsWithoutExpectations]
class SystemTest extends TestCase
{
    public function testGetPoweredByPlainString(): void
    {
        $this->assertEquals(
            sprintf('powered with ❤️ and ☕️ by phpMyFAQ %s', System::getVersion()),
            System::getPoweredByPlainString(),
        );
    }

    public function testGetPoweredByString(): void
    {
        $this->assertEquals(
            sprintf(
                'powered with ❤️ and ☕️ by <a class="link-light text-decoration-none" target="_blank" href="https://www.phpmyfaq.de/">phpMyFAQ</a> %s',
                System::getVersion(),
            ),
            System::getPoweredByString(),
        );
    }

    public function testIsSqlite(): void
    {
        $this->assertTrue(System::isSqlite('sqlite3'));
        $this->assertTrue(System::isSqlite('pdo_sqlite'));
        $this->assertFalse(System::isSqlite(''));
        $this->assertFalse(System::isSqlite('pdo_mysql'));
    }

    public function testIsUpdateNecessary(): void
    {
        // An outdated installed version requires an update
        $this->assertTrue(System::isUpdateNecessary('1.0.0'));

        // The current code base version is up to date
        $this->assertFalse(System::isUpdateNecessary(System::getVersion()));

        // A newer installed version is not treated as outdated
        $this->assertFalse(System::isUpdateNecessary('999.0.0'));

        // Empty or unknown versions must not trigger a redirect to the updater
        $this->assertFalse(System::isUpdateNecessary(''));
        $this->assertFalse(System::isUpdateNecessary(null));
    }

    public function testIsUpdateExemptRequestForUpdaterAndApiContexts(): void
    {
        // The standalone updater, installer and REST endpoints must never be
        // redirected, otherwise the recovery process would loop or break.
        $this->assertTrue(System::isUpdateExemptRequest('/update/index.php', '/'));
        $this->assertTrue(System::isUpdateExemptRequest('/setup/index.php', '/'));
        $this->assertTrue(System::isUpdateExemptRequest('/api/index.php', '/version'));
        $this->assertTrue(System::isUpdateExemptRequest('/admin/api/index.php', '/update-database'));
    }

    public function testIsUpdateExemptRequestAllowsAdminRecoveryPages(): void
    {
        // Admin login and the upgrade UI must stay reachable during a pending
        // update so the maintenance mode can be enabled and the update started.
        $this->assertTrue(System::isUpdateExemptRequest('/admin/index.php', '/login'));
        $this->assertTrue(System::isUpdateExemptRequest('/admin/index.php', '/authenticate'));
        $this->assertTrue(System::isUpdateExemptRequest('/admin/index.php', '/check'));
        $this->assertTrue(System::isUpdateExemptRequest('/admin/index.php', '/token'));
        $this->assertTrue(System::isUpdateExemptRequest('/admin/index.php', '/update'));
    }

    public function testIsUpdateExemptRequestBlocksOtherAdminAndFrontendPages(): void
    {
        // Content-facing admin pages still hit the outdated schema and must be
        // redirected to the updater.
        $this->assertFalse(System::isUpdateExemptRequest('/admin/index.php', '/dashboard'));
        $this->assertFalse(System::isUpdateExemptRequest('/admin/index.php', '/'));
        $this->assertFalse(System::isUpdateExemptRequest('/admin/index.php', '/category'));

        // The front-end must not be unlocked just because a path happens to look
        // like an admin recovery route.
        $this->assertFalse(System::isUpdateExemptRequest('/index.php', '/login'));
        $this->assertFalse(System::isUpdateExemptRequest('/index.php', '/'));
    }

    public function testSetDatabase(): void
    {
        // Create a mock DatabaseDriver object
        $database = $this->createMock(DatabaseDriver::class);

        // Create a System object and set the mock database driver
        $system = new System();
        $result = $system->setDatabase($database);

        // Check that the System object was returned
        $this->assertInstanceOf(System::class, $result);

        // Check that the database driver was set correctly
        $this->assertSame($database, $system->getDatabase());
    }

    public function testGetHttpsStatus(): void
    {
        $system = new System();
        $result = $system->getHttpsStatus();

        $this->assertFalse($result); // because we're on the CLI
    }

    public function testCheckRequiredExtensions(): void
    {
        $system = new System();
        $result = $system->checkRequiredExtensions();

        $this->assertTrue($result);
    }

    public function testGetApiVersion(): void
    {
        $result = System::getApiVersion();

        $this->assertIsString($result);
        $this->assertNotEmpty($result);
    }

    public function testGetPluginVersion(): void
    {
        $result = System::getPluginVersion();

        $this->assertIsString($result);
        $this->assertNotEmpty($result);
    }

    public function testGetMcpServerVersion(): void
    {
        $result = System::getMcpServerVersion();

        $this->assertIsString($result);
        $this->assertNotEmpty($result);
    }

    public function testGetDocumentationUrl(): void
    {
        $expectedUrl = 'https://www.phpmyfaq.de/docs/4.2';
        $actualUrl = System::getDocumentationUrl();

        $this->assertEquals($expectedUrl, $actualUrl);
    }

    public function testGetGitHubIssuesUrl(): void
    {
        $expectedUrl = 'https://github.com/thorsten/phpMyFAQ/issues';
        $actualUrl = System::getGitHubIssuesUrl();

        $this->assertEquals($expectedUrl, $actualUrl);
    }

    public function testGetAvailableTemplates()
    {
        $system = new System();
        $result = $system->getAvailableTemplates();

        $this->assertIsArray($result);
        $this->assertNotEmpty($result);
    }

    public function testGetSupportedSafeDatabases()
    {
        $system = new System();
        $result = $system->getSupportedSafeDatabases();

        $this->assertIsArray($result);
        $this->assertNotEmpty($result);
    }

    public function testGetSystemUri(): void
    {
        $configuration = $this->createStub(Configuration::class);
        $configuration->method('getDefaultUrl')->willReturn('https://example.com');

        $system = new System();
        $result = $system->getSystemUri($configuration);

        $this->assertIsString($result);
        $this->assertNotEmpty($result);
        $this->assertStringContainsString('http', $result);
    }

    public function testCheckDatabase(): void
    {
        $system = new System();
        $result = $system->checkDatabase();

        $this->assertTrue($result);
    }

    public function testCheckInstallation(): void
    {
        $system = new System();
        $result = $system->checkInstallation();

        $this->assertIsBool($result);
    }

    public function testGetMissingExtensions(): void
    {
        $system = new System();
        $result = $system->getMissingExtensions();

        $this->assertEquals([], $result);
    }

    public function testGetSystemUriWithHttpUrl(): void
    {
        $configuration = $this->createStub(Configuration::class);
        $configuration->method('getDefaultUrl')->willReturn('http://example.com');

        $system = new System();
        $result = $system->getSystemUri($configuration);

        $this->assertIsString($result);
        $this->assertStringEndsWith('/', $result);
    }

    public function testGetSystemUriConvertsHttpToHttpsWhenSecure(): void
    {
        $_SERVER['HTTPS'] = 'on';

        $configuration = $this->createStub(Configuration::class);
        $configuration->method('getDefaultUrl')->willReturn('http://example.com');

        $system = new System();
        $result = $system->getSystemUri($configuration);

        $this->assertStringStartsWith('https://', $result);
        $this->assertStringEndsWith('/', $result);

        unset($_SERVER['HTTPS']);
    }

    public function testGetSystemUriWithTrailingSlash(): void
    {
        $configuration = $this->createStub(Configuration::class);
        $configuration->method('getDefaultUrl')->willReturn('https://example.com/');

        $system = new System();
        $result = $system->getSystemUri($configuration);

        $this->assertEquals('https://example.com/', $result);
    }

    public function testGetVersion(): void
    {
        $version = System::getVersion();

        $this->assertIsString($version);
        $this->assertMatchesRegularExpression('/^\d+\.\d+\.\d+(-\w+)?$/', $version);
    }

    public function testGetMajorVersion(): void
    {
        $majorVersion = System::getMajorVersion();

        $this->assertIsString($majorVersion);
        $this->assertMatchesRegularExpression('/^\d+\.\d+$/', $majorVersion);
    }

    public function testIsDevelopmentVersion(): void
    {
        $result = System::isDevelopmentVersion();

        $this->assertIsBool($result);
    }

    public function testGetDatabaseReturnsNullByDefault(): void
    {
        $system = new System();

        $this->assertNull($system->getDatabase());
    }

    /**
     * @throws \Exception
     */
    public function testCreateHashes(): void
    {
        $system = new System();
        $result = $system->createHashes();

        $this->assertIsString($result);
        $this->assertNotEmpty($result);
        $this->assertStringContainsString('created', $result);
    }

    /**
     * @throws \Exception
     */
    public function testCreateHashesExcludesMultisiteAndUpgrade(): void
    {
        $multisiteDir = PMF_ROOT_DIR . '/multisite/sub';
        $upgradeDir = PMF_ROOT_DIR . '/upgrade/sub';

        @mkdir($multisiteDir, 0755, true);
        @mkdir($upgradeDir, 0755, true);

        file_put_contents($multisiteDir . '/test.php', '<?php // test');
        file_put_contents($upgradeDir . '/test.php', '<?php // test');

        $system = new System();
        $result = $system->createHashes();
        $decoded = json_decode($result, true);

        $this->assertArrayNotHasKey('/multisite/sub/test.php', $decoded);
        $this->assertArrayNotHasKey('/upgrade/sub/test.php', $decoded);

        @unlink($multisiteDir . '/test.php');
        @unlink($upgradeDir . '/test.php');
        @rmdir($multisiteDir);
        @rmdir(PMF_ROOT_DIR . '/multisite');
        @rmdir($upgradeDir);
        @rmdir(PMF_ROOT_DIR . '/upgrade');
    }
}
