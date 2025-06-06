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
 * @copyright 2010-2025 phpMyFAQ Team
 * @license   https://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2010-01-11
 */

namespace phpMyFAQ\Captcha\Helper;

use phpMyFAQ\Captcha\BuiltinCaptcha;
use phpMyFAQ\Captcha\CaptchaInterface;
use phpMyFAQ\Configuration;
use phpMyFAQ\Helper\AbstractHelper;

/**
 * Class CaptchaHelper
 *
 * @package phpMyFAQ\Helper
 */
class BuiltinCaptchaAbstractHelper extends AbstractHelper implements CaptchaHelperInterface
{
    private const string FORM_ID = 'captcha';

    private const string FORM_BUTTON = 'captcha-button';

    /**
     * Constructor.
     */
    public function __construct(protected Configuration $configuration)
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

        if (true === $this->configuration->get('spam.enableCaptchaCode') && !$auth) {
            $html .= '<div class="row g-4">';
            $html .= sprintf('<label class="col-md-3 col-sm-12 col-form-label">%s</label>', $label);
            $html .= '    <div class="col-md-4 col-sm-6 col-7">';
            $html .= sprintf('<p class="form-control-static">%s</p>', $captcha->renderCaptchaImage());
            $html .= '    </div>';
            $html .= '    <div class="col-md-5 col-sm-6 col-5">';
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
                    '<i aria-hidden="true" class="bi bi-arrow-repeat" data-action="%s"></i></button>',
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
