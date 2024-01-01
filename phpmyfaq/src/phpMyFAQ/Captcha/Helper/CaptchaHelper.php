<?php

/**
 * Helper class for the captchas.
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at https://mozilla.org/MPL/2.0/.
 *
 * @package   phpMyFAQ
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2023-2024 phpMyFAQ Team
 * @license   https://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2023-02-11
 */

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
