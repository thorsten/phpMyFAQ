<?php

namespace phpMyFAQ\Captcha;

interface CaptchaInterface
{
    public function checkCaptchaCode(string $code): bool;
}
