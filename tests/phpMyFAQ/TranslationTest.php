<?php

declare(strict_types=1);

namespace phpMyFAQ;

use FilesystemIterator;
use phpMyFAQ\Core\Exception;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\TestCase;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

#[AllowMockObjectsWithoutExpectations]
class TranslationTest extends TestCase
{
    /**
     * @throws Exception
     */ protected function setUp(): void
    {
        parent::setUp();

        // Prepare a custom translations directory before creating the instance
        $translationsDir = __DIR__ . '/_translations';
        if (!is_dir($translationsDir)) {
            mkdir($translationsDir, 0777, true);
        }

        file_put_contents(
            $translationsDir . '/language_en.php',
            "<?php\n\n"
            . "\$LANG_CONF['main.dateFormat'] = ['select', 'English date format', 'Date format help'];\n"
            . "\$LANG_CONF['main.maintenanceMode'] = ['checkbox', 'Maintenance mode', 'Maintenance help'];\n"
            . "\$LANG_CONF['records.maxAttachmentSize'] = ['text', 'Maximum attachment size: %s', 'Attachment help'];\n\n"
            . "return [\n"
            . "    'test.key' => 'Default Label',\n"
            . "];\n",
        );

        file_put_contents(
            $translationsDir . '/language_de.php',
            "<?php\n\n"
            . "\$LANG_CONF['main.dateFormat'] = ['select', 'Deutsches Datumsformat', 'Datumsformat Hilfe'];\n"
            . "\$LANG_CONF['main.maintenanceMode'] = ['checkbox', 'Wartungsmodus', 'Wartungsmodus Hilfe'];\n"
            . "\$LANG_CONF['records.maxAttachmentSize'] = ['text', 'Maximale Anhangsgr\u00f6\u00dfe: %s', 'Anhang Hilfe'];\n\n"
            . "return [\n"
            . "    'test.key' => '',\n"
            . "    'test.zero' => '0',\n"
            . "];\n",
        );

        Translation::resetInstance();

        // Now create and configure the instance so that init() sees our test directory
        Translation::create()
            ->setTranslationsDir($translationsDir)
            ->setDefaultLanguage('en')
            ->setCurrentLanguage('de');
    }

    public static function tearDownAfterClass(): void
    {
        $translationsDir = __DIR__ . '/_translations';

        if (!is_dir($translationsDir)) {
            return;
        }

        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($translationsDir, FilesystemIterator::SKIP_DOTS),
            RecursiveIteratorIterator::CHILD_FIRST,
        );

        foreach ($iterator as $fileInfo) {
            if ($fileInfo->isDir()) {
                rmdir($fileInfo->getPathname());
            } else {
                unlink($fileInfo->getPathname());
            }
        }

