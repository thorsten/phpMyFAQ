<?php

declare(strict_types=1);

namespace phpMyFAQ;

use FilesystemIterator;
use phpMyFAQ\Core\Exception;
use PHPUnit\Framework\TestCase;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;

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
            "<?php\n\nreturn [\n" . "    'test.key' => 'Default Label',\n" . "];\n",
        );

        file_put_contents(
            $translationsDir . '/language_de.php',
            "<?php\n\nreturn [\n" . "    'test.key' => '',\n" . "    'test.zero' => '0',\n" . "];\n",
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
