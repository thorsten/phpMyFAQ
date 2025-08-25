<?php

namespace phpMyFAQ\Captcha;

use Exception;
use phpMyFAQ\Configuration;
use phpMyFAQ\Database\Sqlite3;
use phpMyFAQ\Strings;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

/**
 * Class CaptchaTest
 *
 * @package phpMyFAQ
 */
class BuiltinCaptchaTest extends TestCase
{
    /** @var BuiltinCaptcha */
    protected BuiltinCaptcha $captcha;

    /** @var Configuration */
    protected Configuration $configuration;

    protected function setUp(): void
    {
        parent::setUp();

        Strings::init();

        $_SERVER['HTTP_USER_AGENT'] = 'AwesomeBrowser';
        $_SERVER['REMOTE_ADDR'] = '::1';
        $_SERVER['REQUEST_TIME'] = 1;
        $_SERVER['SCRIPT_NAME'] = 'test-me.php';

        $dbHandle = new Sqlite3();
        $dbHandle->connect(PMF_TEST_DIR . '/test.db', '', '');
        $this->configuration = new Configuration($dbHandle);
        $this->captcha = new BuiltinCaptcha($this->configuration);
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
        $expected = '<img id="captchaImage" class="rounded border" ' .
            'src="./api/captcha" height="50" width="200" alt="Chuck Norris has counted to infinity. Twice.">';
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
        $result = $this->captcha
            ->setUserIsLoggedIn(true)
            ->setUserIsLoggedIn(false);

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
            '<img src=x onerror=alert(1)>'
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
}
