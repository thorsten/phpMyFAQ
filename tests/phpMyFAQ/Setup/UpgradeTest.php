<?php

namespace phpMyFAQ\Setup;

use Monolog\Logger;
use phpMyFAQ\Configuration;
use phpMyFAQ\Core\Exception;
use phpMyFAQ\Database\Sqlite3;
use phpMyFAQ\Enums\DownloadHostType;
use phpMyFAQ\System;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;
use ZipArchive;

#[AllowMockObjectsWithoutExpectations]
class UpgradeTest extends TestCase
{
    private Upgrade $upgrade;
    private HttpClientInterface $httpClientMock;
    private string $testDir;

    protected function setUp(): void
    {
        parent::setUp();

        $dbHandle = new Sqlite3();
        $dbHandle->connect(PMF_TEST_DIR . '/test.db', '', '');
        $configuration = new Configuration($dbHandle);

        $this->httpClientMock = $this->createMock(HttpClientInterface::class);
        $this->upgrade = new Upgrade(new System(), $configuration, $this->httpClientMock);
        $this->upgrade->setUpgradeDirectory(PMF_CONTENT_DIR . '/upgrades');

        // Setup test directory for Zip Slip tests
        $this->testDir = sys_get_temp_dir() . '/zip_slip_test_' . uniqid();
        mkdir($this->testDir);
        mkdir($this->testDir . '/extract');
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        // Clean up test files
        if (is_dir($this->testDir)) {
            $this->recursiveDelete($this->testDir);
        }
    }

    private function recursiveDelete(string $dir): void
    {
        if (!is_dir($dir)) {
            return;
        }

        $items = scandir($dir);
        foreach ($items as $item) {
            if ($item === '.' || $item === '..') {
                continue;
            }

            $path = $dir . '/' . $item;
            if (is_dir($path)) {
                $this->recursiveDelete($path);
            } else {
                unlink($path);
            }
        }
        rmdir($dir);
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

    // Zip Slip vulnerability tests

    public function testIsPathSafeRejectsDotDotSlash(): void
    {
        $reflection = new ReflectionClass($this->upgrade);
        $method = $reflection->getMethod('isPathSafe');

        $result = $method->invoke($this->upgrade, '../../../etc/passwd', $this->testDir . '/extract/');
        $this->assertFalse($result);
    }

    public function testIsPathSafeRejectsDotDot(): void
    {
        $reflection = new ReflectionClass($this->upgrade);
        $method = $reflection->getMethod('isPathSafe');

        $result = $method->invoke($this->upgrade, 'subdir/../../etc/passwd', $this->testDir . '/extract/');
        $this->assertFalse($result);
    }

    public function testIsPathSafeRejectsAbsolutePath(): void
    {
        $reflection = new ReflectionClass($this->upgrade);
        $method = $reflection->getMethod('isPathSafe');

        $result = $method->invoke($this->upgrade, '/etc/passwd', $this->testDir . '/extract/');
        $this->assertFalse($result);
    }

    public function testIsPathSafeRejectsWindowsAbsolutePath(): void
    {
        $reflection = new ReflectionClass($this->upgrade);
        $method = $reflection->getMethod('isPathSafe');

        $result = $method->invoke($this->upgrade, 'C:\\Windows\\System32\\evil.exe', $this->testDir . '/extract/');
        $this->assertFalse($result);
    }

    public function testIsPathSafeAcceptsSafePath(): void
    {
        $reflection = new ReflectionClass($this->upgrade);
        $method = $reflection->getMethod('isPathSafe');

        $result = $method->invoke($this->upgrade, 'subdir/file.txt', $this->testDir . '/extract/');
        $this->assertTrue($result);
    }

    public function testIsPathSafeAcceptsSafeNestedPath(): void
    {
        $reflection = new ReflectionClass($this->upgrade);
        $method = $reflection->getMethod('isPathSafe');

        $result = $method->invoke($this->upgrade, 'a/b/c/d/file.txt', $this->testDir . '/extract/');
        $this->assertTrue($result);
    }

    public function testIsPathSafeRemovesNullBytes(): void
    {
        $reflection = new ReflectionClass($this->upgrade);
        $method = $reflection->getMethod('isPathSafe');

        $result = $method->invoke($this->upgrade, "file.txt\0.php", $this->testDir . '/extract/');
        // Should still be safe after null byte removal
        $this->assertTrue($result);
    }

    public function testSecureExtractZipRejectsMaliciousArchive(): void
    {
        // Create a malicious ZIP archive
        $zipPath = $this->testDir . '/malicious.zip';
        $zip = new ZipArchive();
        $zip->open($zipPath, ZipArchive::CREATE);

        // Add a file with directory traversal
        $zip->addFromString('../../../evil.txt', 'malicious content');
        $zip->close();

        // Try to extract
        $zip->open($zipPath);

        $reflection = new ReflectionClass($this->upgrade);
        $method = $reflection->getMethod('secureExtractZip');

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Malicious path detected in archive');

        $method->invoke($this->upgrade, $zip, $this->testDir . '/extract/');
    }

    public function testSecureExtractZipAllowsSafeArchive(): void
    {
        // Create a safe ZIP archive
        $zipPath = $this->testDir . '/safe.zip';
        $zip = new ZipArchive();
        $zip->open($zipPath, ZipArchive::CREATE);

        // Add safe files
        $zip->addFromString('file1.txt', 'content 1');
        $zip->addFromString('subdir/file2.txt', 'content 2');
        $zip->close();

        // Extract
        $zip->open($zipPath);

        $reflection = new ReflectionClass($this->upgrade);
        $method = $reflection->getMethod('secureExtractZip');

        $method->invoke($this->upgrade, $zip, $this->testDir . '/extract/');
        $zip->close();

        // Verify files were extracted
        $this->assertFileExists($this->testDir . '/extract/file1.txt');
        $this->assertFileExists($this->testDir . '/extract/subdir/file2.txt');
        $this->assertEquals('content 1', file_get_contents($this->testDir . '/extract/file1.txt'));
        $this->assertEquals('content 2', file_get_contents($this->testDir . '/extract/subdir/file2.txt'));
    }

    public function testSecureExtractZipDoesNotEscapeDirectory(): void
    {
        // Create a malicious ZIP that tries to escape
        $zipPath = $this->testDir . '/escape.zip';
        $zip = new ZipArchive();
        $zip->open($zipPath, ZipArchive::CREATE);

        // Try various escape techniques
        $zip->addFromString('../../outside.txt', 'should not be here');
        $zip->addFromString('a/../../../outside2.txt', 'should not be here');
        $zip->close();

        // Try to extract
        $zip->open($zipPath);

        $reflection = new ReflectionClass($this->upgrade);
        $method = $reflection->getMethod('secureExtractZip');

        try {
            $method->invoke($this->upgrade, $zip, $this->testDir . '/extract/');
            $this->fail('Should have thrown an exception');
        } catch (Exception $e) {
            $this->assertStringContainsString('Malicious path detected', $e->getMessage());
        }

        $zip->close();

        // Verify files were NOT created outside the extract directory
        $this->assertFileDoesNotExist($this->testDir . '/outside.txt');
        $this->assertFileDoesNotExist($this->testDir . '/outside2.txt');
    }
}
