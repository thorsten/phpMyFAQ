<?php

/**
 * Helper class for the Google Recaptcha captchas.
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
use phpMyFAQ\Configuration;
use phpMyFAQ\Helper;

class GoogleRecaptchaHelper extends Helper implements CaptchaHelperInterface
{
    /**
     * Constructor.
     */
    public function __construct(protected Configuration $config)
    {
    }

    public function renderCaptcha(
        CaptchaInterface $captcha,
        string $action = '',
        string $label = '',
        bool $auth = false
    ): string {
        $html = '';

        if (true === $this->config->get('spam.enableCaptchaCode') && !$auth) {
            $html .= '<div class="row mb-2">';
            $html .= sprintf('<label class="col-sm-3 col-form-label">%s</label>', $label);
            $html .= '    <div class="col-sm-9">';
            $html .= '        <script src="https://www.google.com/recaptcha/api.js" async defer></script>';
            $html .= sprintf(
                '<div class="g-recaptcha" data-sitekey="%s"></div>',
                $this->config->get('security.googleReCaptchaV2SiteKey')
            );
            $html .= '    </div>';
            $html .= '</div>';
        }

        return $html;
    }
}
