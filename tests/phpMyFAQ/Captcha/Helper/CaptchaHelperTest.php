<?php

namespace phpMyFAQ\Captcha\Helper;

use phpMyFAQ\Captcha\BuiltinCaptcha;
use phpMyFAQ\Configuration;
use phpMyFAQ\Database\Sqlite3;
use phpMyFAQ\Strings;
use PHPUnit\Framework\TestCase;

class CaptchaHelperTest extends TestCase
{
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

        $captchaHelper = CaptchaHelper::getInstance($this->configuration);

        $this->assertInstanceOf(GoogleRecaptchaHelper::class, $captchaHelper);
    }

    public function testGetInstanceWithGoogleRecaptchaDisabled(): void
    {
        $this->configuration->set('security.enableGoogleReCaptchaV2', false);

        $captchaHelper = CaptchaHelper::getInstance($this->configuration);

        $this->assertInstanceOf(BuiltinCaptchaHelper::class, $captchaHelper);
    }
}
