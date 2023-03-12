<?php

/**
 * Helper class for the default phpMyFAQ captchas.
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at https://mozilla.org/MPL/2.0/.
 *
 * @package   phpMyFAQ
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2010-2023 phpMyFAQ Team
 * @license   https://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2010-01-11
 */

namespace phpMyFAQ\Captcha\Helper;

use phpMyFAQ\Captcha\BuiltinCaptcha;
use phpMyFAQ\Captcha\CaptchaInterface;
use phpMyFAQ\Configuration;
use phpMyFAQ\Helper;

/**
 * Class CaptchaHelper
 *
 * @package phpMyFAQ\Helper
 */
class BuiltinCaptchaHelper extends Helper implements CaptchaHelperInterface
{
    private const FORM_ID = 'captcha';
    private const FORM_BUTTON = 'captcha-button';

    /**
     * Constructor.
     */
    public function __construct(protected Configuration $config)
    {
    }

    /**
     * Renders the captcha check.
     *
     * @param BuiltinCaptcha $captcha
     */
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
            $html .= '    <div class="col-sm-4">';
            $html .= sprintf('<p class="form-control-static">%s</p>', $captcha->renderCaptchaImage($action));
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
                '<button type="button" class="btn btn-primary" id="%s" data-action="%s">' .
                    '<i aria-hidden="true" class="fa fa-refresh" data-action="%s"></i></button>',
                self::FORM_BUTTON,
                $action,
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
