<?php

namespace phpMyFAQ;

use Exception;
use phpMyFAQ\Captcha\BuiltinCaptcha;
use phpMyFAQ\Database\Sqlite3;
use PHPUnit\Framework\TestCase;

/**
 * Class CaptchaTest
 *
 * @testdox Captcha should
 * @package phpMyFAQ
 */
class CaptchaTest extends TestCase
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
     * @testdox validate correctly the captcha code
     * @throws Exception
     */
    public function testValidateCaptchaCode(): void
    {
        $this->assertFalse($this->captcha->validateCaptchaCode(''));
    }

    /**
     * @testdox render a HTML <img> tag with the captcha image correctly
     */
    public function testRenderCaptchaImage(): void
    {
        $expected = '<img id="captchaImage" class="rounded border" ' .
            'src="test-me.php?action=foobar&amp;gen=img&amp;ck=1" ' .
            'height="50" width="200" alt="Chuck Norris has counted to infinity. Twice.">';
        $this->assertEquals($expected, $this->captcha->renderCaptchaImage('foobar'));
    }

    /**
     * @testdox return true if a user is logged in
     */
    public function testSetUserIsLoggedIn(): void
    {
        $this->assertFalse($this->captcha->isUserIsLoggedIn());
        $this->captcha->setUserIsLoggedIn(true);
        $this->assertTrue($this->captcha->isUserIsLoggedIn());
    }

    /**
     * @testdox should set a session id and return the class
     */
    public function testSetSessionId(): void
    {
        $captcha = $this->captcha->setSessionId('sid=4711');
        $this->assertInstanceOf('phpMyFAQ\Captcha\BuiltinCaptcha', $captcha);
    }

    /**
     * @testdox should return true for validating the captcha code of a logged in user
     */
    public function testCheckCaptchaCode(): void
    {
        $this->captcha->setUserIsLoggedIn(true);
        $this->assertTrue($this->captcha->checkCaptchaCode('123456'));
    }
}
