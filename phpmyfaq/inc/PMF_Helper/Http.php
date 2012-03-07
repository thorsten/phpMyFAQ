<?php
/**
 * Helper class for phpMyFAQ categories
 *
 * @package    phpMyFAQ
 * @subpackage PMF_Helper
 * @license    MPL
 * @author     Thorsten Rinne <thorsten@phpmyfaq.de>
 * @since      2009-09-13
 * @version    SVN: $Id$
 * @copyright  2009-2012 phpMyFAQ Team
 *

 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at http://mozilla.org/MPL/2.0/.
 */

if (!defined('IS_VALID_PHPMYFAQ')) {
    exit();
}

/**
 * PMF_Helper
 *
 * @package    phpMyFAQ
 * @subpackage PMF_Helper
 * @author     Thorsten Rinne <thorsten@phpmyfaq.de>
 * @since      2009-09-13
 * @copyright  2009 phpMyFAQ Team
 */
class PMF_Helper_Http extends PMF_Helper 
{
    /**
     * Instance
     *
     * @var PMF_Helper_Http
     */
    private static $instance = null;
    
    /**
     * Content type
     *
     * @var string
     */
    private $contentType = '';
    
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
     * Setter for content type
     *
     * @param string $contentType Content type
     *
     * @return void
     */
    public function setContentType($contentType)
    {
        $this->contentType = $contentType;
    }

    /**
     * Returns the Header
     *
     * @return void
     */
    public function addHeader()
    {
        header('Expires: Thu, 07 Apr 1977 14:47:00 GMT');
        header('Last-Modified: ' . gmdate('D, d M Y H:i:s') . ' GMT');
        header('Cache-Control: no-store, no-cache, must-revalidate');
        header('Cache-Control: post-check=0, pre-check=0', false);
        header('Pragma: no-cache');
        header('Vary: Negotiate,Accept');
        header('Content-type: ' . $this->contentType);
    }
    
    /**
     * Returns a 404 header
     *
     * @return void
     */
    public function printHTTPStatus404()
    {
        if (('cgi' == PMF_String::substr(PHP_SAPI, 0, 3)) || isset($_SERVER['ALL_HTTP'])) {
            header('Status: 404 Not Found');
        } else {
            header('HTTP/1.0 404 Not Found');
        }
        exit();
    }
}