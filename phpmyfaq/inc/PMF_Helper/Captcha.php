<?php
/**
 * Helper class for phpMyFAQ captchas
 * 
 * PHP Version 5.2
 *
 * The contents of this file are subject to the Mozilla Public License
 * Version 1.1 (the "License"); you may not use this file except in
 * compliance with the License. You may obtain a copy of the License at
 * http://www.mozilla.org/MPL/
 *
 * Software distributed under the License is distributed on an "AS IS"
 * basis, WITHOUT WARRANTY OF ANY KIND, either express or implied. See the
 * License for the specific language governing rights and limitations
 * under the License.
 *
 * @category  phpMyFAQ
 * @package   PMF_Helper
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2010-2012 phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/MPL-1.1.html Mozilla Public License Version 1.1
 * @link      http://www.phpmyfaq.de
 * @since     2010-01-11
 */

/**
 * PMF_Helper
 * 
 * @category  phpMyFAQ
 * @package   PMF_Helper
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2010-2012 phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/MPL-1.1.html Mozilla Public License Version 1.1
 * @link      http://www.phpmyfaq.de
 * @since     2010-01-11
 */
class PMF_Helper_Captcha extends PMF_Helper 
{
    /**
     * Instance
     * 
     * @var PMF_Helper_Search
     */
    private static $instance = null;
    
    /**
     * Constructor
     * 
     * @return 
     */
    private function __construct()
    {
        
    }
    
    /**
     * Returns the single instance
     *
     * @access static
     * @return PMF_Helper_Category
     */
    public static function getInstance()
    {
        if (null == self::$instance) {
            $className = __CLASS__;
            self::$instance = new $className();
        }
        return self::$instance;
    }
   
    /**
     * __clone() Magic method to prevent cloning
     * 
     * @return void
     */
    private function __clone()
    {
        
    }

    /**
     * Renders the main navigation
     *
     * @param string $legend Text of the HTML Legend element
     * @param string $img    HTML code for the Captcha image
     * @param string $error  Error message
     * 
     * @return string
     */
    public function renderCaptcha(PMF_Captcha $captcha, $action, $legend, $error = '')
    {
        $html = '';
        
        if (PMF_Configuration::getInstance()->get('spam.enableCaptchaCode')) {
            if ($error != '') {
                $html .= sprintf('<div class="alert alert-error">%s</div>', $error);
            }
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