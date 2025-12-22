<?php

/**
 * The main Captcha class.
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at https://mozilla.org/MPL/2.0/.
 *
 * @package   phpMyFAQ
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2023-2025 phpMyFAQ Team
 * @license   https://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2023-02-11
 */

declare(strict_types=1);

namespace phpMyFAQ\Captcha;

use phpMyFAQ\Configuration;

class Captcha
{
    private static ?CaptchaInterface $captcha = null;

    private static Configuration $configuration;

    public static function getInstance(Configuration $configuration): BuiltinCaptcha|GoogleRecaptcha
    {
        self::$configuration = $configuration;

        if (self::$configuration->get('security.enableGoogleReCaptchaV2')) {
            self::$captcha = new GoogleRecaptcha(self::$configuration);
        } else {
            self::$captcha = new BuiltinCaptcha(self::$configuration);
        }

        return self::$captcha;
    }
}
