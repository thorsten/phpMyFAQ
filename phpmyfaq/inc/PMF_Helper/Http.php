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
 * @copyright  2009-2011 phpMyFAQ Team
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