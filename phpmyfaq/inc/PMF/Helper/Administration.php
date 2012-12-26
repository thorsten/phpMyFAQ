<?php
/**
 * Helper class for Administration backend
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
 * @author    Anatoliy Belsky <anatoliy.belsky@mayflower.de>
 * @copyright 2010-2013 phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      http://www.phpmyfaq.de
 * @since     2010-01-19
 */

/**
 * PMF_Helper_Administration
 *
 * @category  phpMyFAQ
 * @package   Helper
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @author    Anatoliy Belsky <anatoliy.belsky@mayflower.de>
 * @copyright 2010-2013 phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      http://www.phpmyfaq.de
 * @since     2010-01-19
 */
class PMF_Helper_Administration
{
    /**
     * Array with permissions
     *
     * @var array
     */
    private $permission = array();

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