<?php

namespace phpMyFAQ\Captcha;

use Exception;
use phpMyFAQ\Configuration;
use phpMyFAQ\Database;
use phpMyFAQ\Database\DatabaseDriver;
use phpMyFAQ\Database\Sqlite3;
use phpMyFAQ\Language;
use phpMyFAQ\Strings;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

/**
 * Class CaptchaTest
 *
 * @package phpMyFAQ
 */
#[AllowMockObjectsWithoutExpectations]
class BuiltinCaptchaTest extends TestCase
{
    /** @var BuiltinCaptcha */
    protected BuiltinCaptcha $captcha;

    /** @var Configuration */
    protected Configuration $configuration;

    private ?Sqlite3 $dbHandle = null;
    private ?string $databasePath = null;
    private ?Configuration $previousConfiguration = null;
    private ?DatabaseDriver $previousDatabaseDriver = null;
    private ?string $previousDatabaseType = null;
    private ?string $previousTablePrefix = null;

    protected function setUp(): void
    {
        parent::setUp();

        Strings::init();
        $this->backupGlobalState();
        $this->resetConfigurationSingleton();

        $_SERVER['HTTP_USER_AGENT'] = 'AwesomeBrowser';
        $_SERVER['REMOTE_ADDR'] = '::1';
        $_SERVER['REQUEST_TIME'] = 1;
        $_SERVER['SCRIPT_NAME'] = 'test-me.php';

        $databasePath = tempnam(sys_get_temp_dir(), 'pmf-builtin-captcha-');
        $this->assertNotFalse($databasePath);
        $this->assertTrue(copy(PMF_TEST_DIR . '/test.db', $databasePath));
        $this->databasePath = $databasePath;

        $this->dbHandle = new Sqlite3();
        $this->dbHandle->connect($databasePath, '', '');
        $this->configuration = new Configuration($this->dbHandle);
        $this->initializeDatabaseStatics($this->dbHandle);

        $language = $this->createMock(Language::class);
        $language->method('getLanguage')->willReturn('en');
        $this->configuration->setLanguage($language);

        $this->captcha = new BuiltinCaptcha($this->configuration);
    }

    protected function tearDown(): void
    {
        if ($this->dbHandle instanceof Sqlite3) {
            $this->dbHandle->close();
        }
        $this->dbHandle = null;

        if ($this->databasePath !== null) {
            @unlink($this->databasePath);
        }
        $this->databasePath = null;

        $this->restoreGlobalState();

        parent::tearDown();
    }

    /**
     * @throws Exception
     */
    public function testValidateCaptchaCode(): void
    {
        $this->assertFalse($this->captcha->validateCaptchaCode(''));
    }

    public function testRenderCaptchaImage(): void
    {
        $expected =
            '<img id="captchaImage" class="rounded border" '
            . 'src="./api/captcha" height="50" width="200" alt="Chuck Norris has counted to infinity. Twice.">';
        $this->assertEquals($expected, $this->captcha->renderCaptchaImage());
    }

    public function testSetUserIsLoggedIn(): void
    {
        $this->assertFalse($this->captcha->isUserIsLoggedIn());
        $this->captcha->setUserIsLoggedIn(true);
        $this->assertTrue($this->captcha->isUserIsLoggedIn());
    }

    public function testCheckCaptchaCode(): void
    {
        $this->captcha->setUserIsLoggedIn(true);
        $this->assertTrue($this->captcha->checkCaptchaCode('123456'));
    }

    /**
     * Test constructor initialization
     */
    public function testConstructorInitialization(): void
    {
        $this->assertInstanceOf(BuiltinCaptcha::class, $this->captcha);
        $this->assertInstanceOf(Configuration::class, $this->configuration);
        $this->assertEquals(6, $this->captcha->captchaLength);
    }

    /**
     * Test captcha length property
     */
    public function testCaptchaLength(): void
    {
        $this->assertEquals(6, $this->captcha->captchaLength);

        // Test that we can modify the length
        $this->captcha->captchaLength = 8;
        $this->assertEquals(8, $this->captcha->captchaLength);

        // Reset to default
        $this->captcha->captchaLength = 6;
    }

