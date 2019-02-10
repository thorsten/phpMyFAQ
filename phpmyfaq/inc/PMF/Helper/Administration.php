<?php

/**
 * Helper class for Administration backend.
 *
 * PHP Version 5.5
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at http://mozilla.org/MPL/2.0/.
 *
 * @category  phpMyFAQ
 *
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @author    Anatoliy Belsky <anatoliy.belsky@mayflower.de>
 * @copyright 2010-2019 phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 *
 * @link      https://www.phpmyfaq.de
 * @since     2010-01-19
 */

/**
 * PMF_Helper_Administration.
 *
 * @category  phpMyFAQ
 *
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @author    Anatoliy Belsky <anatoliy.belsky@mayflower.de>
 * @copyright 2010-2019 phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 *
 * @link      https://www.phpmyfaq.de
 * @since     2010-01-19
 */
class PMF_Helper_Administration
{
    /**
     * Array with permissions.
     *
     * @var array
     */
    private $permission = [];

    /**
     * Adds a menu entry according to user permissions.
     * ',' stands for 'or', '*' stands for 'and'.
     *
     * @param string $restrictions Restrictions
     * @param string $action       Action parameter
     * @param string $caption      Caption
     * @param string $active       Active
     * @param bool   $checkPerm    Check permission (default: true)
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
            $action = 'action='.$action;
        }

        if (isset($PMF_LANG[$caption])) {
            $renderedCaption = $PMF_LANG[$caption];
        } else {
            $renderedCaption = 'No string for '.$caption;
        }

        $output = sprintf(
            '<li%s><a href="?%s">%s</a></li>%s',
            $active,
            $action,
            $renderedCaption,
            "\n"
        );

        if ($checkPerm) {
            return $this->evaluatePermission($restrictions) ? $output : '';
        } else {
            return $output;
        }
    }

    /**
     * Parse and check a permission string.
     *
     * Permissions are glued with each other as follows
     * - '+' stands for 'or'
     * - '*' stands for 'and'
     *
     * No braces will be parsed, only simple expressions
     *
     * @example right1*right2+right3+right4*right5
     *
     * @param string $restrictions
     *
     * @return bool
     */
    private function evaluatePermission($restrictions)
    {
        if (false !== strpos($restrictions, '+')) {
            $retval = false;
            foreach (explode('+', $restrictions) as $_restriction) {
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
     * Setter for permission array.
     *
     * @param PMF_User $user
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
        if (false !== $allUserRights) {
            foreach ($allRights as $right) {
                if (in_array($right['right_id'], $allUserRights)) {
                    $this->permission[$right['name']] = true;
                }
            }
        }
    }

    /**
     * @param string $metaRobots
     *
     * @return string
     */
    public function renderMetaRobotsDropdown($metaRobots)
    {
        $html = '';
        $values = [
            'index, follow',
            'index, nofollow',
            'noindex, follow',
            'noindex, nofollow',
        ];

        foreach ($values as $value) {
            $html .= sprintf(
                '<option%s>%s</option>',
                ($value === $metaRobots) ? ' selected' : '',
                $value
            );
        }

        return $html;
    }
}
