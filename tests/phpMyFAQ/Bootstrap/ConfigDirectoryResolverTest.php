<?php

namespace phpMyFAQ\Bootstrap;

use PHPUnit\Framework\Attributes\CoversClass;
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
    public function testResolveAttachmentsDirDefinesConstant(): void
    {
        ConfigDirectoryResolver::resolveAttachmentsDir('attachments', '/app');

        $this->assertTrue(defined('PMF_ATTACHMENTS_DIR'));
        $this->assertSame('/app' . DIRECTORY_SEPARATOR . 'attachments', PMF_ATTACHMENTS_DIR);
    }
}
