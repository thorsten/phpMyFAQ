<?php

/**
 * Helper class for Administration backend.
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at https://mozilla.org/MPL/2.0/.
 *
 * @package   phpMyFAQ
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @author    Anatoliy Belsky <anatoliy.belsky@mayflower.de>
 * @copyright 2010-2023 phpMyFAQ Team
 * @license   https://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2010-01-19
 */

namespace phpMyFAQ\Helper;

use phpMyFAQ\Translation;
use phpMyFAQ\User;

/**
 * Class Administration
 *
 * @package phpMyFAQ\Helper
 */
class AdministrationHelper
{
    /**
     * Array with permissions.
     */
    private array $permission = [];

    /**
     * Adds a menu entry according to user permissions.
     * ',' stands for 'or', '*' stands for 'and'.
     *
     * @param string $restrictions Restrictions
     * @param string $action       Action parameter
     * @param string $caption      Caption
     * @param string|null $active  Active
     * @param bool   $checkPerm    Check permission (default: true)
     */
    public function addMenuEntry(
        string $restrictions = '',
        string $action = '',
        string $caption = '',
        string|null $active = '',
        bool $checkPerm = true
    ): string {

        if ($action != '') {
            $action = 'action=' . $action;
        }

        if (Translation::get($caption) !== null) {
            $renderedCaption = Translation::get($caption);
        } else {
            $renderedCaption = 'No string for ' . $caption;
        }

        $output = sprintf(
            '<a class="nav-link" href="?%s">%s</a>%s',
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
     * Permissions are glued with each other as follows
     * - '+' stands for 'or'
     * - '*' stands for 'and'
     * No braces will be parsed, only simple expressions
     *
     * @example right1*right2+right3+right4*right5
     */
    private function evaluatePermission(string $restrictions): bool
    {
        if (str_contains($restrictions, '+')) {
            $hasPermission = false;
            foreach (explode('+', $restrictions) as $restriction) {
                $hasPermission = $hasPermission || $this->evaluatePermission($restriction);
                if ($hasPermission) {
                    break;
                }
            }
        } elseif (str_contains($restrictions, '*')) {
            $hasPermission = true;
            foreach (explode('*', $restrictions) as $restriction) {
                if (!isset($this->permission[$restriction]) || !$this->permission[$restriction]) {
                    $hasPermission = false;
                    break;
                }
            }
        } else {
            $hasPermission = strlen($restrictions) > 0 &&
                isset($this->permission[$restrictions]) &&
                $this->permission [$restrictions];
        }

        return $hasPermission;
    }

    /**
     * Setter for a permission array.
     */
    public function setUser(User $user): void
    {
        // read all rights, set them FALSE
        $allRights = $user->perm->getAllRightsData();
        foreach ($allRights as $right) {
            $this->permission[$right['name']] = false;
        }
        // check user rights, set them TRUE
        $allUserRights = $user->perm->getAllUserRights($user->getUserId());
        if (false != $allUserRights) {
            foreach ($allRights as $right) {
                if (in_array($right['right_id'], $allUserRights)) {
                    $this->permission[$right['name']] = true;
                }
            }
        }
        // If user is super admin, give all rights
        if ($user->isSuperAdmin()) {
            foreach ($allRights as $right) {
                $this->permission[$right['name']] = true;
            }
        }
    }

    public function renderMetaRobotsDropdown(string $metaRobots): string
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

    /**
     * Returns all sorting possibilities for FAQ records.
     */
    public static function sortingOptions(string $current): string
    {
        $options = ['id', 'thema', 'visits', 'updated', 'author'];
        $output = '';

        foreach ($options as $value) {
            printf(
                '<option value="%s" %s>%s</option>',
                $value,
                ($value == $current) ? 'selected' : '',
                Translation::get('ad_conf_order_' . $value)
            );
        }

        return $output;
    }
}
