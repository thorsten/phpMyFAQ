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
