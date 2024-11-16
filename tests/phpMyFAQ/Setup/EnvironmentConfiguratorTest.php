<?php

namespace phpMyFAQ\Setup;

use phpMyFAQ\Core\Exception;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;

class EnvironmentConfiguratorTest extends TestCase
{
    private EnvironmentConfigurator $configurator;

    public function setUp(): void
    {
        $this->configurator = new EnvironmentConfigurator(dirname(__DIR__, 2));
    }

    protected function tearDown(): void
    {
        Request::setTrustedProxies([], -1);
        Request::setTrustedHosts([]);

        file_put_contents(dirname(__DIR__, 2) . '/.htaccess', 'RewriteBase /phpmyfaq-test/');
    }

    public function testGetRootFilePath(): void
    {
        $this->assertEquals(dirname(__DIR__, 2), $this->configurator->getRootFilePath());
    }

    public function testGetHtaccessPath(): void
    {
        $this->assertEquals(dirname(__DIR__, 2) . '/.htaccess', $this->configurator->getHtaccessPath());
    }

    public function testGetServerPath(): void
    {
        $request = new Request();
        $server = [];
        $server['REQUEST_URI'] = '/';
        $request->initialize([], [], [], [], [], $server);
        $configurator = new EnvironmentConfigurator('/path/to', $request);
        $this->assertEquals('/', $configurator->getServerPath());
    }

    /**
     * @throws Exception
     */
    public function testGetRewriteBase(): void
    {
        $request = new Request();
        $server = [];
        $server['REQUEST_URI'] = '/phpmyfaq-test/';
        $server['HTTP_HOST'] = 'https://localhost/phpmyfaq-test/';
        $server['SERVER_NAME'] = 'https://localhost/phpmyfaq-test/';
        $request->initialize([], [], [], [], [], $server);
        $configurator = new EnvironmentConfigurator(dirname(__DIR__, 2), $request);
        $this->assertEquals('/phpmyfaq-test/', $configurator->getRewriteBase());
    }

    public function testGetServerPathWithSubdirectoryPath(): void
    {
        $request = new Request();
        $server = [];
        $server['REQUEST_URI'] = '/path/info';
        $request->initialize([], [], [], [], [], $server);
        $configurator = new EnvironmentConfigurator('/path/to', $request);
        $this->assertEquals('/path/info', $configurator->getServerPath());
    }

    public function testAdjustRewriteBaseHtaccessThrowsExceptionForMissingFile(): void
    {
        $configurator = new EnvironmentConfigurator('/path/to');
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('The /path/to/.htaccess file does not exist!');
        $configurator->adjustRewriteBaseHtaccess();
    }

    /**
     * @throws Exception
     */
    public function testAdjustRewriteBaseHtaccess(): void
    {
        $request = new Request();
        $server = [];
        $server['REQUEST_URI'] = '/path/info';
        $request->initialize([], [], [], [], [], $server);
        $configurator = new EnvironmentConfigurator(dirname(__DIR__, 2), $request);
        $this->assertTrue($configurator->adjustRewriteBaseHtaccess());
        $this->assertEquals('/path/info', $configurator->getRewriteBase());
    }
}
