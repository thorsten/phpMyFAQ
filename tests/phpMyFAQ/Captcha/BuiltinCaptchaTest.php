<?php

namespace phpMyFAQ\Captcha;

use Exception;
use phpMyFAQ\Configuration;
use phpMyFAQ\Database\Sqlite3;
use phpMyFAQ\Strings;
use PHPUnit\Framework\TestCase;

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
}
