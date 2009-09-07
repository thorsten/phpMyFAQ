<?php
/**
 * Helper class for phpMyFAQ search
 *
 * @package    phpMyFAQ
 * @subpackage PMF_Helper
 * @license    MPL
 * @author     Thorsten Rinne <thorsten@phpmyfaq.de>
 * @since      2009-09-07
 * @version    SVN: $Id$
 * @copyright  2009 phpMyFAQ Team
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

/**
 * PMF_Helper
 * 
 * @package    phpMyFAQ
 * @subpackage PMF_Helper
 * @author     Thorsten Rinne <thorsten@phpmyfaq.de>
 * @since      2009-09-07
 * @copyright  2009 phpMyFAQ Team
 */
class PMF_Helper_Search extends PMF_Helper 
{
    /**
     * Instance
     * 
     * @var PMF_Helper_Search
     */
    private static $instance = null;
    
    /**
     * Language
     * 
     * @var string
     */
    private $language = null;
    
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
     * @return PMF_Helper_Search
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
       
}