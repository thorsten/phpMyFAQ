<?php

namespace phpMyFAQ\Setup;

use phpMyFAQ\Configuration;
use phpMyFAQ\Core\Exception;
use phpMyFAQ\Database\Sqlite3;
use phpMyFAQ\Enums\DownloadHostType;
use phpMyFAQ\System;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;

class UpgradeTest extends TestCase
{
    private Upgrade $upgrade;
    private HttpClientInterface|MockObject $httpClientMock;

    protected function setUp(): void
    {
        parent::setUp();

        $dbHandle = new Sqlite3();
        $dbHandle->connect(PMF_TEST_DIR . '/test.db', '', '');
        $configuration = new Configuration($dbHandle);

        $this->httpClientMock = $this->createMock(HttpClientInterface::class);
        $this->upgrade = new Upgrade(new System(), $configuration, $this->httpClientMock);
        $this->upgrade->setUpgradeDirectory(PMF_CONTENT_DIR . '/upgrades');
    }

    /**
     * @throws Exception
     */
    public function testDownloadPackageSuccessful(): void
    {
        $response = $this->createMock(ResponseInterface::class);
        $response->method('getStatusCode')->willReturn(200);
        $response->method('getContent')->willReturn('zip-binary-content');

        $this->httpClientMock
            ->expects($this->once())
            ->method('request')
            ->with('GET', $this->isString())
            ->willReturn($response);

        $path = $this->upgrade->downloadPackage('3.1.15');

        $this->assertIsString($path);
        $this->assertFileExists($path);
        $this->assertSame('zip-binary-content', file_get_contents($path));
    }

    /**
     * @throws Exception
     */
    public function testDownloadPackageThrowsOnHttpError(): void
    {
        $response = $this->createMock(ResponseInterface::class);
        $response->method('getStatusCode')->willReturn(404);

        $this->httpClientMock
            ->expects($this->once())
            ->method('request')
            ->willReturn($response);

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Cannot download package (HTTP Status: 404).');

        $this->upgrade->downloadPackage('1.2.3');
    }

    /**
     * @throws Exception
     */
    public function testCheckFilesystemValid(): void
    {
        touch(PMF_CONTENT_DIR . '/core/config/constants.php');

        $this->assertTrue($this->upgrade->checkFilesystem());

        unlink(PMF_CONTENT_DIR . '/core/config/constants.php');
    }

    /**
     * @throws Exception
     */
    public function testCheckFilesystemMissingConfigFiles(): void
    {
        $this->expectException('phpMyFAQ\\Core\\Exception');
        $this->expectExceptionMessage(
            'The files /content/core/config/constant.php and /content/core/config/database.php are missing.'
        );
        $this->upgrade->checkFilesystem();
    }

    public function testGetDownloadHostForNightly(): void
    {
        $this->upgrade->setIsNightly(true);

        $this->assertEquals(DownloadHostType::GITHUB->value, $this->upgrade->getDownloadHost());
    }

    public function testGetDownloadHostForNonNightly(): void
    {
        $this->upgrade->setIsNightly(false);

        $this->assertEquals(DownloadHostType::PHPMYFAQ->value, $this->upgrade->getDownloadHost());
    }

    public function testGetPathForNightly(): void
    {
        $this->upgrade->setIsNightly(true);

        $expectedPath = sprintf(Upgrade::GITHUB_PATH, date(format: 'Y-m-d', timestamp: strtotime('-1 days')));
        $this->assertEquals($expectedPath, $this->upgrade->getPath());
    }

    public function testGetPathForNonNightly(): void
    {
        $this->upgrade->setIsNightly(false);

        $this->assertEquals('', $this->upgrade->getPath());
    }
}
