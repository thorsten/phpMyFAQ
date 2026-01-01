<?php

namespace phpMyFAQ\Plugin;

use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use ReflectionException;

require 'MockPlugin.php';
require 'MockPluginEvent.php';

#[AllowMockObjectsWithoutExpectations]
class PluginManagerTest extends TestCase
{
    private PluginManager $pluginManager;

    protected function setUp(): void
    {
        $this->pluginManager = new PluginManager();
    }

    public function testRegisterPlugin(): void
    {
        $this->pluginManager->registerPlugin(MockPlugin::class);
        $this->assertArrayHasKey('mockPlugin', $this->pluginManager->getPlugins());
    }

    /**
     * @throws ReflectionException
     */ public function testLoadPlugins(): void
    {
        $mockPluginPath = __DIR__ . '/MockPlugin.php';
        file_put_contents($mockPluginPath, file_get_contents(__DIR__ . '/MockPlugin.php'));

        $reflection = new ReflectionClass($this->pluginManager);
        $method = $reflection->getMethod('getNamespaceFromFile');
        $namespace = $method->invokeArgs($this->pluginManager, [$mockPluginPath]);

        $this->assertEquals('phpMyFAQ\Plugin', $namespace);
    }

    /**
     * @throws ReflectionException
     */
    public function testAreDependenciesMet(): void
    {
        $mockPlugin = new MockPlugin();

        $reflection = new ReflectionClass($this->pluginManager);
        $method = $reflection->getMethod('areDependenciesMet');
        $result = $method->invokeArgs($this->pluginManager, [$mockPlugin]);

        $this->assertTrue($result);
    }

    /**
     * @throws ReflectionException
     */
    public function testRegisterPluginStylesheets(): void
    {
        // Create a temporary plugin directory with CSS files
        $pluginDir = sys_get_temp_dir() . '/phpmyfaq_test_plugin_' . uniqid();
        $assetsDir = $pluginDir . '/assets';
        mkdir($assetsDir, 0777, true);

        file_put_contents($assetsDir . '/style.css', '/* test css */');
        file_put_contents($assetsDir . '/admin.css', '/* admin css */');

        // Mock PMF_ROOT_DIR for testing
        if (!defined('PMF_ROOT_DIR')) {
            define('PMF_ROOT_DIR', sys_get_temp_dir() . '/phpmyfaq_root_' . uniqid());
        }
        $testPluginDir = PMF_ROOT_DIR . '/content/plugins/TestPlugin';
        mkdir($testPluginDir . '/assets', 0777, true);
        file_put_contents($testPluginDir . '/assets/style.css', '/* test css */');

        $reflection = new ReflectionClass($this->pluginManager);
        $method = $reflection->getMethod('registerPluginStylesheets');

        $method->invokeArgs($this->pluginManager, ['TestPlugin', ['assets/style.css']]);

        $getAllMethod = $reflection->getMethod('getAllPluginStylesheets');
        $stylesheets = $getAllMethod->invoke($this->pluginManager);

        $this->assertContains('content/plugins/TestPlugin/assets/style.css', $stylesheets);

        // Cleanup
        unlink($testPluginDir . '/assets/style.css');
        rmdir($testPluginDir . '/assets');
        rmdir($testPluginDir);
    }

    /**
     * @throws ReflectionException
     */
    public function testRegisterPluginStylesheetsWithInvalidPath(): void
    {
        $reflection = new ReflectionClass($this->pluginManager);
        $method = $reflection->getMethod('registerPluginStylesheets');

        // Try to register with a path traversal attempt
        $method->invokeArgs($this->pluginManager, ['TestPlugin', ['../../../etc/passwd']]);

        $getAllMethod = $reflection->getMethod('getAllPluginStylesheets');
        $stylesheets = $getAllMethod->invoke($this->pluginManager);

        $this->assertEmpty($stylesheets, 'Should not register stylesheets with invalid/traversal paths');
    }

    public function testGetAllPluginStylesheetsReturnsEmpty(): void
    {
        $stylesheets = $this->pluginManager->getAllPluginStylesheets();
        $this->assertIsArray($stylesheets);
        $this->assertEmpty($stylesheets);
    }

    /**
     * @throws ReflectionException
     */
    public function testGetPluginStylesheetsForSpecificPlugin(): void
    {
        if (!defined('PMF_ROOT_DIR')) {
            define('PMF_ROOT_DIR', sys_get_temp_dir() . '/phpmyfaq_root_' . uniqid());
        }
        $testPluginDir = PMF_ROOT_DIR . '/content/plugins/SpecificPlugin';
        mkdir($testPluginDir . '/assets', 0777, true);
        file_put_contents($testPluginDir . '/assets/custom.css', '/* custom css */');

        $reflection = new ReflectionClass($this->pluginManager);
        $method = $reflection->getMethod('registerPluginStylesheets');
        $method->invokeArgs($this->pluginManager, ['SpecificPlugin', ['assets/custom.css']]);

        $stylesheets = $this->pluginManager->getPluginStylesheets('SpecificPlugin');

        $this->assertIsArray($stylesheets);
        $this->assertContains('content/plugins/SpecificPlugin/assets/custom.css', $stylesheets);

        // Cleanup
        unlink($testPluginDir . '/assets/custom.css');
        rmdir($testPluginDir . '/assets');
        rmdir($testPluginDir);
    }

    public function testGetPluginStylesheetsForNonExistentPlugin(): void
    {
        $stylesheets = $this->pluginManager->getPluginStylesheets('NonExistentPlugin');
        $this->assertIsArray($stylesheets);
        $this->assertEmpty($stylesheets);
    }

