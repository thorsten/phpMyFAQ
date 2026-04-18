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
            . "\$LANG_CONF['oauth2.enable'] = ['checkbox', 'Enable OAuth 2.0 authorization server'];\n"
            . "\$LANG_CONF['oauth2.privateKeyPath'] = ['input', 'Private key path', 'Absolute path to the OAuth 2.0 private key'];\n"
            . "\$LANG_CONF['oauth2.publicKeyPath'] = ['input', 'Public key path', 'Absolute path to the OAuth 2.0 public key'];\n"
            . "\$LANG_CONF['oauth2.encryptionKey'] = ['password', 'Encryption key'];\n"
            . "\$LANG_CONF['oauth2.accessTokenTTL'] = ['input', 'Access token TTL', 'ISO 8601 duration, e.g. PT1H'];\n"
            . "\$LANG_CONF['oauth2.refreshTokenTTL'] = ['input', 'Refresh token TTL', 'ISO 8601 duration, e.g. P1M'];\n"
            . "\$LANG_CONF['oauth2.authCodeTTL'] = ['input', 'Authorization code TTL', 'ISO 8601 duration, e.g. PT10M'];\n\n"
            . "\$LANG_CONF['keycloak.enable'] = ['checkbox', 'Enable Keycloak sign-in'];\n"
            . "\$LANG_CONF['keycloak.baseUrl'] = ['input', 'Keycloak base URL'];\n"
            . "\$LANG_CONF['keycloak.realm'] = ['input', 'Realm'];\n"
            . "\$LANG_CONF['keycloak.clientId'] = ['input', 'Client ID'];\n"
            . "\$LANG_CONF['keycloak.clientSecret'] = ['password', 'Client secret'];\n"
            . "\$LANG_CONF['keycloak.redirectUri'] = ['input', 'Redirect URI'];\n"
            . "\$LANG_CONF['keycloak.scopes'] = ['input', 'Scopes'];\n"
            . "\$LANG_CONF['keycloak.autoProvision'] = ['checkbox', 'Auto provision'];\n"
            . "\$LANG_CONF['keycloak.logoutRedirectUrl'] = ['input', 'Logout redirect URL'];\n\n"
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
            . "\$LANG_CONF['oauth2.enable'] = ['checkbox', 'OAuth-2.0-Autorisierungsserver aktivieren'];\n"
            . "\$LANG_CONF['oauth2.privateKeyPath'] = ['input', 'Pfad zum privaten Schlüssel', 'Absoluter Pfad zum privaten OAuth-2.0-Schlüssel'];\n"
            . "\$LANG_CONF['oauth2.publicKeyPath'] = ['input', 'Pfad zum öffentlichen Schlüssel', 'Absoluter Pfad zum öffentlichen OAuth-2.0-Schlüssel'];\n"
            . "\$LANG_CONF['oauth2.encryptionKey'] = ['password', 'Verschlüsselungsschlüssel'];\n"
            . "\$LANG_CONF['oauth2.accessTokenTTL'] = ['input', 'Access-Token-TTL', 'ISO-8601-Dauer, z.B. PT1H'];\n"
            . "\$LANG_CONF['oauth2.refreshTokenTTL'] = ['input', 'Refresh-Token-TTL', 'ISO-8601-Dauer, z.B. P1M'];\n"
            . "\$LANG_CONF['oauth2.authCodeTTL'] = ['input', 'Autorisierungscode-TTL', 'ISO-8601-Dauer, z.B. PT10M'];\n\n"
            . "\$LANG_CONF['keycloak.enable'] = ['checkbox', 'Keycloak-Anmeldung aktivieren'];\n"
            . "\$LANG_CONF['keycloak.baseUrl'] = ['input', 'Keycloak-Basis-URL'];\n"
            . "\$LANG_CONF['keycloak.realm'] = ['input', 'Realm'];\n"
            . "\$LANG_CONF['keycloak.clientId'] = ['input', 'Client-ID'];\n"
            . "\$LANG_CONF['keycloak.clientSecret'] = ['password', 'Client-Secret'];\n"
            . "\$LANG_CONF['keycloak.redirectUri'] = ['input', 'Redirect-URI'];\n"
            . "\$LANG_CONF['keycloak.scopes'] = ['input', 'Scopes'];\n"
            . "\$LANG_CONF['keycloak.autoProvision'] = ['checkbox', 'Automatisch anlegen'];\n"
            . "\$LANG_CONF['keycloak.logoutRedirectUrl'] = ['input', 'Logout-Redirect-URL'];\n\n"
            . "return [\n"
            . "    'test.key' => '',\n"
            . "    'test.zero' => '0',\n"
            . "];\n",
        );

        Translation::resetInstance();

        // Now create and configure the instance so that init() sees our test directory
        Translation::create()->setTranslationsDir($translationsDir)->setDefaultLanguage('en')->setCurrentLanguage('de');
    }

    public static function tearDownAfterClass(): void
    {
        Translation::resetInstance();

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

    public function testGetConfigurationItemsReturnsOAuth2Section(): void
    {
        $configurationItems = Translation::getConfigurationItems('oauth2.');

        $this->assertCount(7, $configurationItems);
        $this->assertArrayHasKey('oauth2.enable', $configurationItems);
        $this->assertSame('checkbox', $configurationItems['oauth2.enable']['element']);
        $this->assertArrayHasKey('oauth2.privateKeyPath', $configurationItems);
        $this->assertArrayHasKey('oauth2.publicKeyPath', $configurationItems);
        $this->assertArrayHasKey('oauth2.encryptionKey', $configurationItems);
        $this->assertArrayHasKey('oauth2.accessTokenTTL', $configurationItems);
        $this->assertArrayHasKey('oauth2.refreshTokenTTL', $configurationItems);
        $this->assertArrayHasKey('oauth2.authCodeTTL', $configurationItems);
    }

    public function testGetConfigurationItemsReturnsKeycloakSection(): void
    {
        $configurationItems = Translation::getConfigurationItems('keycloak.');

        $this->assertCount(9, $configurationItems);
        $this->assertArrayHasKey('keycloak.enable', $configurationItems);
        $this->assertSame('checkbox', $configurationItems['keycloak.enable']['element']);
        $this->assertArrayHasKey('keycloak.clientSecret', $configurationItems);
        $this->assertSame('password', $configurationItems['keycloak.clientSecret']['element']);
        $this->assertArrayHasKey('keycloak.autoProvision', $configurationItems);
        $this->assertArrayHasKey('keycloak.logoutRedirectUrl', $configurationItems);
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

    public function testGetStringReturnsStringValue(): void
    {
        $value = Translation::getString('test.key');

        $this->assertSame('Default Label', $value);
    }

    public function testGetStringReturnsEmptyStringForUnknownKey(): void
    {
        $value = Translation::getString('unknown.key');

        $this->assertSame('', $value);
    }

    /**
     * @throws Exception
     */
    public function testGetAllReturnsCurrentLanguageTranslations(): void
    {
        $all = Translation::getAll();

        $this->assertIsArray($all);
        $this->assertArrayHasKey('test.key', $all);
    }

    public function testSetMultiByteLanguage(): void
    {
        $translation = Translation::getInstance();
        $translation->setMultiByteLanguage();

        $this->assertSame('UTF-8', mb_internal_encoding());
    }

    public function testGetCurrentLanguage(): void
    {
        $language = Translation::getInstance()->getCurrentLanguage();

        $this->assertSame('de', $language);
    }

    /**
     * @throws Exception
     */
    public function testSetTranslationsDirWithInvalidDirectoryThrowsException(): void
    {
        $this->expectException(Exception::class);

        Translation::getInstance()->setTranslationsDir('/nonexistent/directory');
    }

    /**
     * @throws Exception
     */
    public function testSetDefaultLanguageWithInvalidLanguageThrowsException(): void
    {
        $this->expectException(Exception::class);

        Translation::getInstance()->setDefaultLanguage('xx');
    }

    /**
     * @throws Exception
     */
    public function testHasReturnsTrueForKeyOnlyInDefaultLanguage(): void
    {
        // 'test.key' in de is '' (empty), but in en is 'Default Label'
        // has() checks array_key_exists, so it will find it in current language first
        // We need a key that only exists in the default language
        $translationsDir = __DIR__ . '/_translations';

        file_put_contents(
            $translationsDir . '/language_en.php',
            "<?php\n\n"
            . "\$LANG_CONF['main.dateFormat'] = ['select', 'English date format', 'Date format help'];\n"
            . "\$LANG_CONF['main.maintenanceMode'] = ['checkbox', 'Maintenance mode', 'Maintenance help'];\n"
            . "\$LANG_CONF['records.maxAttachmentSize'] = ['text', 'Maximum attachment size: %s', 'Attachment help'];\n\n"
            . "return [\n"
            . "    'test.key' => 'Default Label',\n"
            . "    'english.only' => 'Only in English',\n"
            . "];\n",
        );

        Translation::resetInstance();
        Translation::create()->setTranslationsDir($translationsDir)->setDefaultLanguage('en')->setCurrentLanguage('de');

        $this->assertTrue(
            Translation::has('english.only'),
            'has() should return true for keys that exist only in the default language.',
        );
    }

    /**
     * @throws Exception
     */
    public function testPluginHasReturnsTrueForKeyInDefaultLanguageOnly(): void
    {
        $pluginTranslationsDir = __DIR__ . '/_translations/DefaultOnlyPlugin';
        if (!is_dir($pluginTranslationsDir)) {
            mkdir($pluginTranslationsDir, 0777, true);
        }

        file_put_contents(
            $pluginTranslationsDir . '/language_en.php',
            "<?php\n\n\$PMF_LANG['defaultKey'] = 'English value';\n",
        );

        Translation::getInstance()->registerPluginTranslations('DefaultOnlyPlugin', $pluginTranslationsDir);

        $this->assertTrue(
            Translation::has('plugin.DefaultOnlyPlugin.defaultKey'),
            'has() should return true for plugin keys existing only in default language',
        );
    }

    /**
     * @throws Exception
     */
    public function testRegisterPluginTranslationsSkipsInvalidFilenames(): void
    {
        $pluginTranslationsDir = __DIR__ . '/_translations/InvalidPlugin';
        if (!is_dir($pluginTranslationsDir)) {
            mkdir($pluginTranslationsDir, 0777, true);
        }

        // File matches the glob pattern (language_*) but not the regex for valid language codes
        file_put_contents($pluginTranslationsDir . '/language_!!!.php', "<?php\n\n\$PMF_LANG['key'] = 'value';\n");

        Translation::getInstance()->registerPluginTranslations('InvalidPlugin', $pluginTranslationsDir);

        $this->assertNull(
            Translation::get('plugin.InvalidPlugin.key'),
            'Should not register translations from files not matching the language code pattern',
        );
    }

    public function testHasReturnsTrueForKeyInCurrentLanguage(): void
    {
        // 'test.zero' exists in de with value '0', so has() should find it in current language
        $this->assertTrue(
            Translation::has('test.zero'),
            'has() should return true for keys that exist in the current language.',
        );
    }

    public function testHasReturnsTrueForKeyInDefaultLanguageOnly(): void
    {
        // 'english.only' only exists in en (default), not in de (current)
        $translationsDir = __DIR__ . '/_translations';

        file_put_contents(
            $translationsDir . '/language_en.php',
            "<?php\n\n"
            . "\$LANG_CONF['main.dateFormat'] = ['select', 'English date format', 'Date format help'];\n"
            . "\$LANG_CONF['main.maintenanceMode'] = ['checkbox', 'Maintenance mode', 'Maintenance help'];\n"
            . "\$LANG_CONF['records.maxAttachmentSize'] = ['text', 'Maximum attachment size: %s', 'Attachment help'];\n\n"
            . "return [\n"
            . "    'test.key' => 'Default Label',\n"
            . "    'default.only' => 'Only in default',\n"
            . "];\n",
        );

        Translation::resetInstance();
        Translation::create()->setTranslationsDir($translationsDir)->setDefaultLanguage('en')->setCurrentLanguage('de');

        // 'default.only' does not exist in de, but exists in en
        $this->assertTrue(
            Translation::has('default.only'),
            'has() should return true for keys only in default language.',
        );
    }

    /**
     * @throws Exception
     */
    public function testPluginHasReturnsTrueForKeyInCurrentLanguage(): void
    {
        $pluginTranslationsDir = __DIR__ . '/_translations/CurrentLangPlugin';
        if (!is_dir($pluginTranslationsDir)) {
            mkdir($pluginTranslationsDir, 0777, true);
        }

        file_put_contents(
            $pluginTranslationsDir . '/language_de.php',
            "<?php\n\n\$PMF_LANG['currentKey'] = 'German value';\n",
        );

        Translation::getInstance()->registerPluginTranslations('CurrentLangPlugin', $pluginTranslationsDir);

        $this->assertTrue(
            Translation::has('plugin.CurrentLangPlugin.currentKey'),
            'has() should return true for plugin keys existing in current language',
        );
    }

    public function testGetReturnsValueFromCurrentLanguageWhenNotEmpty(): void
    {
        // 'test.zero' is '0' in de which is not empty string
        $value = Translation::get('test.zero');

        $this->assertSame('0', $value);
    }

    public function testGetReturnsFallbackFromDefaultLanguage(): void
    {
        // 'test.key' in de is '' (empty), should fall back to en's 'Default Label'
        $value = Translation::get('test.key');

        $this->assertSame('Default Label', $value);
    }

    public function testCreateReturnsSameInstance(): void
    {
        $instance1 = Translation::create();
        $instance2 = Translation::create();

        $this->assertSame($instance1, $instance2);
    }

    public function testGetFallsBackWhenCurrentLanguageNotInLoadedLanguages(): void
    {
        $instance = Translation::getInstance();

        // Set current language to something that has no translations loaded
        // but whose file would be the same as default (so checkCurrentLanguage falls back)
        // Instead, we set it to a language code that doesn't have a file
        // checkLanguageLoaded -> ensureLanguageLoaded -> checkCurrentLanguage resets to default
        // Then the ?? [] at line 99 won't trigger because it falls back to default.

        // Alternative: set currentLanguage to a value that IS loaded (en) but different from default
        // by manipulating the loaded languages to have 'fr' with no 'test.key'
        $loadedRef = new \ReflectionProperty(Translation::class, 'loadedLanguages');
        $loaded = $loadedRef->getValue($instance);
        $loaded['fr'] = ['other.key' => 'French other'];
        $loadedRef->setValue($instance, $loaded);

        $currentRef = new \ReflectionProperty(Translation::class, 'currentLanguage');
        $currentRef->setValue($instance, 'fr');

        // get('test.key') — 'fr' is loaded but doesn't have 'test.key'
        // so it falls through to default language
        $value = Translation::get('test.key');
        $this->assertSame('Default Label', $value);

        // Restore
        $currentRef->setValue($instance, 'de');
        unset($loaded['fr']);
        $loadedRef->setValue($instance, $loaded);
    }

    public function testHasChecksDefaultLanguageWhenKeyNotInCurrentLanguage(): void
    {
        $instance = Translation::getInstance();

        // Set current language to 'fr' which has a loaded but different set of keys
        $loadedRef = new \ReflectionProperty(Translation::class, 'loadedLanguages');
        $loaded = $loadedRef->getValue($instance);
        $loaded['fr'] = ['other.key' => 'French other'];
        $loadedRef->setValue($instance, $loaded);

        $currentRef = new \ReflectionProperty(Translation::class, 'currentLanguage');
        $currentRef->setValue($instance, 'fr');

        // has('test.key') — not in 'fr', but exists in 'en' (default)
        $result = Translation::has('test.key');
        $this->assertTrue($result);

        // Restore
        $currentRef->setValue($instance, 'de');
        unset($loaded['fr']);
        $loadedRef->setValue($instance, $loaded);
    }

    /**
     * @throws Exception
     */
    public function testGetInstanceCreatesNewInstanceWhenNull(): void
    {
        Translation::resetInstance();

        $instance = Translation::getInstance();

        $this->assertInstanceOf(Translation::class, $instance);

        // Re-initialize for other tests
        $translationsDir = __DIR__ . '/_translations';
        $instance->setTranslationsDir($translationsDir)->setDefaultLanguage('en')->setCurrentLanguage('de');
    }

    public function testGetReturnsNullWhenNotInitialized(): void
    {
        // Save the current Configuration instance
        $configRef = new \ReflectionProperty(Configuration::class, 'configuration');
        $savedConfig = $configRef->getValue();

        // Set up a mock Configuration with a logger
        $logger = $this->createMock(\Monolog\Logger::class);
        $logger->method('error');

        $mockConfig = $this->createStub(Configuration::class);
        $mockConfig->method('getLogger')->willReturn($logger);
        $configRef->setValue(null, $mockConfig);

        Translation::resetInstance();

        $instance = Translation::create();

        $reflection = new \ReflectionProperty(Translation::class, 'translationsDir');
        $reflection->setValue($instance, '/nonexistent/path');

        $reflectionReady = new \ReflectionProperty(Translation::class, 'isReady');
        $reflectionReady->setValue($instance, false);

        $value = Translation::get('test.key');

        $this->assertNull($value, 'get() should return null when initialization fails');

        // Restore the original Configuration
        $configRef->setValue(null, $savedConfig);

        // Re-initialize for other tests
        $translationsDir = __DIR__ . '/_translations';
        Translation::resetInstance();
        Translation::create()->setTranslationsDir($translationsDir)->setDefaultLanguage('en')->setCurrentLanguage('de');
    }

    public function testHasReturnsFalseWhenNotInitialized(): void
    {
        Translation::resetInstance();

        $instance = Translation::create();

        $reflection = new \ReflectionProperty(Translation::class, 'translationsDir');
        $reflection->setValue($instance, '/nonexistent/path');

        $reflectionReady = new \ReflectionProperty(Translation::class, 'isReady');
        $reflectionReady->setValue($instance, false);

        $result = Translation::has('test.key');

        $this->assertFalse($result, 'has() should return false when initialization fails');

        // Re-initialize for other tests
        $translationsDir = __DIR__ . '/_translations';
        Translation::resetInstance();
        Translation::create()->setTranslationsDir($translationsDir)->setDefaultLanguage('en')->setCurrentLanguage('de');
    }
}
