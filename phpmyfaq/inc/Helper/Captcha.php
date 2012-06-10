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
 * @copyright 2010-2012 phpMyFAQ Team
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
 * @copyright 2010-2012 phpMyFAQ Team
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
            $html .= sprintf('<div class="controls"><label>%s</label>', $legend);
            $html .= $captcha->printCaptcha($action);
            $html .= sprintf(
                '<input type="text" name="captcha" id="captcha" class="span2" size="%d" required="required" />',
                $captcha->caplength
            );
            $html .= sprintf(
                '<div class="captchaRefresh"><a href="javascript:;" onclick="refreshCaptcha(\'%s\');">%s</a></div>',
                $action,
                'click to refresh');
            $html .= '</div>';
        }
        
        return $html;
    }
       
}