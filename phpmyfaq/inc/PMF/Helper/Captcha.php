<?php

/**
 * Helper class for the default phpMyFAQ captcha.
 *
 * PHP Version 5.5
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at http://mozilla.org/MPL/2.0/.
 *
 * @category  phpMyFAQ
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2010-2019 phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2010-01-11
 */

/**
 * Helper to render the default captcha
 *
 * @category  phpMyFAQ
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2010-2019 phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2010-01-11
 */
class PMF_Helper_Captcha extends PMF_Helper
{
    const FORM_ID = 'captcha';
    const FORM_BUTTON = 'captcha-button';

    /**
     * Constructor.
     *
     * @param PMF_Configuration $config
     *
     * @return PMF_Helper_Captcha
     */
    public function __construct(PMF_Configuration $config)
    {
        $this->_config = $config;
    }

    /**
     * Renders the captcha check.
     *
     * @param PMF_Captcha $captcha
     * @param string      $action
     * @param string      $legend
     * @param bool        $auth
     *
     * @return string
     */
    public function renderCaptcha(PMF_Captcha $captcha, $action, $legend, $auth = false)
    {
        $html = '';

        if (true === $this->_config->get('spam.enableCaptchaCode') && is_null($auth)) {
            $html .= '<div class="form-group">';
            $html .= sprintf('<label class="col-sm-3 control-label">%s</label>', $legend);
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
                '<a class="btn btn-primary" id="%s" data-action="%s"><i aria-hidden="true" class="fa fa-refresh"></i></a>',
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
