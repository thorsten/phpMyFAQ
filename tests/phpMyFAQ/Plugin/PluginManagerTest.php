<?php

namespace phpMyFAQ\Plugin;

use PHPUnit\Framework\TestCase;

require 'MockPlugin.php';
require 'MockPluginEvent.php';

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
        // Simulate loading plugins from directory
        $mockPluginPath = __DIR__ . '/MockPlugin.php';
        file_put_contents($mockPluginPath, file_get_contents(__DIR__ . '/MockPlugin.php'));

        // Use reflection to call private method getNamespaceFromFile
        $reflection = new \ReflectionClass($this->pluginManager);
        $method = $reflection->getMethod('getNamespaceFromFile');
        $method->setAccessible(true);
        $namespace = $method->invokeArgs($this->pluginManager, [$mockPluginPath]);

        $this->assertEquals('phpMyFAQ\Plugin', $namespace);
    }

    public function testLoadPluginConfig(): void
    {
        $config = ['option1' => 'value1'];
        $this->pluginManager->loadPluginConfig('mockPlugin', $config);

        $this->assertEquals($config, $this->pluginManager->getPluginConfig('mockPlugin'));
    }

    public function testAreDependenciesMet(): void
    {
        $mockPlugin = new MockPlugin($this->pluginManager);

        // Use reflection to call private method areDependenciesMet
        $reflection = new \ReflectionClass($this->pluginManager);
        $method = $reflection->getMethod('areDependenciesMet');
        $method->setAccessible(true);
        $result = $method->invokeArgs($this->pluginManager, [$mockPlugin]);

        $this->assertTrue($result);
    }
}