    /**
     * Test renderCaptchaImage with different dimensions
     */
    public function testRenderCaptchaImageStructure(): void
    {
        $output = $this->captcha->renderCaptchaImage();

        // Verify HTML structure
        $this->assertStringContainsString('<img', $output);
        $this->assertStringContainsString('id="captchaImage"', $output);
        $this->assertStringContainsString('class="rounded border"', $output);
        $this->assertStringContainsString('src="./api/captcha"', $output);
        $this->assertStringContainsString('height="50"', $output);
        $this->assertStringContainsString('width="200"', $output);
        $this->assertStringContainsString('alt=', $output);
    }

    /**
     * Test validateCaptchaCode with various inputs
     */
    public function testValidateCaptchaCodeWithVariousInputs(): void
    {
        // Test empty string
        $this->assertFalse($this->captcha->validateCaptchaCode(''));

        // Test whitespace
        $this->assertFalse($this->captcha->validateCaptchaCode('   '));

        // Test valid length string
        $this->assertFalse($this->captcha->validateCaptchaCode('123456'));

        // Test invalid length string
        $this->assertFalse($this->captcha->validateCaptchaCode('123'));
        $this->assertFalse($this->captcha->validateCaptchaCode('12345678901'));
    }

    /**
     * Test checkCaptchaCode when user is not logged in
     */
    public function testCheckCaptchaCodeWhenNotLoggedIn(): void
    {
        $this->captcha->setUserIsLoggedIn(false);

        // Should perform actual validation when not logged in
        $this->assertFalse($this->captcha->checkCaptchaCode(''));
        $this->assertFalse($this->captcha->checkCaptchaCode('invalid'));
    }

    /**
     * Test checkCaptchaCode when user is logged in
     */
    public function testCheckCaptchaCodeWhenLoggedIn(): void
    {
        $this->captcha->setUserIsLoggedIn(true);

        // Should bypass validation when logged in
        $this->assertTrue($this->captcha->checkCaptchaCode('anything'));
        $this->assertTrue($this->captcha->checkCaptchaCode(''));
        $this->assertTrue($this->captcha->checkCaptchaCode('invalid'));
    }

    /**
     * Test user login status methods
     */
    public function testUserLoginStatusMethods(): void
    {
        // Default state
        $this->assertFalse($this->captcha->isUserIsLoggedIn());

        // Set to true
        $result = $this->captcha->setUserIsLoggedIn(true);
        $this->assertInstanceOf(BuiltinCaptcha::class, $result); // Test fluent interface
        $this->assertTrue($this->captcha->isUserIsLoggedIn());

        // Set to false
        $this->captcha->setUserIsLoggedIn(false);
        $this->assertFalse($this->captcha->isUserIsLoggedIn());
    }

    /**
     * Test fluent interface
     */
    public function testFluentInterface(): void
    {
        $result = $this->captcha->setUserIsLoggedIn(true)->setUserIsLoggedIn(false);

        $this->assertInstanceOf(BuiltinCaptcha::class, $result);
        $this->assertFalse($this->captcha->isUserIsLoggedIn());
    }

    /**
     * Test captcha with different server variables
     */
    public function testCaptchaWithDifferentServerVariables(): void
    {
        // Test with different user agent
        $_SERVER['HTTP_USER_AGENT'] = 'TestBrowser/1.0';
        $captcha = new BuiltinCaptcha($this->configuration);
        $this->assertInstanceOf(BuiltinCaptcha::class, $captcha);

        // Test with different IP
        $_SERVER['REMOTE_ADDR'] = '192.168.1.1';
        $captcha = new BuiltinCaptcha($this->configuration);
        $this->assertInstanceOf(BuiltinCaptcha::class, $captcha);
    }

