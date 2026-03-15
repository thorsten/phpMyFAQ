<?php

namespace phpMyFAQ\Bootstrap;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\PreserveGlobalState;
use PHPUnit\Framework\Attributes\RunInSeparateProcess;
use PHPUnit\Framework\TestCase;

#[CoversClass(ConfigDirectoryResolver::class)]
class ConfigDirectoryResolverTest extends TestCase
{
    public function testComputeAttachmentsPathWithAbsoluteUnixPath(): void
    {
        $result = ConfigDirectoryResolver::computeAttachmentsPath('/var/uploads', '/app');

        $this->assertSame('/var/uploads', $result);
    }

    public function testComputeAttachmentsPathWithRelativePath(): void
    {
        $result = ConfigDirectoryResolver::computeAttachmentsPath('attachments', '/app');

        $this->assertSame('/app' . DIRECTORY_SEPARATOR . 'attachments', $result);
    }

    public function testComputeAttachmentsPathWithWindowsAbsolutePath(): void
    {
        $result = ConfigDirectoryResolver::computeAttachmentsPath('C:\\uploads', '/app');

        $this->assertSame('C:\\uploads', $result);
    }

    public function testComputeAttachmentsPathWithEmptyPathReturnsRootDirectorySeparator(): void
    {
        $result = ConfigDirectoryResolver::computeAttachmentsPath('', '/app');

        $this->assertSame('/app' . DIRECTORY_SEPARATOR, $result);
    }

    public function testComputeAttachmentsPathReturnsFalseForTraversalOutsideRoot(): void
    {
        $result = ConfigDirectoryResolver::computeAttachmentsPath('../outside', '/app/root');

        $this->assertFalse($result);
    }

    public function testComputeAttachmentsPathSupportsNestedRelativeSegments(): void
    {
        $result = ConfigDirectoryResolver::computeAttachmentsPath('attachments/images', '/app/root');

        $this->assertSame('/app/root' . DIRECTORY_SEPARATOR . 'attachments/images', $result);
    }

    public function testResolveUsesExistingBootstrapDirectories(): void
    {
        ConfigDirectoryResolver::resolve();

        $this->assertTrue(defined('PMF_CONFIG_DIR'));
        $this->assertStringContainsString('/content/core/config', PMF_CONFIG_DIR);
    }

    public function testResolveDatabaseFilePrefersModernConfigPath(): void
    {
        $_SERVER['REQUEST_URI'] = '/';

        $result = ConfigDirectoryResolver::resolveDatabaseFile();

        $this->assertSame(PMF_CONFIG_DIR . '/database.php', $result);
    }

    public function testResolveDatabaseFileReturnsNullInSetupContextWhenMissing(): void
    {
        $databaseFile = PMF_CONFIG_DIR . '/database.php';
        $backupFile = PMF_CONFIG_DIR . '/database.php.bak';
        rename($databaseFile, $backupFile);
        $_SERVER['REQUEST_URI'] = '/setup/';

        try {
            $this->assertNull(ConfigDirectoryResolver::resolveDatabaseFile());
        } finally {
            rename($backupFile, $databaseFile);
        }
    }

    #[RunInSeparateProcess]
    #[PreserveGlobalState(false)]
    public function testResolveDatabaseFileFallsBackToLegacyConfigPath(): void
    {
        $databaseFile = PMF_CONFIG_DIR . '/database.php';
        $backupFile = PMF_CONFIG_DIR . '/database.php.bak';
        $legacyDir = sys_get_temp_dir() . '/pmf-legacy-config-' . uniqid('', true);
        mkdir($legacyDir, 0777, true);
        file_put_contents($legacyDir . '/database.php', "<?php\nreturn [];\n");
        define('PMF_LEGACY_CONFIG_DIR', $legacyDir);
        $_SERVER['REQUEST_URI'] = '/';
        rename($databaseFile, $backupFile);

        try {
            $this->assertSame($legacyDir . '/database.php', ConfigDirectoryResolver::resolveDatabaseFile());
        } finally {
            rename($backupFile, $databaseFile);
            @unlink($legacyDir . '/database.php');
            @rmdir($legacyDir);
        }
    }

    #[RunInSeparateProcess]
    public function testLoadConfigConstantsLoadsModernConstantsFile(): void
    {
        $constantsFile = PMF_CONFIG_DIR . '/constants.php';
        file_put_contents($constantsFile, "<?php define('PMF_BOOTSTRAP_TEST_CONSTANT', 'modern');");

        try {
            ConfigDirectoryResolver::loadConfigConstants();
        } finally {
            @unlink($constantsFile);
        }

        $this->assertTrue(defined('PMF_BOOTSTRAP_TEST_CONSTANT'));
        $this->assertSame('modern', PMF_BOOTSTRAP_TEST_CONSTANT);
    }

    #[RunInSeparateProcess]
    #[PreserveGlobalState(false)]
    public function testLoadConfigConstantsLoadsLegacyConstantsFileWhenModernFileIsMissing(): void
    {
        $legacyDir = sys_get_temp_dir() . '/pmf-legacy-constants-' . uniqid('', true);
        mkdir($legacyDir, 0777, true);
        file_put_contents($legacyDir . '/constants.php', "<?php define('PMF_BOOTSTRAP_LEGACY_CONSTANT', 'legacy');");
        define('PMF_LEGACY_CONFIG_DIR', $legacyDir);

        ConfigDirectoryResolver::loadConfigConstants();

        $this->assertTrue(defined('PMF_BOOTSTRAP_LEGACY_CONSTANT'));
        $this->assertSame('legacy', PMF_BOOTSTRAP_LEGACY_CONSTANT);

        @unlink($legacyDir . '/constants.php');
        @rmdir($legacyDir);
    }

    #[RunInSeparateProcess]
    public function testResolveAttachmentsDirDefinesConstant(): void
    {
        ConfigDirectoryResolver::resolveAttachmentsDir('attachments', '/app');

        $this->assertTrue(defined('PMF_ATTACHMENTS_DIR'));
        $this->assertSame('/app' . DIRECTORY_SEPARATOR . 'attachments', PMF_ATTACHMENTS_DIR);
    }
}
