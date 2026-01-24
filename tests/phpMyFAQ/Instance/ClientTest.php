<?php

namespace phpMyFAQ\Instance;

use phpMyFAQ\Configuration;
use phpMyFAQ\Database\DatabaseDriver;
use phpMyFAQ\Filesystem\Filesystem;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\TestCase;

/**
 * Class ClientTest
 */
#[AllowMockObjectsWithoutExpectations]
class ClientTest extends TestCase
{
    private Client $client;
    private Filesystem $filesystem;
    private Configuration $configuration;

    /**
     * @throws \PHPUnit\Framework\MockObject\Exception
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->filesystem = $this->createMock(Filesystem::class);
        $this->configuration = $this->createStub(Configuration::class);
        $this->client = new Client($this->configuration);
        $this->client->setFileSystem($this->filesystem);
    }

    public function testCreateClientFolder(): void
    {
        $hostname = 'example.com';
        $this->filesystem->method('createDirectory')->willReturn(true);

        $result = $this->client->createClientFolder($hostname);

        $this->assertTrue($result);
    }

    public function testCreateClientTables(): void
    {
        $prefix = 'test_';
        $dbMock = $this->createMock(DatabaseDriver::class);
        $this->configuration->method('getDb')->willReturn($dbMock);

        $dbMock->expects($this->exactly(5))->method('query');

        $this->client->setClientUrl('https://example.com');
        $this->client->createClientTables($prefix);
    }

    public function testCopyConstantsFile(): void
    {
        $destination = '/path/to/destination/constants.php';
        $this->filesystem->method('copy')->willReturn(true);

        $result = $this->client->copyConstantsFile($destination);

        $this->assertTrue($result);
    }

    public function testCopyTemplateFolder(): void
    {
        $destination = '/path/to/destination';
        $templateDir = 'default';

        $this->filesystem->expects($this->once())->method('recursiveCopy');

        $this->client->copyTemplateFolder($destination, $templateDir);
    }

    public function testMoveClientFolder(): void
    {
        $sourceUrl = 'https://source.com';
        $destinationUrl = 'https://destination.com';
        $this->filesystem->method('moveDirectory')->willReturn(true);

        $result = $this->client->moveClientFolder($sourceUrl, $destinationUrl);

        $this->assertTrue($result);
    }

    public function testDeleteClientFolder(): void
    {
        $sourceUrl = 'https://source.com';
        $this->filesystem->method('deleteDirectory')->willReturn(true);

        $result = $this->client->deleteClientFolder($sourceUrl);

        $this->assertTrue($result);
    }
}
