<?php
/**
 * Helper class for phpMyFAQ FAQs
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
 * @since     2010-11-12
 */

if (!defined('IS_VALID_PHPMYFAQ')) {
    exit();
}

/**
 * PMF_Helper_Faq
 *
 * @category  phpMyFAQ
 * @package   Helper
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2010-2012 phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      http://www.phpmyfaq.de
 * @since     2010-11-12
 */
class PMF_Helper_Faq extends PMF_Helper
{
    /**
     * Instance
     *
     * @var PMF_Helper_Faq
     */
    private static $instance = null;

    /**
     * Language
     *
     * @var PMF_Language
     */
    private $language = null;

    /**
     * SSL enabled
     *
     * @var boolean
     */
    private $_ssl = false;

    /**
     * Constructor
     *
     * @return PMF_Helper_Faq
     */
    private function __construct(PMF_Configuration $config)
    {
        $this->_config = $config;
    }

    /**
     * Returns the single instance
     *
     * @param PMF_Configuration $config
     *
     * @access static
     * @return PMF_Helper_Faq
     */
    public static function getInstance(PMF_Configuration $config)
    {
        if (null == self::$instance) {
            $className = __CLASS__;
            self::$instance = new $className($config);
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
     * Sets SSL mode
     *
     * @param boolean $ssl
     * @return void
     */
    public function setSsl($ssl)
    {
        $this->_ssl = $ssl;
    }

    /**
     * Returns current SSL mode
     *
     * @return boolean
     */
    public function getSsl()
    {
        return $this->_ssl;
    }

    /**
     * @return string
     */
    public function renderFacebookLikeButton($url)
    {
        if (empty($url) || $this->_config->get('socialnetworks.enableFacebookSupport') == false) {
            return '';
        }

        if ($this->_ssl) {
            $http = 'https://';
        } else {
            $http = 'http://';
        }
                
        return sprintf('<iframe src="%sfacebook.com/plugins/like.php?href=%s&amp;layout=standard&amp;show_faces=true&amp;width=250&amp;action=like&amp;font=arial&amp;colorscheme=light&amp;height=30" scrolling="no" frameborder="0" style="border:none; overflow:hidden; width:250px; height:30px;" allowTransparency="true"></iframe>',
            $http,
            urlencode($url)
        );
    }
}