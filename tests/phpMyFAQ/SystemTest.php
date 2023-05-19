<?php

namespace phpMyFAQ;

use phpMyFAQ\Database\DatabaseDriver;
use PHPUnit\Framework\TestCase;

class SystemTest extends TestCase
{
    public function testGetPoweredByString(): void
    {
        $this->assertEquals(
            sprintf('powered with ❤️ and ☕️ by phpMyFAQ %s', System::getVersion()),
            System::getPoweredByString()
        );
    }

    public function testGetPoweredByStringWithLink(): void
    {
        $this->assertEquals(
            sprintf('powered with ❤️ and ☕️ by <a class="link-light text-decoration-none" target="_blank" href="https://www.phpmyfaq.de/">phpMyFAQ</a> %s', System::getVersion()),
            System::getPoweredByString(true)
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
        $database = $this->getMockBuilder(DatabaseDriver::class)
            ->getMock();

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
        $expectedUrl = 'https://www.phpmyfaq.de/docs/3.3';
        $actualUrl = System::getDocumentationUrl();

        $this->assertEquals($expectedUrl, $actualUrl);
    }
}