    /**
     * @throws ReflectionException
     */
    public function testRegisterPluginScripts(): void
    {
        if (!defined('PMF_ROOT_DIR')) {
            define('PMF_ROOT_DIR', sys_get_temp_dir() . '/phpmyfaq_root_' . uniqid());
        }
        $testPluginDir = PMF_ROOT_DIR . '/content/plugins/ScriptTestPlugin';
        mkdir($testPluginDir . '/assets', 0777, true);
        file_put_contents($testPluginDir . '/assets/script.js', '/* test js */');

        $reflection = new ReflectionClass($this->pluginManager);
        $method = $reflection->getMethod('registerPluginScripts');

        $method->invokeArgs($this->pluginManager, ['ScriptTestPlugin', ['assets/script.js']]);

        $getAllMethod = $reflection->getMethod('getAllPluginScripts');
        $scripts = $getAllMethod->invoke($this->pluginManager);

        $this->assertContains('content/plugins/ScriptTestPlugin/assets/script.js', $scripts);

        // Cleanup
        unlink($testPluginDir . '/assets/script.js');
        rmdir($testPluginDir . '/assets');
        rmdir($testPluginDir);
    }

    /**
     * @throws ReflectionException
     */
    public function testRegisterPluginScriptsWithInvalidPath(): void
    {
        $reflection = new ReflectionClass($this->pluginManager);
        $method = $reflection->getMethod('registerPluginScripts');

        // Try to register with a path traversal attempt
        $method->invokeArgs($this->pluginManager, ['ScriptTestPlugin', ['../../../etc/passwd']]);

        $getAllMethod = $reflection->getMethod('getAllPluginScripts');
        $scripts = $getAllMethod->invoke($this->pluginManager);

        $this->assertEmpty($scripts, 'Should not register scripts with invalid/traversal paths');
    }

    public function testGetAllPluginScriptsReturnsEmpty(): void
    {
        $scripts = $this->pluginManager->getAllPluginScripts();
        $this->assertIsArray($scripts);
        $this->assertEmpty($scripts);
    }

    /**
     * @throws ReflectionException
     */
    public function testGetPluginScriptsForSpecificPlugin(): void
    {
        if (!defined('PMF_ROOT_DIR')) {
            define('PMF_ROOT_DIR', sys_get_temp_dir() . '/phpmyfaq_root_' . uniqid());
        }
        $testPluginDir = PMF_ROOT_DIR . '/content/plugins/SpecificScriptPlugin';
        mkdir($testPluginDir . '/assets', 0777, true);
        file_put_contents($testPluginDir . '/assets/custom.js', '/* custom js */');

        $reflection = new ReflectionClass($this->pluginManager);
        $method = $reflection->getMethod('registerPluginScripts');
        $method->invokeArgs($this->pluginManager, ['SpecificScriptPlugin', ['assets/custom.js']]);

        $scripts = $this->pluginManager->getPluginScripts('SpecificScriptPlugin');

        $this->assertIsArray($scripts);
        $this->assertContains('content/plugins/SpecificScriptPlugin/assets/custom.js', $scripts);

        // Cleanup
        unlink($testPluginDir . '/assets/custom.js');
        rmdir($testPluginDir . '/assets');
        rmdir($testPluginDir);
    }

    public function testGetPluginScriptsForNonExistentPlugin(): void
    {
        $scripts = $this->pluginManager->getPluginScripts('NonExistentPlugin');
        $this->assertIsArray($scripts);
        $this->assertEmpty($scripts);
    }

    public function testGetIncompatiblePluginsReturnsEmpty(): void
    {
        $incompatiblePlugins = $this->pluginManager->getIncompatiblePlugins();
        $this->assertIsArray($incompatiblePlugins);
        $this->assertEmpty($incompatiblePlugins);
    }

    public function testIncompatiblePluginIsTracked(): void
    {
        // Create a mock plugin class that is incompatible
        $incompatiblePluginClass = new class() implements PluginInterface {
            public function getName(): string
            {
                return 'IncompatiblePlugin';
            }

            public function getVersion(): string
            {
                return '0.0.1'; // Very old version, likely incompatible
            }

            public function getDescription(): string
            {
                return 'An incompatible test plugin';
            }

            public function getAuthor(): string
            {
                return 'Test Author';
            }

            public function getDependencies(): array
            {
                return [];
            }

            public function getConfig(): ?PluginConfigurationInterface
            {
                return null;
            }

            public function registerEvents(\Symfony\Component\EventDispatcher\EventDispatcherInterface $dispatcher): void
            {
            }

            public function getStylesheets(): array
            {
                return [];
            }

            public function getTranslationsPath(): ?string
            {
                return null;
            }

            public function getScripts(): array
            {
                return [];
            }
        };

        $className = $incompatiblePluginClass::class;
        $this->pluginManager->registerPlugin($className);

        // Plugin should not be in the regular plugins list
        $plugins = $this->pluginManager->getPlugins();
        $this->assertArrayNotHasKey('IncompatiblePlugin', $plugins);

        // Plugin should be in the incompatible plugins list
        $incompatiblePlugins = $this->pluginManager->getIncompatiblePlugins();
        $this->assertArrayHasKey('IncompatiblePlugin', $incompatiblePlugins);
        $this->assertArrayHasKey('plugin', $incompatiblePlugins['IncompatiblePlugin']);
        $this->assertArrayHasKey('reason', $incompatiblePlugins['IncompatiblePlugin']);
        $this->assertStringContainsString('not compatible', $incompatiblePlugins['IncompatiblePlugin']['reason']);
    }
}
