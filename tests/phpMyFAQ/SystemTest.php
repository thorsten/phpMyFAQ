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
        $this->assertFalse(System::isSqlite(''));
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
        $database = $this->getMockBuilder(DatabaseDriver::class)->getMock();

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

    public function testGetDocumentationUrl(): void
    {
        $expectedUrl = 'https://www.phpmyfaq.de/docs/4.1';
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
        $configuration = Configuration::getConfigurationInstance();
        $configuration->set('main.referenceURL', 'https://example.com');
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

    public function testGetMissingExtensions(): void
    {
        $system = new System();
        $result = $system->getMissingExtensions();

        $this->assertEquals([], $result);
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
}
