<?php
/**
 * Helper class for phpMyFAQ captchas
 *
 * PHP Version 5.3
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at http://mozilla.org/MPL/2.0/.
 *
 * @category  phpMyFAQ
 * @package   Helper
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2010-2016 phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      http://www.phpmyfaq.de
 * @since     2010-01-11
 */

/**
 * Helper
 *
 * @category  phpMyFAQ
 * @package   Helper
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2010-2016 phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      http://www.phpmyfaq.de
 * @since     2010-01-11
 */
class PMF_Helper_Captcha extends PMF_Helper 
{
    /**
     * Constructor
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
     * Renders the captcha check
     *
     * @param PMF_Captcha $captcha
     * @param string      $action
     * @param string      $legend
     * @param boolean     $auth
     *
     * @return string
     */
    public function renderCaptcha(PMF_Captcha $captcha, $action, $legend, $auth = false)
    {
        $html = '';

        if (true === $this->_config->get('spam.enableCaptchaCode') && is_null($auth)) {
            $html .= '<div class="control-group">';
            $html .= '    <label class="control-label"></label>';
            $html .= '    <div class="controls">';
            $html .= $captcha->printCaptcha($action);
            $html .= '      </div>';
            $html .= '</div>';
            $html .= '<div class="control-group">';
            $html .= sprintf('<label class="control-label">%s</label>', $legend);
            $html .= '    <div class="controls">';
            $html .= '        <div class="input-append">';
            $html .= sprintf(
                '<input type="text" name="captcha" id="captcha" size="%d" required>',
                $captcha->caplength
            );
            $html .= sprintf(
                '<a class="btn" id="captcha-button" data-action="%s"><i class="icon-refresh"></i></a>',
                $action
            );
            $html .= '        </div>';
            $html .= '    </div>';
            $html .= '</div>';
        }
        
        return $html;
    }
       
}