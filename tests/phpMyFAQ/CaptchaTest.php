<?php

/**
 * Captcha Tests
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at https://mozilla.org/MPL/2.0/.
 *
 * @package phpMyFAQ
 * @author Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2021 phpMyFAQ Team
 * @license http://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link https://www.phpmyfaq.de
 * @since 2021-03-14
 */

namespace phpMyFAQ;


use Exception;
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
    /** @var Captcha */
    protected $captcha;

    /** @var Configuration */
    protected $configuration;

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
        $this->captcha = new Captcha($this->configuration);
    }

    /**
     * @testdox validate correctly the captcha code
     * @throws Exception
     */
    public function testValidateCaptchaCode()
    {
        $this->assertFalse($this->captcha->validateCaptchaCode(''));
    }

    /**
     * @testdox render a HTML <img> tag with the captcha image correctly
     */
    public function testRenderCaptchaImage()
    {
        $expected = '<img id="captchaImage" src="test-me.php?action=foobar&amp;gen=img&amp;ck=1" ' .
            'height="40" width="165" alt="Chuck Norris has counted to infinity. Twice.">';
        $this->assertEquals($expected, $this->captcha->renderCaptchaImage('foobar'));
    }

    /**
     * @testdox return true if a user is logged in
     */
    public function testSetUserIsLoggedIn()
    {
        $this->assertFalse($this->captcha->isUserIsLoggedIn());
        $this->captcha->setUserIsLoggedIn(true);
        $this->assertTrue($this->captcha->isUserIsLoggedIn());
    }

    /**
     * @testdox should set a session id and return the class
     */
    public function testSetSessionId()
    {
        $captcha = $this->captcha->setSessionId(4711);
        $this->assertInstanceOf('phpMyFAQ\Captcha', $captcha);
    }

    /**
     * @testdox should return true for validating the captcha code of a logged in user
     */
    public function testCheckCaptchaCode()
    {
        $this->captcha->setUserIsLoggedIn(true);
        $this->assertTrue($this->captcha->checkCaptchaCode('123456'));
    }
}