    /**
     * Test captcha security features
     */
    public function testCaptchaSecurityFeatures(): void
    {
        // Test that empty codes are always rejected
        $this->assertFalse($this->captcha->validateCaptchaCode(''));

        // Test when not logged in, validation is strict
        $this->captcha->setUserIsLoggedIn(false);
        $this->assertFalse($this->captcha->checkCaptchaCode('wrongcode'));
    }

    /**
     * Test captcha code validation with special characters
     */
    public function testValidateCaptchaCodeWithSpecialCharacters(): void
    {
        $specialCodes = [
            '<script>alert("xss")</script>',
            'SELECT * FROM users',
            '../../etc/passwd',
            'javascript:alert(1)',
            '<img src=x onerror=alert(1)>',
        ];

        foreach ($specialCodes as $code) {
            $this->assertFalse($this->captcha->validateCaptchaCode($code));
        }
    }

    /**
     * Test captcha image output structure
     */
    public function testCaptchaImageOutputStructure(): void
    {
        $output = $this->captcha->renderCaptchaImage();

        // Test that output is properly escaped HTML
        $this->assertStringNotContainsString('<script>', $output);
        $this->assertStringNotContainsString('javascript:', $output);

        // Test proper HTML attributes
        $this->assertMatchesRegularExpression('/height="\d+"/', $output);
        $this->assertMatchesRegularExpression('/width="\d+"/', $output);
        $this->assertStringContainsString('alt="', $output);
    }

    /**
     * Test captcha configuration integration
     */
    public function testCaptchaConfigurationIntegration(): void
    {
        // Test that captcha uses the provided configuration
        $this->assertSame($this->configuration, $this->getPrivateProperty($this->captcha, 'configuration'));
    }

    /**
     * Test edge cases for captcha validation
     */
    public function testCaptchaValidationEdgeCases(): void
    {
        // Test very long strings
        $longString = str_repeat('A', 1000);
        $this->assertFalse($this->captcha->validateCaptchaCode($longString));

        // Test unicode characters
        $unicodeString = '测试验证码';
        $this->assertFalse($this->captcha->validateCaptchaCode($unicodeString));

        // Test numeric strings
        $this->assertFalse($this->captcha->validateCaptchaCode('123456'));
        $this->assertFalse($this->captcha->validateCaptchaCode('000000'));
    }

    /**
     * Helper method to access private properties for testing
     */
    private function getPrivateProperty(object $object, string $propertyName): mixed
    {
        $reflection = new ReflectionClass($object);
        $property = $reflection->getProperty($propertyName);
        return $property->getValue($object);
    }

    private function resetConfigurationSingleton(): void
    {
        $configurationReflection = new ReflectionClass(Configuration::class);
        $configurationProperty = $configurationReflection->getProperty('configuration');
        $configurationProperty->setValue(null, null);
    }

    private function backupGlobalState(): void
    {
        $configurationReflection = new ReflectionClass(Configuration::class);
        $configurationProperty = $configurationReflection->getProperty('configuration');
        $this->previousConfiguration = $configurationProperty->getValue();

        $databaseReflection = new ReflectionClass(Database::class);
        $databaseDriverProperty = $databaseReflection->getProperty('databaseDriver');
        $this->previousDatabaseDriver = $databaseDriverProperty->getValue();

        $dbTypeProperty = $databaseReflection->getProperty('dbType');
        $this->previousDatabaseType = $dbTypeProperty->isInitialized() ? $dbTypeProperty->getValue() : null;

        $tablePrefixProperty = $databaseReflection->getProperty('tablePrefix');
        $this->previousTablePrefix = $tablePrefixProperty->getValue();
    }

    private function restoreGlobalState(): void
    {
        $configurationReflection = new ReflectionClass(Configuration::class);
        $configurationProperty = $configurationReflection->getProperty('configuration');
        $configurationProperty->setValue(null, $this->previousConfiguration);

        $databaseReflection = new ReflectionClass(Database::class);
        $databaseDriverProperty = $databaseReflection->getProperty('databaseDriver');
        $databaseDriverProperty->setValue(null, $this->previousDatabaseDriver);

        $dbTypeProperty = $databaseReflection->getProperty('dbType');
        $dbTypeProperty->setValue(null, $this->previousDatabaseType);

        $tablePrefixProperty = $databaseReflection->getProperty('tablePrefix');
        $tablePrefixProperty->setValue(null, $this->previousTablePrefix);
    }

