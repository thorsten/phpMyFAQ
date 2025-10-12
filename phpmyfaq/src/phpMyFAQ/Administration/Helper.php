<?php

declare(strict_types=1);

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
 * @copyright 2010-2025 phpMyFAQ Team
 * @license   https://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2010-01-19
 */

namespace phpMyFAQ\Administration;

use phpMyFAQ\Enums\ReleaseType;
use phpMyFAQ\Translation;
use phpMyFAQ\User;
use phpMyFAQ\User\CurrentUser;

/**
 * Class Administration
 *
 * @package phpMyFAQ\Helper
 */
class Helper
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
     * @param string $caption      Caption
     * @param string|null $route   Route, will be used if it's not empty
     * @param bool   $checkPerm    Check permission (default: true)
     */
    public function addMenuEntry(
        string $restrictions = '',
        string $caption = '',
        ?string $route = null,
        bool $checkPerm = true,
    ): string {
        if (Translation::get($caption) !== null) {
            $renderedCaption = Translation::get($caption);
        } else {
            $renderedCaption = 'No string for ' . $caption;
        }

        $output = sprintf('<a class="nav-link" href="./%s">%s</a>%s', $route, $renderedCaption, "\n");

        if ($checkPerm) {
            return $this->evaluatePermission($restrictions) ? $output : '';
        }

        return $output;
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
            $hasPermission =
                strlen($restrictions) > 0
                && isset($this->permission[$restrictions])
                && $this->permission[$restrictions];
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
        if (false !== $allUserRights) {
            foreach ($allRights as $allRight) {
                if (in_array($allRight['right_id'], $allUserRights)) {
                    $this->permission[$allRight['name']] = true;
                }
            }
        }

        // If a user is super admin, give all rights
        if ($user->isSuperAdmin()) {
            foreach ($allRights as $allRight) {
                $this->permission[$allRight['name']] = true;
            }
        }
    }

    public static function renderMetaRobotsDropdown(string $metaRobots): string
    {
        $output = '';
        $options = [
            'index, follow',
            'index, nofollow',
            'noindex, follow',
            'noindex, nofollow',
        ];

        foreach ($options as $option) {
            $output .= sprintf('<option%s>%s</option>', $option === $metaRobots ? ' selected' : '', $option);
        }

        return $output;
    }

    /**
     * Returns all key sorting possibilities for FAQ records.
     */
    public static function sortingKeyOptions(string $current): string
    {
        $options = ['id', 'thema', 'visits', 'updated', 'author'];
        $output = '';

        foreach ($options as $option) {
            $output .= Helper::generateOption($current, $option, 'ad_conf_order_' . $option);
        }

        return $output;
    }

    /**
     * Returns all order sorting possibilities for FAQ records.
     */
    public static function sortingOrderOptions(string $current): string
    {
        $options = ['ASC', 'DESC'];
        $output = '';

        foreach ($options as $option) {
            $output .= Helper::generateOption($current, $option, 'ad_conf_' . strtolower($option));
        }

        return $output;
    }

    public static function sortingPopularFaqsOptions(string $current): string
    {
        $options = ['visits', 'voting'];
        $output = '';

        foreach ($options as $option) {
            $output .= Helper::generateOption($current, $option, 'records.orderingPopularFaqs.' . $option);
        }

        return $output;
    }

    public static function searchRelevanceOptions(string $current): string
    {
        $output = '';
        $output .= Helper::generateOption(
            $current,
            'thema,content,keywords',
            'search.relevance.thema-content-keywords',
        );
        $output .= Helper::generateOption(
            $current,
            'thema,keywords,content',
            'search.relevance.thema-keywords-content',
        );
        $output .= Helper::generateOption(
            $current,
            'content,thema,keywords',
            'search.relevance.content-thema-keywords',
        );
        $output .= Helper::generateOption(
            $current,
            'content,keywords,thema',
            'search.relevance.content-keywords-thema',
        );
        $output .= Helper::generateOption(
            $current,
            'keywords,content,thema',
            'search.relevance.keywords-content-thema',
        );

        return $output
        . Helper::generateOption($current, 'keywords,thema,content', 'search.relevance.keywords-thema-content');
    }

    public static function renderReleaseTypeOptions(string $current): string
    {
        $releaseTypes = [ReleaseType::DEVELOPMENT, ReleaseType::STABLE, ReleaseType::NIGHTLY];
        $output = '';

        foreach ($releaseTypes as $releaseType) {
            $value = $releaseType->value;

            $output .= sprintf(
                '<option value="%s"%s>%s</option>',
                $value,
                $value === $current ? ' selected' : '',
                ucfirst($releaseType->value),
            );
        }

        return $output;
    }

    /**
     * Checks if the current user can access the content.
     */
    public function canAccessContent(CurrentUser $currentUser): bool
    {
        return (
            $currentUser->isLoggedIn()
            && (
                (
                    is_countable($currentUser->perm->getAllUserRights($currentUser->getUserId()))
                        ? count($currentUser->perm->getAllUserRights($currentUser->getUserId()))
                        : 0
                )
                || $currentUser->isSuperAdmin()
            )
        );
    }

    private static function generateOption(string $current, string $value, string $label): string
    {
        return sprintf(
            '<option value="%s"%s>%s</option>',
            $value,
            $value === $current ? ' selected' : '',
            Translation::get($label),
        );
    }
}
