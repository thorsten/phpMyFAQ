<?php

namespace phpMyFAQ\Plugin;

use PHPUnit\Framework\TestCase;
use ReflectionClass;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;

require 'MockPlugin.php';
require 'MockPluginEvent.php';

#[AllowMockObjectsWithoutExpectations]
class PluginManagerTest extends TestCase
{
    private $pluginManager;

    protected function setUp(): void
    {
        $this->pluginManager = new PluginManager();
    }

    /**
     * @throws PluginException
     */
    public function testRegisterPlugin(): void
    {
        $this->pluginManager->registerPlugin(MockPlugin::class);
        $this->assertArrayHasKey('mockPlugin', $this->pluginManager->getPlugins());
    }

    public function testLoadPlugins(): void
    {
        $mockPluginPath = __DIR__ . '/MockPlugin.php';
        file_put_contents($mockPluginPath, file_get_contents(__DIR__ . '/MockPlugin.php'));

        $reflection = new ReflectionClass($this->pluginManager);
        $method = $reflection->getMethod('getNamespaceFromFile');
        $namespace = $method->invokeArgs($this->pluginManager, [$mockPluginPath]);

        $this->assertEquals('phpMyFAQ\Plugin', $namespace);
    }

    public function testLoadPluginConfig(): void
    {
        $config = ['option1' => 'value1'];
        $this->pluginManager->loadPluginConfig('mockPlugin', $config);

        $this->assertEquals($config, $this->pluginManager->getPluginConfig('mockPlugin'));
    }

    /**
     * @throws \ReflectionException
     */
    public function testAreDependenciesMet(): void
    {
        $mockPlugin = new MockPlugin($this->pluginManager);

        $reflection = new ReflectionClass($this->pluginManager);
        $method = $reflection->getMethod('areDependenciesMet');
        $result = $method->invokeArgs($this->pluginManager, [$mockPlugin]);

        $this->assertTrue($result);
    }
}