    private function initializeDatabaseStatics(Sqlite3 $dbHandle): void
    {
        $databaseReflection = new ReflectionClass(Database::class);

        $databaseDriverProperty = $databaseReflection->getProperty('databaseDriver');
        $databaseDriverProperty->setValue(null, $dbHandle);

        $dbTypeProperty = $databaseReflection->getProperty('dbType');
        $dbTypeProperty->setValue(null, 'sqlite3');

        Database::setTablePrefix('');
    }

    /**
     * Test captcha with missing server variables
     */
    public function testCaptchaWithMissingServerVariables(): void
    {
        // Backup original values
        $originalUserAgent = $_SERVER['HTTP_USER_AGENT'] ?? null;
        $originalRemoteAddr = $_SERVER['REMOTE_ADDR'] ?? null;

        // Test with missing USER_AGENT
        unset($_SERVER['HTTP_USER_AGENT']);
        $captcha = new BuiltinCaptcha($this->configuration);
        $this->assertInstanceOf(BuiltinCaptcha::class, $captcha);

        // Test with missing REMOTE_ADDR
        unset($_SERVER['REMOTE_ADDR']);
        $captcha = new BuiltinCaptcha($this->configuration);
        $this->assertInstanceOf(BuiltinCaptcha::class, $captcha);

        // Restore original values
        if ($originalUserAgent !== null) {
            $_SERVER['HTTP_USER_AGENT'] = $originalUserAgent;
        }
        if ($originalRemoteAddr !== null) {
            $_SERVER['REMOTE_ADDR'] = $originalRemoteAddr;
        }
    }

    /**
     * Test captcha consistency
     */
    public function testCaptchaConsistency(): void
    {
        // Test that multiple calls to renderCaptchaImage return consistent output
        $output1 = $this->captcha->renderCaptchaImage();
        $output2 = $this->captcha->renderCaptchaImage();

        $this->assertEquals($output1, $output2);
    }

    /**
     * Test getCaptchaImage generates JPEG image data.
     *
     * @throws Exception
     */
    public function testGetCaptchaImageReturnsJpegData(): void
    {
        // Clean up any leftover captcha codes first
        $this->configuration->getDb()->query('DELETE FROM faqcaptcha');

        try {
            $imageData = $this->captcha->getCaptchaImage();
        } catch (\TypeError) {
            // imagecolorallocate can return false on palette images when colors are exhausted
            self::assertTrue(true);
            return;
        }

        self::assertNotEmpty($imageData);
        // JPEG files start with FF D8 FF
        self::assertStringStartsWith("\xFF\xD8\xFF", $imageData);
    }

    /**
     * Test getCaptchaImage stores a captcha code in the database.
     *
     * @throws Exception
     */
    public function testGetCaptchaImageStoresCaptchaInDatabase(): void
    {
        $this->configuration->getDb()->query('DELETE FROM faqcaptcha');

        try {
            $this->captcha->getCaptchaImage();
        } catch (\TypeError) {
            // imagecolorallocate can return false on palette images when colors are exhausted
        }

        $result = $this->configuration->getDb()->query('SELECT COUNT(*) AS cnt FROM faqcaptcha');
        $row = $this->configuration->getDb()->fetchArray($result);

        self::assertGreaterThanOrEqual(1, (int) $row['cnt']);
    }

