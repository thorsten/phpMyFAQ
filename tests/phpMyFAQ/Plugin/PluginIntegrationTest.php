<?php

declare(strict_types=1);

namespace phpMyFAQ\Plugin;

use phpMyFAQ\Translation;
use phpMyFAQ\Core\Exception;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;

#[AllowMockObjectsWithoutExpectations]
class PluginIntegrationTest extends TestCase
{
    private string $testPluginDir;
    private PluginManager $pluginManager;

    /**
     * @throws Exception
     */
    protected function setUp(): void
    {
        parent::setUp();

        // Setup temporary plugin directory
        $this->testPluginDir = sys_get_temp_dir() . '/phpmyfaq_integration_test_' . uniqid();
        mkdir($this->testPluginDir . '/IntegrationTestPlugin/assets', 0777, true);
        mkdir($this->testPluginDir . '/IntegrationTestPlugin/translations', 0777, true);

        // Create CSS files
        file_put_contents(
            $this->testPluginDir . '/IntegrationTestPlugin/assets/style.css',
            '.test-plugin { color: blue; }'
        );

        file_put_contents(
            $this->testPluginDir . '/IntegrationTestPlugin/assets/admin-style.css',
            '.test-plugin-admin { color: red; }'
        );

        // Create translation files
        file_put_contents(
            $this->testPluginDir . '/IntegrationTestPlugin/translations/language_en.php',
            "<?php\n\n\$PMF_LANG['testMessage'] = 'Test message in English';\n"
        );

        file_put_contents(
            $this->testPluginDir . '/IntegrationTestPlugin/translations/language_de.php',
            "<?php\n\n\$PMF_LANG['testMessage'] = 'Testnachricht auf Deutsch';\n"
        );

        // Create a plugin class
        $pluginClass = <<<'PHP'
<?php

namespace phpMyFAQ\Plugin\IntegrationTestPlugin;

use phpMyFAQ\Plugin\PluginInterface;
use phpMyFAQ\Plugin\PluginConfigurationInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class IntegrationTestPluginPlugin implements PluginInterface
{
    public function getName(): string
    {
        return 'IntegrationTestPlugin';
    }

    public function getVersion(): string
    {
        return '0.2.0';
    }

    public function getDescription(): string
    {
        return 'Integration test plugin';
    }

    public function getAuthor(): string
    {
        return 'phpMyFAQ Test';
    }

    public function getDependencies(): array
    {
        return [];
    }

    public function getConfig(): ?PluginConfigurationInterface
    {
        return null;
    }

    public function getStylesheets(): array
    {
        return ['assets/style.css', 'assets/admin-style.css'];
    }

    public function getTranslationsPath(): ?string
    {
        return 'translations';
    }

    public function registerEvents(EventDispatcherInterface $eventDispatcher): void
    {
        // No events for this test
    }
}
PHP;

        file_put_contents(
            $this->testPluginDir . '/IntegrationTestPlugin/IntegrationTestPluginPlugin.php',
            $pluginClass
        );

        // Setup Translation
        $translationsDir = __DIR__ . '/../_translations';
        if (!is_dir($translationsDir)) {
            mkdir($translationsDir, 0777, true);
        }

        file_put_contents(
            $translationsDir . '/language_en.php',
            "<?php\n\nreturn ['core.key' => 'Core value'];\n"
        );

        file_put_contents(
            $translationsDir . '/language_de.php',
            "<?php\n\nreturn ['core.key' => 'Kernwert'];\n"
        );

        Translation::resetInstance();
        Translation::create()
            ->setTranslationsDir($translationsDir)
            ->setDefaultLanguage('en')
            ->setCurrentLanguage('en');

        $this->pluginManager = new PluginManager();
    }

    protected function tearDown(): void
    {
        // Cleanup test plugin directory
        $this->recursiveDelete($this->testPluginDir);
        parent::tearDown();
    }

    private function recursiveDelete(string $dir): void
    {
        if (!is_dir($dir)) {
            return;
        }

        $files = array_diff(scandir($dir), ['.', '..']);
        foreach ($files as $file) {
            $path = $dir . '/' . $file;
            is_dir($path) ? $this->recursiveDelete($path) : unlink($path);
        }
        rmdir($dir);
    }

    /**
     * @throws PluginException
     * @throws Exception
     */
    public function testPluginWithBothCssAndTranslations(): void
    {
        // Load the plugin class
        require_once $this->testPluginDir . '/IntegrationTestPlugin/IntegrationTestPluginPlugin.php';

        // Register plugin
        $this->pluginManager->registerPlugin('phpMyFAQ\Plugin\IntegrationTestPlugin\IntegrationTestPluginPlugin');

        $plugins = $this->pluginManager->getPlugins();
        $this->assertArrayHasKey('IntegrationTestPlugin', $plugins);

        $plugin = $plugins['IntegrationTestPlugin'];

        // Test stylesheet registration
        $stylesheets = $plugin->getStylesheets();
        $this->assertCount(2, $stylesheets);
        $this->assertContains('assets/style.css', $stylesheets);
        $this->assertContains('assets/admin-style.css', $stylesheets);

        // Test translations path
        $translationsPath = $plugin->getTranslationsPath();
        $this->assertEquals('translations', $translationsPath);

        // Test translation registration
        $translationsDir = $this->testPluginDir . '/IntegrationTestPlugin/' . $translationsPath;
        Translation::getInstance()->registerPluginTranslations('IntegrationTestPlugin', $translationsDir);

        $message = Translation::get('plugin.IntegrationTestPlugin.testMessage');
        $this->assertEquals('Test message in English', $message);

        // Verify core translations are not affected
        $coreValue = Translation::get('core.key');
        $this->assertEquals('Core value', $coreValue);
    }

    /**
     * @throws Exception
     */
    public function testPluginTranslationsInMultipleLanguages(): void
    {
        // Switch to German
        Translation::create()->setCurrentLanguage('de');

        $translationsDir = $this->testPluginDir . '/IntegrationTestPlugin/translations';
        Translation::getInstance()->registerPluginTranslations('IntegrationTestPlugin', $translationsDir);

        $message = Translation::get('plugin.IntegrationTestPlugin.testMessage');
        $this->assertEquals('Testnachricht auf Deutsch', $message);
    }
}
