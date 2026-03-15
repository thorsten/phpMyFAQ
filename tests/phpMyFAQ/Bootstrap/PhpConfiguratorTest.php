<?php

namespace phpMyFAQ\Bootstrap;

use phpMyFAQ\Configuration;
use phpMyFAQ\Session\RedisSessionHandler;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\PreserveGlobalState;
use PHPUnit\Framework\Attributes\RunInSeparateProcess;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use RuntimeException;

#[AllowMockObjectsWithoutExpectations]
#[CoversClass(PhpConfigurator::class)]
#[UsesClass(RedisSessionHandler::class)]
class PhpConfiguratorTest extends TestCase
{
    /** @var array<string, string|false> */
    private array $iniBackup = [];
    private ReflectionClass $reflection;

    protected function setUp(): void
    {
        parent::setUp();
        $this->reflection = new ReflectionClass(PhpConfigurator::class);

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

        $this->setRedisConfigurator(null);

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

    private function setRedisConfigurator(?callable $redisConfigurator): void
    {
        $property = $this->reflection->getProperty('redisConfigurator');
        $property->setValue(null, $redisConfigurator ?? [RedisSessionHandler::class, 'configure']);
    }

    public function testFixIncludePathEnsuresDotIsPresent(): void
    {
        $originalIncludePath = get_include_path();
        set_include_path('/tmp');

        PhpConfigurator::fixIncludePath();

        try {
            $paths = explode(PATH_SEPARATOR, ini_get('include_path'));
            $this->assertContains('.', $paths);
        } finally {
            set_include_path($originalIncludePath);
        }
    }

    public function testConfigurePcreSetsLimits(): void
    {
        PhpConfigurator::configurePcre();

        $this->assertEquals('100000000', ini_get('pcre.backtrack_limit'));
        $this->assertEquals('100000000', ini_get('pcre.recursion_limit'));
    }

    #[RunInSeparateProcess]
    public function testRegisterErrorHandlersRegistersPhpMyFaqHandlers(): void
    {
        PhpConfigurator::registerErrorHandlers();

        $previousErrorHandler = set_error_handler(static fn(): bool => true);
        restore_error_handler();

        $previousExceptionHandler = set_exception_handler(static function (): void {});
        restore_exception_handler();

        $this->assertSame('\\phpMyFAQ\\Core\\Error::errorHandler', $previousErrorHandler);
        $this->assertSame('\\phpMyFAQ\\Core\\Error::exceptionHandler', $previousExceptionHandler);
    }

    public function testConfigureSessionDefaultsToFilesHandler(): void
    {
        $configuration = $this->createMock(Configuration::class);
        $configuration
            ->method('get')
            ->willReturnMap([
                ['session.handler',  'files'],
                ['session.redisDsn', ''],
                ['session.savePath', ''],
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
                ['session.savePath', ''],
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
                ['session.savePath', ''],
            ]);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Unsupported session handler');

        PhpConfigurator::configureSession($configuration);
    }

    public function testConfigureSessionUsesRedisHandlerWhenConfigured(): void
    {
        $configuration = $this->createMock(Configuration::class);
        $configuration
            ->method('get')
            ->willReturnMap([
                ['session.handler',  'redis'],
                ['session.redisDsn', 'tcp://127.0.0.1:6379?database=1'],
                ['session.savePath', ''],
            ]);

        unset($GLOBALS['__pmf_test_redis_dsn']);
        $this->setRedisConfigurator(static function (string $dsn): void {
            $GLOBALS['__pmf_test_redis_dsn'] = $dsn;
        });

        PhpConfigurator::configureSession($configuration);

        $this->assertSame('tcp://127.0.0.1:6379?database=1', $GLOBALS['__pmf_test_redis_dsn'] ?? null);
    }

    #[RunInSeparateProcess]
    #[PreserveGlobalState(false)]
    public function testConfigureSessionUsesConfiguredSavePath(): void
    {
        $configuration = $this->createMock(Configuration::class);
        $configuration
            ->method('get')
            ->willReturnMap([
                ['session.handler', 'files'],
                ['session.redisDsn', ''],
                ['session.savePath', sys_get_temp_dir() . '/pmf-session-configured-path'],
            ]);

        PhpConfigurator::configureSession($configuration);

        $this->assertSame(sys_get_temp_dir() . '/pmf-session-configured-path', ini_get('session.save_path'));
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
                ['session.savePath', ''],
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
