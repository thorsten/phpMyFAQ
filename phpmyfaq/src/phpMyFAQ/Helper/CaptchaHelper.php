<?php

/**
 * Helper class for the default phpMyFAQ captcha.
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at http://mozilla.org/MPL/2.0/.
 *
 * @package   phpMyFAQ
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2010-2021 phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2010-01-11
 */

namespace phpMyFAQ\Helper;

use phpMyFAQ\Captcha;
use phpMyFAQ\Configuration;
use phpMyFAQ\Helper;

/**
 * Class CaptchaHelper
 *
 * @package phpMyFAQ\Helper
 */
class CaptchaHelper extends Helper
{
    private const FORM_ID = 'captcha';
    private const FORM_BUTTON = 'captcha-button';

    /**
     * Constructor.
     *
     * @param Configuration $config
     */
    public function __construct(Configuration $config)
    {
        $this->config = $config;
    }

    /**
     * Renders the captcha check.
     *
     * @param Captcha $captcha
     * @param string  $action
     * @param string  $legend
     * @param bool    $auth
     * @return string
     */
    public function renderCaptcha(Captcha $captcha, string $action, string $legend, $auth = false): string
    {
        $html = '';

        if (true === $this->config->get('spam.enableCaptchaCode') && is_null($auth)) {
            $html .= '<div class="form-group row">';
            $html .= sprintf('<label class="col-sm-3 col-form-label">%s</label>', $legend);
            $html .= '    <div class="col-sm-4">';
            $html .= '        <p class="form-control-static">';
            $html .= $captcha->renderCaptchaImage($action);
            $html .= '        </p>';
            $html .= '    </div>';
            $html .= '    <div class="col-sm-5">';
            $html .= '        <div class="input-group">';
            $html .= sprintf(
                '<input type="text" class="form-control" name="%s" id="%s" size="%d" autocomplete="off" required>',
                self::FORM_ID,
                self::FORM_ID,
                $captcha->captchaLength
            );
            $html .= '            <span class="input-group-btn">';
            $html .= sprintf(
                '<a class="btn btn-primary" id="%s" data-action="%s">' .
                    '<i aria-hidden="true" class="fa fa-refresh"></i></a>',
                self::FORM_BUTTON,
                $action
            );
            $html .= '            </span>';
            $html .= '        </div>';
            $html .= '    </div>';
            $html .= '</div>';
        }

        return $html;
    }
}