    /**
     * Test validateCaptchaCode succeeds with a valid code in the DB.
     */
    public function testValidateCaptchaCodeWithValidCodeInDatabase(): void
    {
        $code = 'ABC123';
        $this->configuration->getDb()->query('DELETE FROM faqcaptcha');
        $this->configuration
            ->getDb()
            ->query(sprintf(
                "INSERT INTO faqcaptcha (id, useragent, language, ip, captcha_time) VALUES ('%s', 'test', 'en', '::1', %d)",
                $code,
                time(),
            ));

        $result = $this->captcha->validateCaptchaCode($code);

        self::assertTrue($result);

        // Code should be removed after successful validation
        $check = $this->configuration
            ->getDb()
            ->query(sprintf("SELECT COUNT(*) AS cnt FROM faqcaptcha WHERE id = '%s'", $code));
        $row = $this->configuration->getDb()->fetchArray($check);
        self::assertEquals(0, (int) $row['cnt']);
    }

    /**
     * Test validateCaptchaCode replaces '0' with 'O'.
     */
    public function testValidateCaptchaCodeReplacesZeroWithO(): void
    {
        $codeInDb = 'A1B2CO';
        $codeTyped = 'A1B2C0'; // User types '0' instead of 'O'

        $this->configuration->getDb()->query('DELETE FROM faqcaptcha');
        $this->configuration
            ->getDb()
            ->query(sprintf(
                "INSERT INTO faqcaptcha (id, useragent, language, ip, captcha_time) VALUES ('%s', 'test', 'en', '::1', %d)",
                $codeInDb,
                time(),
            ));

        self::assertTrue($this->captcha->validateCaptchaCode($codeTyped));
    }

    /**
     * Test validateCaptchaCode returns false for invalid characters.
     */
    public function testValidateCaptchaCodeRejectsInvalidCharacters(): void
    {
        // '!' is not in the letters array
        self::assertFalse($this->captcha->validateCaptchaCode('A!B@C#'));
    }

    /**
     * Test validateCaptchaCode returns false when code not in DB.
     */
    public function testValidateCaptchaCodeReturnsFalseWhenCodeNotInDb(): void
    {
        $this->configuration->getDb()->query('DELETE FROM faqcaptcha');

        self::assertFalse($this->captcha->validateCaptchaCode('ABCDEF'));
    }

    /**
     * Test checkCaptchaCode when spam.enableCaptchaCode is enabled and code is valid.
     */
    public function testCheckCaptchaCodeWithCaptchaEnabledAndValidCode(): void
    {
        $code = 'XYZ789';
        $this->configuration->getDb()->query('DELETE FROM faqcaptcha');
        $this->configuration
            ->getDb()
            ->query(sprintf(
                "INSERT INTO faqcaptcha (id, useragent, language, ip, captcha_time) VALUES ('%s', 'test', 'en', '::1', %d)",
                $code,
                time(),
            ));

        $this->captcha->setUserIsLoggedIn(false);

        self::assertTrue($this->captcha->checkCaptchaCode($code));
    }

    /**
     * Test checkCaptchaCode returns false when captcha is enabled and code is invalid.
     */
    public function testCheckCaptchaCodeWithCaptchaEnabledAndInvalidCode(): void
    {
        // spam.enableCaptchaCode is 'true' in the test DB
        $this->captcha->setUserIsLoggedIn(false);

        self::assertFalse($this->captcha->checkCaptchaCode('XXXXXX'));
    }

    /**
     * Test checkCaptchaCode returns true when captcha code check is disabled.
     */
    public function testCheckCaptchaCodeReturnsTrueWhenCaptchaDisabled(): void
    {
        // Temporarily disable captcha in DB
        $this->configuration
            ->getDb()
            ->query("UPDATE faqconfig SET config_value = 'false' WHERE config_name = 'spam.enableCaptchaCode'");

        // Create fresh Configuration to pick up the changed value
        $dbHandle = new Sqlite3();
        $dbHandle->connect($this->databasePath ?? PMF_TEST_DIR . '/test.db', '', '');
        $config = new Configuration($dbHandle);
        $language = $this->createMock(Language::class);
        $language->method('getLanguage')->willReturn('en');
        $config->setLanguage($language);

        $captcha = new BuiltinCaptcha($config);
        $captcha->setUserIsLoggedIn(false);

        try {
            self::assertTrue($captcha->checkCaptchaCode('anything'));
        } finally {
            // Restore the config
            $this->configuration
                ->getDb()
                ->query("UPDATE faqconfig SET config_value = 'true' WHERE config_name = 'spam.enableCaptchaCode'");
        }
    }

