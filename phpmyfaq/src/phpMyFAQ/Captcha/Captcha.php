<?php

namespace phpMyFAQ\Captcha;

use phpMyFAQ\Configuration;

class Captcha
{
    private static ?CaptchaInterface $instance = null;

    private static Configuration $configuration;

    public static function getInstance(Configuration $configuration): ?CaptchaInterface
    {
        self::$configuration = $configuration;

        if (self::$configuration->get('security.enableGoogleReCaptchaV2')) {
            self::$instance = new GoogleRecaptcha(self::$configuration);
        } else {
            self::$instance = new BuiltinCaptcha(self::$configuration);
        }

        return self::$instance;
    }
}
