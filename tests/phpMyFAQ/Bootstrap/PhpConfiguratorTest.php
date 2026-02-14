<?php

namespace phpMyFAQ\Bootstrap;

use phpMyFAQ\Configuration;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use RuntimeException;

#[AllowMockObjectsWithoutExpectations]
#[CoversClass(PhpConfigurator::class)]
class PhpConfiguratorTest extends TestCase
{
    /** @var array<string, string|false> */
    private array $iniBackup = [];

    protected function setUp(): void
    {
        parent::setUp();

        if (session_status() === PHP_SESSION_ACTIVE) {
            session_write_close();
        }

        $this->iniBackup = [
            'session.save_handler' => ini_get('session.save_handler'),
            'session.save_path' => ini_get('session.save_path'),
            'session.use_only_cookies' => ini_get('session.use_only_cookies'),
            'session.use_trans_sid' => ini_get('session.use_trans_sid'),
            'session.cookie_samesite' => ini_get('session.cookie_samesite'),
            'session.cookie_httponly' => ini_get('session.cookie_httponly'),
            'session.cookie_secure' => ini_get('session.cookie_secure'),
        ];
    }

    protected function tearDown(): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_write_close();
        }

        foreach ($this->iniBackup as $name => $value) {
            if ($name === 'session.save_handler') {
                continue;
            }

            if ($value !== false) {
                ini_set($name, (string) $value);
            }
        }

        parent::tearDown();
    }

    public function testFixIncludePathEnsuresDotIsPresent(): void
    {
        PhpConfigurator::fixIncludePath();

        $paths = explode(PATH_SEPARATOR, ini_get('include_path'));
        $this->assertContains('.', $paths);
    }

    public function testConfigurePcreSetsLimits(): void
    {
        PhpConfigurator::configurePcre();

        $this->assertEquals('100000000', ini_get('pcre.backtrack_limit'));
        $this->assertEquals('100000000', ini_get('pcre.recursion_limit'));
    }

    public function testConfigureSessionDefaultsToFilesHandler(): void
    {
        $configuration = $this->createMock(Configuration::class);
        $configuration
            ->method('get')
            ->willReturnMap([
                ['session.handler',  'files'],
                ['session.redisDsn', ''],
            ]);

        PhpConfigurator::configureSession($configuration);

        $this->assertEquals('files', ini_get('session.save_handler'));
    }

    public function testConfigureSessionUsesFilesForDatabaseHandlerInLiteMode(): void
    {
        $configuration = $this->createMock(Configuration::class);
        $configuration
            ->method('get')
            ->willReturnMap([
                ['session.handler',  'files'],
                ['session.redisDsn', ''],
            ]);

        PhpConfigurator::configureSession($configuration);

        $this->assertEquals('files', ini_get('session.save_handler'));
    }

    public function testConfigureSessionThrowsForUnsupportedHandler(): void
    {
        $configuration = $this->createMock(Configuration::class);
        $configuration
            ->method('get')
            ->willReturnMap([
                ['session.handler',  'invalid'],
                ['session.redisDsn', ''],
            ]);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Unsupported session handler');

        PhpConfigurator::configureSession($configuration);
    }

    public function testConfigureSessionPersistsDataWithFilesHandler(): void
    {
        $sessionDirectory = sys_get_temp_dir() . '/pmf-session-test-' . uniqid('', true);
        mkdir($sessionDirectory, 0777, true);
        ini_set('session.save_path', $sessionDirectory);

        $configuration = $this->createMock(Configuration::class);
        $configuration
            ->method('get')
            ->willReturnMap([
                ['session.handler',  'files'],
                ['session.redisDsn', ''],
            ]);

        PhpConfigurator::configureSession($configuration);

        session_id('');
        session_start();
        $_SESSION['phase7'] = 'ok';
        $sessionId = session_id();
        session_write_close();

        session_id($sessionId);
        session_start();
        $value = $_SESSION['phase7'] ?? null;
        session_write_close();

        $this->assertSame('ok', $value);

        $sessionFile = $sessionDirectory . '/sess_' . $sessionId;
        if (file_exists($sessionFile)) {
            unlink($sessionFile);
        }
        rmdir($sessionDirectory);
    }
}