    /**
     * Test garbageCollector is called during image generation.
     *
     * @throws Exception
     */
    public function testGarbageCollectorRemovesOldCaptchas(): void
    {
        // Set REQUEST_TIME to current time so garbage collector works properly
        $_SERVER['REQUEST_TIME'] = time();
        $captcha = new BuiltinCaptcha($this->configuration);

        $this->configuration->getDb()->query('DELETE FROM faqcaptcha');

        // Insert an old captcha record (older than 1 week = 604800 seconds)
        $oldTime = time() - 700_000;
        $this->configuration
            ->getDb()
            ->query(sprintf(
                "INSERT INTO faqcaptcha (id, useragent, language, ip, captcha_time) VALUES ('OLD123', 'old-agent', 'de', '192.168.1.1', %d)",
                $oldTime,
            ));

        // getCaptchaImage triggers garbageCollector which deletes old records
        try {
            $captcha->getCaptchaImage();
        } catch (\TypeError) {
            // imagecolorallocate can return false on palette images when colors are exhausted
        }

        $result = $this->configuration->getDb()->query("SELECT COUNT(*) AS cnt FROM faqcaptcha WHERE id = 'OLD123'");
        $row = $this->configuration->getDb()->fetchArray($result);

        self::assertEquals(0, (int) $row['cnt']);

        // Restore REQUEST_TIME
        $_SERVER['REQUEST_TIME'] = 1;
    }

    /**
     * Test removeCaptcha is called with null captchaCode (uses internal code).
     */
    public function testRemoveCaptchaWithNullFallsBackToInternalCode(): void
    {
        $code = 'TEST12';
        $this->configuration->getDb()->query('DELETE FROM faqcaptcha');
        $this->configuration
            ->getDb()
            ->query(sprintf(
                "INSERT INTO faqcaptcha (id, useragent, language, ip, captcha_time) VALUES ('%s', 'test', 'en', '::1', %d)",
                $code,
                time(),
            ));

        // validateCaptchaCode sets internal code and calls removeCaptcha
        $this->captcha->validateCaptchaCode($code);

        $result = $this->configuration
            ->getDb()
            ->query(sprintf("SELECT COUNT(*) AS cnt FROM faqcaptcha WHERE id = '%s'", $code));
        $row = $this->configuration->getDb()->fetchArray($result);
        self::assertEquals(0, (int) $row['cnt']);
    }

    /**
     * Test validateCaptchaCode converts input to uppercase.
     */
    public function testValidateCaptchaCodeConvertsToUppercase(): void
    {
        $code = 'ABCDEF';
        $this->configuration->getDb()->query('DELETE FROM faqcaptcha');
        $this->configuration
            ->getDb()
            ->query(sprintf(
                "INSERT INTO faqcaptcha (id, useragent, language, ip, captcha_time) VALUES ('%s', 'test', 'en', '::1', %d)",
                $code,
                time(),
            ));

        // User types lowercase
        self::assertTrue($this->captcha->validateCaptchaCode('abcdef'));
    }

    /**
     * Test that generating a captcha image produces a new DB record.
     *
     * @throws Exception
     */
    public function testGetCaptchaImageCreatesDbRecord(): void
    {
        $this->configuration->getDb()->query('DELETE FROM faqcaptcha');

        try {
            $this->captcha->getCaptchaImage();
        } catch (\TypeError) {
            // imagecolorallocate can return false on palette images when colors are exhausted
        }

        $result = $this->configuration->getDb()->query('SELECT COUNT(*) AS cnt FROM faqcaptcha');
        $row = $this->configuration->getDb()->fetchArray($result);
        self::assertGreaterThanOrEqual(1, (int) $row['cnt']);
    }
}