        rmdir($translationsDir);
    }

    public function testGetFallsBackToDefaultWhenCurrentIsEmptyString(): void
    {
        $value = Translation::get('test.key');

        $this->assertSame(
            'Default Label',
            $value,
            'Should fall back to default language when the current language value is an empty string.',
        );
    }

    public function testGetAcceptsZeroStringAndDoesNotFallback(): void
    {
        $value = Translation::get('test.zero');

        $this->assertSame('0', $value, 'String "0" must not be treated as empty and must not trigger a fallback.');
    }

    public function testGetReturnsNullForUnknownKey(): void
    {
        $value = Translation::get('unknown.key');

        $this->assertNull($value, 'Unknown translation keys should return null.');
    }

    public function testHasReturnsTrueForExistingKey(): void
    {
        $this->assertTrue(
            Translation::has('test.key'),
            'has() should return true for keys defined in the current or default language.',
        );
    }

    public function testHasReturnsFalseForUnknownKey(): void
    {
        $this->assertFalse(
            Translation::has('unknown.key'),
            'has() should return false for keys that are not defined in any language.',
        );
    }

    public function testGetDefaultLanguageReturnsConfiguredLanguage(): void
    {
        $this->assertSame('en', Translation::getInstance()->getDefaultLanguage());
    }

    public function testGetConfigurationItemsFormatsAndReordersConfigurationKeys(): void
    {
        $configurationItems = Translation::getConfigurationItems();

        $this->assertSame('main.maintenanceMode', array_key_first($configurationItems));
        $this->assertSame('checkbox', $configurationItems['main.maintenanceMode']['element']);
        $this->assertSame('Wartungsmodus', $configurationItems['main.maintenanceMode']['label']);

        $this->assertStringContainsString(
            'https://www.php.net/manual/en/function.date.php',
            $configurationItems['main.dateFormat']['label'],
        );
        $this->assertStringContainsString('Deutsches Datumsformat', $configurationItems['main.dateFormat']['label']);

        $this->assertStringContainsString(
            (string) ini_get('upload_max_filesize'),
            $configurationItems['records.maxAttachmentSize']['label'],
        );
    }

    public function testGetConfigurationItemsFiltersBySection(): void
    {
        $configurationItems = Translation::getConfigurationItems('main.');

        $this->assertCount(2, $configurationItems);
        $this->assertArrayHasKey('main.maintenanceMode', $configurationItems);
        $this->assertArrayHasKey('main.dateFormat', $configurationItems);
        $this->assertArrayNotHasKey('records.maxAttachmentSize', $configurationItems);
    }

    /**
     * @throws Exception
     */
    public function testRegisterPluginTranslations(): void
    {
        $pluginTranslationsDir = __DIR__ . '/_translations/TestPlugin';
        if (!is_dir($pluginTranslationsDir)) {
            mkdir($pluginTranslationsDir, 0777, true);
        }

        file_put_contents(
            $pluginTranslationsDir . '/language_en.php',
            "<?php\n\n\$PMF_LANG['greeting'] = 'Hello';\n\$PMF_LANG['message'] = 'Welcome!';\n",
        );

        file_put_contents(
            $pluginTranslationsDir . '/language_de.php',
            "<?php\n\n\$PMF_LANG['greeting'] = 'Hallo';\n\$PMF_LANG['message'] = 'Willkommen!';\n",
        );

        Translation::getInstance()->registerPluginTranslations('TestPlugin', $pluginTranslationsDir);

        $greeting = Translation::get('plugin.TestPlugin.greeting');
        $this->assertSame('Hallo', $greeting, 'Should return German translation for current language (de)');

        $message = Translation::get('plugin.TestPlugin.message');
        $this->assertSame('Willkommen!', $message, 'Should return German message for current language (de)');
    }

    /**
     * @throws Exception
     */
    public function testPluginTranslationFallbackToEnglish(): void
    {
        $pluginTranslationsDir = __DIR__ . '/_translations/FallbackPlugin';
        if (!is_dir($pluginTranslationsDir)) {
            mkdir($pluginTranslationsDir, 0777, true);
        }

        file_put_contents(
            $pluginTranslationsDir . '/language_en.php',
            "<?php\n\n\$PMF_LANG['onlyInEnglish'] = 'English only text';\n",
        );

        Translation::getInstance()->registerPluginTranslations('FallbackPlugin', $pluginTranslationsDir);

        $value = Translation::get('plugin.FallbackPlugin.onlyInEnglish');
        $this->assertSame(
            'English only text',
            $value,
            'Should fall back to English when key not found in current language (de)',
        );
    }

    public function testPluginTranslationReturnsNullForUnknownKey(): void
    {
        $value = Translation::get('plugin.NonexistentPlugin.unknownKey');
        $this->assertNull($value, 'Should return null for plugin translation keys that do not exist');
    }

    /**
     * @throws Exception
     */
    public function testPluginTranslationHasReturnsTrue(): void
    {
        $pluginTranslationsDir = __DIR__ . '/_translations/HasPlugin';
        if (!is_dir($pluginTranslationsDir)) {
            mkdir($pluginTranslationsDir, 0777, true);
        }

        file_put_contents(
            $pluginTranslationsDir . '/language_en.php',
            "<?php\n\n\$PMF_LANG['testKey'] = 'Test value';\n",
        );

        Translation::getInstance()->registerPluginTranslations('HasPlugin', $pluginTranslationsDir);

        $this->assertTrue(
            Translation::has('plugin.HasPlugin.testKey'),
            'has() should return true for existing plugin translation keys',
        );
    }

    public function testPluginTranslationHasReturnsFalse(): void
    {
        $this->assertFalse(
            Translation::has('plugin.NonexistentPlugin.unknownKey'),
            'has() should return false for non-existent plugin translation keys',
        );
    }

    /**
     * @throws Exception
     */
    public function testRegisterPluginTranslationsWithNonExistentDirectory(): void
    {
        // Should silently skip without throwing exception
        Translation::getInstance()->registerPluginTranslations('MissingPlugin', '/nonexistent/path');

        // Verify plugin translations were not registered
        $value = Translation::get('plugin.MissingPlugin.anyKey');
        $this->assertNull($value, 'Should return null when plugin directory does not exist');
    }

    /**
     * @throws Exception
     */
    public function testPluginTranslationsDoNotOverrideCore(): void
    {
        // Core translation is 'test.key' => 'Default Label'
        $coreValue = Translation::get('test.key');
        $this->assertSame('Default Label', $coreValue, 'Core translation should work');

        // Plugin translations with 'plugin.' prefix should be isolated
        $pluginValue = Translation::get('plugin.TestPlugin.test.key');
        $this->assertNull($pluginValue, 'Plugin namespace should be isolated from core');
    }
}
