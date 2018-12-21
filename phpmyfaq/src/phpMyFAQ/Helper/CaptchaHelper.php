<?php

namespace phpMyFAQ\Helper;

/**
 * Helper class for the default phpMyFAQ captcha.
 *
 *
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at http://mozilla.org/MPL/2.0/.
 *
 * @category  phpMyFAQ
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2010-2018 phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2010-01-11
 */

use phpMyFAQ\Captcha;
use phpMyFAQ\Configuration;
use phpMyFAQ\Helper;

/**
 * Helper to render the default captcha
 *
 * @category  phpMyFAQ
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2010-2018 phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2010-01-11
 */
class CaptchaHelper extends Helper
{
    const FORM_ID = 'captcha';
    const FORM_BUTTON = 'captcha-button';

    /**
     * Constructor.
     *
     * @param Configuration $config
     */
    public function __construct(Configuration $config)
    {
        $this->_config = $config;
    }

    /**
     * Renders the captcha check.
     *
     * @param Captcha $captcha
     * @param string      $action
     * @param string      $legend
     * @param bool        $auth
     *
     * @return string
     */
    public function renderCaptcha(Captcha $captcha, $action, $legend, $auth = false)
    {
        $html = '';

        if (true === $this->_config->get('spam.enableCaptchaCode') && is_null($auth)) {
            $html .= '<div class="form-group row">';
            $html .= sprintf('<label class="col-sm-3 form-control-label">%s</label>', $legend);
            $html .= '    <div class="col-sm-4">';
            $html .= '        <p class="form-control-static">';
            $html .= $captcha->printCaptcha($action);
            $html .= '        </p>';
            $html .= '    </div>';
            $html .= '    <div class="col-sm-5">';
            $html .= '        <div class="input-group">';
            $html .= sprintf(
                '<input type="text" class="form-control" name="%s" id="%s" size="%d" autocomplete="off" required>',
                self::FORM_ID,
                self::FORM_ID,
                $captcha->caplength
            );
            $html .= '            <span class="input-group-btn">';
            $html .= sprintf(
                '<a class="btn btn-primary" id="%s" data-action="%s"><i aria-hidden="true" class="fas fa-refresh"></i></a>',
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
