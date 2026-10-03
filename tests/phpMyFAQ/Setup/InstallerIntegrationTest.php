<?php

declare(strict_types=1);

namespace phpMyFAQ\Setup;

use phpMyFAQ\Configuration;
use phpMyFAQ\Core\Exception;
use phpMyFAQ\Database;
use phpMyFAQ\System;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesNamespace;
use PHPUnit\Framework\TestCase;
use ReflectionProperty;
use Symfony\Component\Filesystem\Filesystem;

/**
 * Exercises the Installer against the checked-out application and a temporary install root.
 * startInstall() is only run against the temporary root: its failure cleanup deletes config
 * files below PMF_ROOT_DIR, and checkInitialRewriteBasePath() rewrites the real .htaccess.
 */
#[AllowMockObjectsWithoutExpectations]
#[CoversClass(Installer::class)]
#[UsesNamespace('phpMyFAQ')]
final class InstallerIntegrationTest extends TestCase
{
    private string $rootDir;

    private ?Configuration $previousConfiguration = null;

    private mixed $previousDatabaseDriver = null;

    private mixed $previousDatabaseType = null;

    private string $previousTablePrefix = '';

    protected function setUp(): void
    {
        $this->rootDir = sys_get_temp_dir() . '/pmf-installer-' . bin2hex(random_bytes(6));
        mkdir($this->rootDir . '/content/core/config', 0777, true);

        $this->previousConfiguration = new ReflectionProperty(Configuration::class, 'configuration')->getValue();
        $this->previousDatabaseDriver = new ReflectionProperty(Database::class, 'databaseDriver')->getValue();
        $this->previousDatabaseType = new ReflectionProperty(Database::class, 'dbType')->getValue();
        $this->previousTablePrefix = Database::getTablePrefix();
    }

    protected function tearDown(): void
    {
        new ReflectionProperty(Configuration::class, 'configuration')->setValue(null, $this->previousConfiguration);
        new ReflectionProperty(Database::class, 'databaseDriver')->setValue(null, $this->previousDatabaseDriver);
        new ReflectionProperty(Database::class, 'dbType')->setValue(null, $this->previousDatabaseType);
        Database::setTablePrefix($this->previousTablePrefix);

        new Filesystem()->remove($this->rootDir);
    }

    public function testStartInstallInstallsIntoTheGivenRootDirectory(): void
    {
        new Installer(new System())->startInstall([
            'dbServer' => $this->rootDir . '/install.db',
            'dbType' => 'pdo_sqlite',
            'dbPort' => null,
            'dbDatabaseName' => '',
            'loginname' => 'admin',
            'password' => 'password',
            'password_retyped' => 'password',
            'rootDir' => $this->rootDir,
            'mainUrl' => 'https://localhost/',
        ]);

        $this->assertFileExists($this->rootDir . '/content/core/config/database.php');
        $this->assertFileExists($this->rootDir . '/install.db');

        $DB = [];
        include $this->rootDir . '/content/core/config/database.php';
        $this->assertSame('pdo_sqlite', $DB['type']);
    }

    public function testStartInstallRefusesARootThatIsAlreadyInstalled(): void
    {
        touch($this->rootDir . '/content/core/config/database.php');

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('already installed');

        new Installer(new System())->startInstall(['rootDir' => $this->rootDir]);
    }

    public function testStartInstallRejectsInvalidCredentialsBeforeTouchingTheDatabase(): void
    {
        try {
            new Installer(new System())->startInstall([
                'dbServer' => $this->rootDir . '/install.db',
                'dbType' => 'pdo_sqlite',
                'loginname' => 'admin',
                'password' => 'short',
                'password_retyped' => 'short',
                'rootDir' => $this->rootDir,
            ]);
            $this->fail('A too short password must be rejected.');
        } catch (Exception $exception) {
            $this->assertNotSame('', $exception->getMessage());
        }

        $this->assertFileDoesNotExist($this->rootDir . '/install.db');
        $this->assertFileDoesNotExist($this->rootDir . '/content/core/config/database.php');
    }

    public function testCheckFilesystemPermissionsAcceptsTheCheckedOutApplication(): void
    {
        $this->assertNull(new Installer(new System())->checkFilesystemPermissions());
    }

    public function testCheckNoncriticalSettingsWarnsWithoutHttps(): void
    {
        $system = $this->createStub(System::class);
        $system->method('getHttpsStatus')->willReturn(false);

        $hints = new Installer($system)->checkNoncriticalSettings();

        $this->assertNotSame([], $hints);
        $this->assertStringContainsString('HTTPS support is not enabled', $hints[0]);
        foreach ($hints as $hint) {
            $this->assertStringContainsString('alert-warning', $hint);
        }
    }

    public function testCheckNoncriticalSettingsStaysSilentAboutHttpsWhenEnabled(): void
    {
        $system = $this->createStub(System::class);
        $system->method('getHttpsStatus')->willReturn(true);

        $this->assertStringNotContainsString('HTTPS', implode('', new Installer($system)->checkNoncriticalSettings()));
    }

    public function testCheckMinimumPhpVersionPassesOnTheRunningInterpreter(): void
    {
        $this->assertTrue(new Installer(new System())->checkMinimumPhpVersion());
    }

    public function testCheckBasicStuffRequiresTheMinimumPhpVersionFirst(): void
    {
        $system = $this->createStub(System::class);
        $system->method('checkDatabase')->willReturn(false);

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('No supported database detected!');

        new Installer($system)->checkBasicStuff();
    }
}
