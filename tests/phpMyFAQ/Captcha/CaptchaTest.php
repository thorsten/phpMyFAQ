<?php

namespace phpMyFAQ\Captcha;

use phpMyFAQ\Configuration;
use phpMyFAQ\Database\Sqlite3;
use phpMyFAQ\Strings;
use PHPUnit\Framework\TestCase;

class CaptchaTest extends TestCase
{
    private static ?CaptchaInterface $captcha = null;

    protected Configuration $configuration;

    protected function setUp(): void
    {
        parent::setUp();

        Strings::init();

        $dbHandle = new Sqlite3();
        $dbHandle->connect(PMF_TEST_DIR . '/test.db', '', '');
        $this->configuration = new Configuration($dbHandle);
    }
    public function testGetInstanceWithGoogleRecaptchaEnabled(): void
    {
        $this->configuration->set('security.enableGoogleReCaptchaV2', true);

        $captcha = Captcha::getInstance($this->configuration);

        $this->assertInstanceOf(GoogleRecaptcha::class, $captcha);
    }

    public function testGetInstanceWithGoogleRecaptchaDisabled(): void
    {
        $this->configuration->set('security.enableGoogleReCaptchaV2', false);

        $captcha = Captcha::getInstance($this->configuration);

        $this->assertInstanceOf(BuiltinCaptcha::class, $captcha);
    }
}
