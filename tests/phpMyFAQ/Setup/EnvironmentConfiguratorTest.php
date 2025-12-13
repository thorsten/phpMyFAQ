<?php

namespace phpMyFAQ\Setup;

use phpMyFAQ\Configuration;
use phpMyFAQ\Core\Exception;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;

#[AllowMockObjectsWithoutExpectations]
class EnvironmentConfiguratorTest extends TestCase
{
    private Configuration $configuration;
    private EnvironmentConfigurator $configurator;

    /**
     * @throws \PHPUnit\Framework\MockObject\Exception
     */
    public function setUp(): void
    {
        $this->configuration = $this->createStub(Configuration::class);
        $this->configuration->method('getRootPath')->willReturn(dirname(__DIR__, 2));
        $this->configuration->method('getDefaultUrl')->willReturn('https://localhost/');
        $this->configurator = new EnvironmentConfigurator($this->configuration);
    }

    protected function tearDown(): void
    {
        file_put_contents(dirname(__DIR__, 2) . '/.htaccess', 'RewriteBase /phpmyfaq-test/');
    }

    public function testGetHtaccessPath(): void
    {
        $this->assertEquals(dirname(__DIR__, 2) . '/.htaccess', $this->configurator->getHtaccessPath());
    }

    public function testGetServerPath(): void
    {
        $configurator = new EnvironmentConfigurator($this->configuration);
        $this->assertEquals('/', $configurator->getServerPath());
    }

    /**
     * @throws Exception
     */
    public function testGetRewriteBase(): void
    {
        $this->configuration->method('getDefaultUrl')->willReturn('https://localhost/phpmyfaq-test/');
        $configurator = new EnvironmentConfigurator($this->configuration);
        $this->assertEquals('/phpmyfaq-test/', $configurator->getRewriteBase());
    }

    /**
     * @throws \PHPUnit\Framework\MockObject\Exception
     */
    public function testGetServerPathWithSubdirectoryPath(): void
    {
        $configuration = $this->createStub(Configuration::class);
        $configuration->method('getRootPath')->willReturn(dirname(__DIR__, 2) . '/path/info');
        $configuration->method('getDefaultUrl')->willReturn('https://localhost/path/info');
        $configurator = new EnvironmentConfigurator($configuration);
        $this->assertEquals('/path/info', $configurator->getServerPath());
    }

    /**
     * @throws \PHPUnit\Framework\MockObject\Exception
     */
    public function testAdjustRewriteBaseHtaccessThrowsExceptionForMissingFile(): void
    {
        $configuration = $this->createStub(Configuration::class);
        $configuration->method('getRootPath')->willReturn(dirname(__DIR__, 2). '/path/to');
        $configuration->method('getDefaultUrl')->willReturn('https://localhost/path/to');
        $configurator = new EnvironmentConfigurator($configuration);
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('The /path/to/.htaccess file does not exist!');
        $configurator->adjustRewriteBaseHtaccess();
    }

    /**
     * @throws Exception
     * @throws \PHPUnit\Framework\MockObject\Exception
     */
    public function testAdjustRewriteBaseHtaccess(): void
    {
        $configuration = $this->createStub(Configuration::class);
        $configuration->method('getRootPath')->willReturn(dirname(__DIR__, 2));
        $configuration->method('getDefaultUrl')->willReturn('https://localhost/path/info');
        $configurator = new EnvironmentConfigurator($configuration);
        $this->assertTrue($configurator->adjustRewriteBaseHtaccess());
        $this->assertEquals('/path/info', $configurator->getRewriteBase());
    }

    /**
     * @throws Exception
     * @throws \PHPUnit\Framework\MockObject\Exception
     */
    public function testAdjustRewriteBaseHtaccessUpdatesErrorDocumentWithRootPath(): void
    {
        // Set up a proper .htaccess file with ErrorDocument directive
        $htaccessPath = dirname(__DIR__, 2) . '/.htaccess';
        $htaccessContent = <<<'HTACCESS'
<IfModule mod_rewrite.c>
    RewriteEngine On
    RewriteBase /phpmyfaq-test/
    ErrorDocument 404 /phpmyfaq-test/index.php?action=404
</IfModule>
HTACCESS;
        file_put_contents($htaccessPath, $htaccessContent);

        $configuration = $this->createStub(Configuration::class);
        $configuration->method('getRootPath')->willReturn(dirname(__DIR__, 2));
        $configuration->method('getDefaultUrl')->willReturn('https://localhost/');
        $configurator = new EnvironmentConfigurator($configuration);
        $this->assertTrue($configurator->adjustRewriteBaseHtaccess());

        // Read the .htaccess file and verify ErrorDocument 404 is set correctly
        $htaccessContent = file_get_contents($htaccessPath);
        $this->assertStringContainsString('ErrorDocument 404 /index.php?action=404', $htaccessContent);
    }

    /**
     * @throws Exception
     * @throws \PHPUnit\Framework\MockObject\Exception
     */
    public function testAdjustRewriteBaseHtaccessUpdatesErrorDocumentWithSubdirectoryPath(): void
    {
        // Set up a proper .htaccess file with ErrorDocument directive
        $htaccessPath = dirname(__DIR__, 2) . '/.htaccess';
        $htaccessContent = <<<'HTACCESS'
<IfModule mod_rewrite.c>
    RewriteEngine On
    RewriteBase /
    ErrorDocument 404 /index.php?action=404
</IfModule>
HTACCESS;
        file_put_contents($htaccessPath, $htaccessContent);

        $configuration = $this->createStub(Configuration::class);
        $configuration->method('getRootPath')->willReturn(dirname(__DIR__, 2));
        $configuration->method('getDefaultUrl')->willReturn('https://localhost/faq/');
        $configurator = new EnvironmentConfigurator($configuration);
        $this->assertTrue($configurator->adjustRewriteBaseHtaccess());

        // Read the .htaccess file and verify ErrorDocument 404 is set correctly
        $htaccessContent = file_get_contents($htaccessPath);
        $this->assertStringContainsString('ErrorDocument 404 /faq/index.php?action=404', $htaccessContent);
    }
}
