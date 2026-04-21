<?php

namespace phpMyFAQ\Instance;

use phpMyFAQ\Configuration;
use phpMyFAQ\Database\DatabaseDriver;
use phpMyFAQ\Filesystem\Filesystem;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\MockObject\Exception;
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
     * @throws Exception
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->filesystem = $this->createMock(Filesystem::class);
        $this->configuration = $this->createStub(Configuration::class);
        $this->client = new class($this->configuration) extends Client {
            public function isMultiSiteWriteable(): bool
            {
                return true;
            }
        };
        $this->client->setFileSystem($this->filesystem);
    }

    public function testCreateClientFolder(): void
    {
        $hostname = 'example.com';
        $this->filesystem
            ->expects($this->once())
            ->method('createDirectory')
            ->with($this->stringEndsWith('/multisite/example.com'))
            ->willReturn(true);

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
        $this->filesystem
            ->expects($this->once())
            ->method('moveDirectory')
            ->with(
                $this->stringEndsWith('/multisite/source.com'),
                $this->stringEndsWith('/multisite/destination.com'),
            )
            ->willReturn(true);

        $result = $this->client->moveClientFolder($sourceUrl, $destinationUrl);

        $this->assertTrue($result);
    }

    public function testDeleteClientFolder(): void
    {
        $sourceUrl = 'https://source.com';
        $this->filesystem
            ->expects($this->once())
            ->method('deleteDirectory')
            ->with($this->stringEndsWith('/multisite/source.com'))
            ->willReturn(true);

        $result = $this->client->deleteClientFolder($sourceUrl);

        $this->assertTrue($result);
    }

    public function testCreateClientFolderRejectsInvalidHostname(): void
    {
        $this->filesystem->expects($this->never())->method('createDirectory');

        $this->assertFalse($this->client->createClientFolder('../../../tmp/poc'));
    }

    public function testMoveClientFolderRejectsTraversalSourceUrl(): void
    {
        $this->filesystem->expects($this->never())->method('moveDirectory');

        $this->assertFalse($this->client->moveClientFolder('https://../../../tmp/poc', 'https://destination.com'));
    }

    public function testDeleteClientFolderRejectsTraversalSourceUrl(): void
    {
        $this->filesystem->expects($this->never())->method('deleteDirectory');

        $this->assertFalse($this->client->deleteClientFolder('https://../../../tmp/poc'));
    }

    public function testIsValidClientUrlAcceptsValidHttpsUrl(): void
    {
        $this->assertTrue($this->client->isValidClientUrl('https://example.com'));
        $this->assertTrue($this->client->isValidClientUrl('https://example.com/'));
    }

    public function testIsValidClientUrlRejectsInvalidOrDangerousUrls(): void
    {
        $this->assertFalse($this->client->isValidClientUrl('http://example.com'));
        $this->assertFalse($this->client->isValidClientUrl('https://../../../tmp/poc'));
        $this->assertFalse($this->client->isValidClientUrl('https://example.com/../../tmp/poc'));
        $this->assertFalse($this->client->isValidClientUrl('https://example.com?foo=bar'));
    }
}
