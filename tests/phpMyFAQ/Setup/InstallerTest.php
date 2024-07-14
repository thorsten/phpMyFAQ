<?php

namespace phpMyFAQ\Setup;

use phpMyFAQ\Core\Exception;
use phpMyFAQ\System;
use PHPUnit\Framework\TestCase;

class InstallerTest extends TestCase
{
    private System $system;
    private Installer $installer;

    /**
     * @throws \PHPUnit\Framework\MockObject\Exception
     * @throws \Exception
     */
    protected function setUp(): void
    {
        $this->system = $this->createMock(System::class);
        $this->installer = new Installer($this->system);
    }
    public function testCheckBasicStuffThrowsExceptionForMissingDatabase(): void
    {
        $this->system->method('checkDatabase')->willReturn(false);
        $this->system->method('checkRequiredExtensions')->willReturn(true);
        $this->system->method('checkInstallation')->willReturn(true);
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('No supported database detected!');
        $this->installer->checkBasicStuff();
    }

    public function testCheckBasicStuffThrowsExceptionForMissingExtensions(): void
    {
        $this->system->method('checkDatabase')->willReturn(true);
        $this->system->method('checkRequiredExtensions')->willReturn(false);
        $this->system->method('checkInstallation')->willReturn(true);
        $this->system->method('getMissingExtensions')->willReturn(['ext1', 'ext2']);
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Some required PHP extensions are missing: ext1, ext2');
        $this->installer->checkBasicStuff();
    }

    public function testCheckBasicStuffThrowsExceptionForAlreadyInstalled(): void
    {
        $this->system->method('checkDatabase')->willReturn(true);
        $this->system->method('checkRequiredExtensions')->willReturn(true);
        $this->system->method('checkInstallation')->willReturn(false);
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('phpMyFAQ is already installed! Please use the <a href="../update">update</a>.');
        $this->installer->checkBasicStuff();
    }

    public function testAdjustRewriteBaseHtaccessThrowsExceptionForMissingFile(): void
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('The /path/to/.htaccess file does not exist!');
        $this->installer->adjustRewriteBaseHtaccess('/path/to');
    }

    public function testHasLdapSupport(): void
    {
        if (extension_loaded('ldap')) {
            $this->assertTrue($this->installer->hasLdapSupport());
        } else {
            $this->assertFalse($this->installer->hasLdapSupport());
        }
    }

    public function testHasElasticsearchSupport(): void
    {
        if (extension_loaded('curl') && extension_loaded('openssl')) {
            $this->assertTrue($this->installer->hasElasticsearchSupport());
        } else {
            $this->assertFalse($this->installer->hasElasticsearchSupport());
        }
    }
}
