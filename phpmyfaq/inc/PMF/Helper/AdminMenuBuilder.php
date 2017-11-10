<?php
/**
 * Helper class for Administration backend
 *
 * PHP Version 5.4
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at http://mozilla.org/MPL/2.0/.
 *
 * @category  phpMyFAQ
 * @package   Helper
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @author    Anatoliy Belsky <anatoliy.belsky@mayflower.de>
 * @copyright 2010-2014 phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      http://www.phpmyfaq.de
 * @since     2010-01-19
 */

namespace PMF\Helper;

use Twig_Environment;

/**
 * AdminMenuBuilder
 *
 * Formerly known as PMF_Helper_Administration
 *
 * @category  phpMyFAQ
 * @package   Helper
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @author    Anatoliy Belsky <anatoliy.belsky@mayflower.de>
 * @author    Alexander M. Turek <me@derrabus.de>
 * @copyright 2010-2014 phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      http://www.phpmyfaq.de
 * @since     2010-01-19
 */
class AdminMenuBuilder
{
    const TEMPLATE = 'sideNavigation.twig';

    /**
     * @var Twig_Environment
     */
    private $twig;

    /**
     * User object
     *
     * @var PMF_User
     */
    private $user = null;

    /**
     * Array with permissions
     *
     * @var array
     */
    private $permission = [];

    /**
     * @var string
     */
    private $header;

    /**
     * @var array
     */
    private $entries = array();

    /**
     * @param Twig_Environment $twig
     */
    function __construct(Twig_Environment $twig)
    {
        $this->twig = $twig;
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

        if ($checkPerm && !$this->evaluatePermission($restrictions)) {
            return '';
        }
        
        if (isset($PMF_LANG[$caption])) {
            $caption = $PMF_LANG[$caption];
        } else {
            $caption = 'No string for ' . $caption;
        }
        
        $this->entries[] = array(
            'caption' => $caption,
            'isActive' => $active == $action,
            'linkUrl' => ($action != '') ? '?action=' . $action : ''
        );
    }

    /**
     * @return string
     */
    public function render()
    {
        global $PMF_LANG;

        return $this->twig
            ->loadTemplate(self::TEMPLATE)
            ->render(
                array(
                    'PMF_LANG' => $PMF_LANG,
                    'entries' => $this->entries,
                    'header' => $this->header
                )
            );
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
     * Setter for permission array
     *
     * @param PMF_User $user User object
     *
     * @return void
     */
    public function setUser(PMF_User $user)
    {
        // read all rights, set them FALSE
        $allRights = $user->perm->getAllRightsData();
        foreach ($allRights as $right) {
            $this->permission[$right['name']] = false;
        }
        // check user rights, set them TRUE
        $allUserRights = $user->perm->getAllUserRights($user->getUserId());
        foreach ($allRights as $right) {
            if (in_array($right['right_id'], $allUserRights))
                $this->permission[$right['name']] = true;
        }
    }

    /**
     * @param string $header
     */
    public function setHeader($header)
    {
        $this->header = $header;
    }
}