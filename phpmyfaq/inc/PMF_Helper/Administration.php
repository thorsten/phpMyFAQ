<?php
/**
 * Helper class for Administration backend
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
 * @author    Anatoliy Belsky <anatoliy.belsky@mayflower.de>
 * @copyright 2010-2012 phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/MPL-1.1.html Mozilla Public License Version 1.1
 * @link      http://www.phpmyfaq.de
 * @since     2010-01-19
 */

/**
 * PMF_Helper_Administration
 *
 * @category  phpMyFAQ
 * @package   PMF_Helper
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @author    Anatoliy Belsky <anatoliy.belsky@mayflower.de>
 * @copyright 2010-2012 phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/MPL-1.1.html Mozilla Public License Version 1.1
 * @link      http://www.phpmyfaq.de
 * @since     2010-01-19
 */
class PMF_Helper_Administration
{
    /**
     * Instance
     * 
     * @var PMF_Helper_Search
     */
    private static $instance = null;
    
    /**
     * Array with permissions
     * 
     * @var array
     */
    private $permission = array();
    
    /**
     * Constructor
     * 
     * @return PMF_Helper_Administration
     */
    private function __construct()
    {
    }
    
    /**
     * Returns the single instance
     *
     * @access static
     * @return PMF_Helper_Administration
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
     * Adds a menu entry according to user permissions.
     * ',' stands for 'or', '*' stands for 'and'
     *
     * @param string  $restrictions Restrictions
     * @param string  $action       Action parameter
     * @param string  $caption      Caption
     * @param string  $active       Active
     * @param boolean $checkPerm    Check permission (default: true)
     * 
     * @return string
     */
    public function addMenuEntry($restrictions = '', $action = '', $caption = '', $active = '', $checkPerm = true)
    {
        global $PMF_LANG;

        if ($active == $action) {
            $active = ' class="active"';
        } else {
            $active = '';
        }
        
        if ($action != '') {
            $action = "action=" . $action;
        }
        
        if (isset($PMF_LANG[$caption])) {
            $_caption = $PMF_LANG[$caption];
        } else {
            $_caption = 'No string for ' . $caption;
        }
        
        $output = sprintf('<li%s><a href="?%s">%s</a></li>%s', $active, $action, $_caption, "\n");
        
        if ($checkPerm) {
            return $this->evaluatePermission($restrictions) ? $output : '';
        } else {
            return $output;
        }
    }
    
    /**
     * Parse and check a permission string
     * 
     * Permissions are glued with each other as follows
     * - '+' stands for 'or'
     * - '*' stands for 'and'
     * 
     * No braces will be parsed, only simple expressions
     * @example right1*right2+right3+right4*right5
     * 
     * @param string $restrictions
     * 
     * @return boolean
     */
    private function evaluatePermission($restrictions)
    {
        if (false !== strpos ($restrictions, '+')) {
            $retval = false;
            foreach (explode('+',$restrictions) as $_restriction) {
                $retval = $retval || $this->evaluatePermission($_restriction);
                if ($retval) {
                    break;
                }
            }
        } elseif (false !== strpos($restrictions, '*')) {
            $retval = true;
            foreach (explode('*', $restrictions) as $_restriction) {
                if (!isset($this->permission[$_restriction]) || !$this->permission[$_restriction]) {
                    $retval = false;
                    break;
                }
            }
        } else {
            $retval = strlen($restrictions) > 0 && 
                isset($this->permission[$restrictions]) && 
                $this->permission [$restrictions];
        }
        
        return $retval;
    }
    
    /**
     * Setter for permission aray
     * 
     * @param array $permission Array of permissions
     * 
     * @return void
     */
    public function setPermission(Array $permission)
    {
        $this->permission = $permission;
    }
}