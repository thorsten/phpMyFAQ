<?php

namespace phpMyFAQ\Captcha\Helper;

use phpMyFAQ\Configuration;

class CaptchaHelper
{
    private static ?CaptchaHelperInterface $instance = null;

    private static Configuration $configuration;

    public static function getInstance(Configuration $configuration): ?CaptchaHelperInterface
    {
        self::$configuration = $configuration;

        if (self::$configuration->get('security.enableGoogleReCaptchaV2')) {
            self::$instance = new GoogleRecaptchaHelper(self::$configuration);
        } else {
            self::$instance = new BuiltinCaptchaHelper(self::$configuration);
        }

        return self::$instance;
    }
}
