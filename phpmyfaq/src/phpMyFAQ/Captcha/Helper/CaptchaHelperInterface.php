<?php

/**
 * Interface for the helper classes for captchas.
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at https://mozilla.org/MPL/2.0/.
 *
 * @package   phpMyFAQ
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2023 phpMyFAQ Team
 * @license   https://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2023-02-07
 */

namespace phpMyFAQ\Captcha\Helper;

use phpMyFAQ\Captcha\CaptchaInterface;

interface CaptchaHelperInterface
{
    public function renderCaptcha(
        CaptchaInterface $captcha,
        string $action = '',
        string $label = '',
        bool $auth = false
    ): string;
}
