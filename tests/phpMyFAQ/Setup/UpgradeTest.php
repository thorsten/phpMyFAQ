<?php

namespace phpMyFAQ\Setup;

use FilesystemIterator;
use phpMyFAQ\Configuration;
use phpMyFAQ\Core\Exception;
use phpMyFAQ\Database\Sqlite3;
use phpMyFAQ\Enums\DownloadHostType;
use phpMyFAQ\System;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;

#[AllowMockObjectsWithoutExpectations]
class UpgradeTest extends TestCase
{
    private Upgrade $upgrade;
    private HttpClientInterface $httpClientMock;

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
        $response = $this->createStub(ResponseInterface::class);
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
        $response = $this->createStub(ResponseInterface::class);
        $response->method('getStatusCode')->willReturn(404);

        $this->httpClientMock->expects($this->once())->method('request')->willReturn($response);

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
            'The files /content/core/config/constant.php and /content/core/config/database.php are missing.',
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

        $expectedPath = sprintf(Upgrade::GITHUB_PATH, date(format: 'Y-m-d'));
        $this->assertEquals($expectedPath, $this->upgrade->getPath());
    }

    public function testGetPathForNonNightly(): void
    {
        $this->upgrade->setIsNightly(false);

        $this->assertEquals('', $this->upgrade->getPath());
    }

    /**
     * @throws Exception
     */
    public function testExtractPackageSucceedsForPackageInsideUpgradeDirectory(): void
    {
        $packagePath = PMF_CONTENT_DIR . '/upgrades/valid-package.zip';
        $zip = new \ZipArchive();
        $zip->open($packagePath, \ZipArchive::CREATE | \ZipArchive::OVERWRITE);
        $zip->addFromString('phpmyfaq/test-extract.php', "<?php\n");
        $zip->close();

        $this->assertTrue($this->upgrade->extractPackage($packagePath, function (): void {
        }));
        $this->assertFileExists(PMF_CONTENT_DIR . '/upgrades/new/phpmyfaq/test-extract.php');

        unlink($packagePath);
        unlink(PMF_CONTENT_DIR . '/upgrades/new/phpmyfaq/test-extract.php');
    }

    /**
     * @throws Exception
     */
    public function testExtractPackageThrowsForPathOutsideUpgradeDirectory(): void
    {
        $outsidePath = PMF_TEST_DIR . '/outside-package.zip';
        file_put_contents($outsidePath, 'not-a-real-package');

        try {
            $this->expectException(Exception::class);
            $this->expectExceptionMessage('Given path to download package is outside the upgrade directory.');

            $this->upgrade->extractPackage($outsidePath, function (): void {
            });
        } finally {
            unlink($outsidePath);
        }
    }

    /**
     * @throws Exception
     */
    public function testInstallPackageCopiesAllFilesIntoInstallationDirectory(): void
    {
        $sourceDir = PMF_CONTENT_DIR . '/upgrades/new/phpmyfaq';
        mkdir($sourceDir . '/src/phpMyFAQ', recursive: true);
        file_put_contents($sourceDir . '/index.php', "<?php // new\n");
        file_put_contents($sourceDir . '/src/phpMyFAQ/System.php', "<?php // new\n");

        $installationDir = PMF_TEST_DIR . '/install-target';
        mkdir($installationDir);
        $this->upgrade->setInstallationDirectory($installationDir);

        try {
            $this->assertTrue($this->upgrade->installPackage(function (): void {
            }));
            $this->assertFileExists($installationDir . '/index.php');
            $this->assertFileExists($installationDir . '/src/phpMyFAQ/System.php');
        } finally {
            $this->removeDirectory(PMF_CONTENT_DIR . '/upgrades/new');
            $this->removeDirectory($installationDir);
        }
    }

    /**
     * @throws Exception
     */
    public function testInstallPackageThrowsWhenAFileCannotBeOverwritten(): void
    {
        if (function_exists('posix_geteuid') && posix_geteuid() === 0) {
            $this->markTestSkipped('File permissions are not enforced for the root user.');
        }

        $sourceDir = PMF_CONTENT_DIR . '/upgrades/new/phpmyfaq';
        mkdir($sourceDir, recursive: true);
        file_put_contents($sourceDir . '/locked.php', "<?php // new\n");

        $installationDir = PMF_TEST_DIR . '/install-target-locked';
        mkdir($installationDir);
        file_put_contents($installationDir . '/locked.php', "<?php // old\n");
        chmod($installationDir . '/locked.php', 0o444);

        $this->upgrade->setInstallationDirectory($installationDir);

        try {
            $this->expectException(Exception::class);
            $this->expectExceptionMessage('Could not copy 1 path(s) into the installation directory: locked.php');

            $this->upgrade->installPackage(function (): void {
            });
        } finally {
            chmod($installationDir . '/locked.php', 0o644);
            $this->assertStringEqualsFile($installationDir . '/locked.php', "<?php // old\n");
            $this->removeDirectory(PMF_CONTENT_DIR . '/upgrades/new');
            $this->removeDirectory($installationDir);
        }
    }

    /**
     * @throws Exception
     */
    public function testInstallPackageThrowsWhenExtractedPackageIsMissing(): void
    {
        $this->removeDirectory(PMF_CONTENT_DIR . '/upgrades/new');

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('The extracted package is missing, please run the extract step again.');

        $this->upgrade->installPackage(function (): void {
        });
    }

    /**
     * @throws Exception
     */
    public function testCheckFilesystemThrowsForNonWritableInstallationPath(): void
    {
        if (function_exists('posix_geteuid') && posix_geteuid() === 0) {
            $this->markTestSkipped('File permissions are not enforced for the root user.');
        }

        touch(PMF_CONTENT_DIR . '/core/config/constants.php');

        $installationDir = PMF_TEST_DIR . '/install-target-readonly';
        mkdir($installationDir);
        file_put_contents($installationDir . '/locked.php', "<?php\n");
        chmod($installationDir . '/locked.php', 0o444);

        $this->upgrade->setInstallationDirectory($installationDir);

        try {
            $this->expectException(Exception::class);
            $this->expectExceptionMessage('not writable for the web server');

            $this->upgrade->checkFilesystem();
        } finally {
            chmod($installationDir . '/locked.php', 0o644);
            $this->removeDirectory($installationDir);
            unlink(PMF_CONTENT_DIR . '/core/config/constants.php');
        }
    }

    private function removeDirectory(string $directory): void
    {
        if (!is_dir($directory)) {
            return;
        }

        $files = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($directory, FilesystemIterator::SKIP_DOTS),
            RecursiveIteratorIterator::CHILD_FIRST,
        );

        foreach ($files as $file) {
            $file->isDir() ? rmdir($file->getPathname()) : unlink($file->getPathname());
        }

        rmdir($directory);
    }

    /**
     * @throws Exception
     */
    public function testExtractPackageThrowsForZipSlipEntry(): void
    {
        $packagePath = PMF_CONTENT_DIR . '/upgrades/zip-slip-package.zip';
        $zip = new \ZipArchive();
        $zip->open($packagePath, \ZipArchive::CREATE | \ZipArchive::OVERWRITE);
        $zip->addFromString('../evil.php', "<?php\n");
        $zip->close();

        try {
            $this->expectException(Exception::class);
            $this->expectExceptionMessage('Download package contains an invalid file path.');

            $this->upgrade->extractPackage($packagePath, function (): void {
            });
        } finally {
            unlink($packagePath);
        }
    }
}
