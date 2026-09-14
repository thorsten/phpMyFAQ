<?php

declare(strict_types=1);

/**
 * The abstract Administration API controller
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at https://mozilla.org/MPL/2.0/.
 *
 * @package   phpMyFAQ
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2026 phpMyFAQ Team
 * @license   https://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2026-01-06
 */

namespace phpMyFAQ\Controller\Administration\Api;

use Override;
use phpMyFAQ\Administration\AdminLog;
use phpMyFAQ\Controller\AbstractController;
use phpMyFAQ\Permission\MediumPermission;

abstract class AbstractAdministrationApiController extends AbstractController
{
    protected AdminLog $adminLog;

    #[Override]
    protected function initializeFromContainer(): void
    {
        parent::initializeFromContainer();

        $adminLog = $this->container->get(id: 'phpmyfaq.admin.admin-log');
        if (!$adminLog instanceof AdminLog) {
            throw new \LogicException('AdminLog service not found in container.');
        }

        $this->adminLog = $adminLog;
    }

    /**
     * Returns true if the acting user may scope the given right to exactly the
     * given languages.
     *
     * An empty restriction set means "unrestricted", so writing one is a
     * privilege *grant*, not a narrowing. A non-SuperAdmin whose own set for the
     * right is restricted may therefore only write a non-empty subset of that
     * set — never an empty list, and never a language they do not hold
     * themselves. SuperAdmins and acting users whose own right is unrestricted
     * (`getAllowedLanguagesForRight()` returns null) are unaffected.
     *
     * @param array<string> $languages
     * @throws \phpMyFAQ\Core\Exception
     */
    protected function mayAssignLanguages(int $rightId, array $languages): bool
    {
        if ($this->currentUser->isSuperAdmin()) {
            return true;
        }

        $allowedLanguages = $this->currentUser->perm->getAllowedLanguagesForRight(
            $this->currentUser->getUserId(),
            $rightId,
        );

        if ($allowedLanguages === null) {
            return true;
        }

        // Clearing the restrictions would widen the right to every language,
        // including ones the acting user does not hold.
        if ($languages === []) {
            return false;
        }

        foreach ($languages as $language) {
            if (!in_array($language, $allowedLanguages, strict: true)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Whether the acting user holds the group's right in at least the language and category
     * scope the group holds it in. Group rights are inherited by every member, so a right the
     * group holds unrestricted, or in a language or category the acting user lacks, would widen
     * the acting user's own scope on joining.
     *
     * @throws \phpMyFAQ\Core\Exception
     */
    protected function holdsGroupRightInFullScope(MediumPermission $permission, int $groupId, int $rightId): bool
    {
        if (!$permission->hasPermission($this->currentUser->getUserId(), $rightId)) {
            return false;
        }

        return (
            $this->mayAssignLanguages($rightId, $permission->getLanguageRestrictions($groupId, $rightId))
            && $this->mayAssignCategories($rightId, $permission->getCategoryRestrictions($groupId, $rightId))
        );
    }

    /**
     * Restricts the group's right to the languages and categories the acting user holds it in,
     * so a delegated grant never exceeds the granter's own scope. A null scope means the acting
     * user is unrestricted in that dimension and the group's restrictions are left alone; an
     * empty scope means the right is not held at all and cannot be granted.
     *
     * @param array<string>|null $ownLanguages
     * @param array<int|string>|null $ownCategories
     */
    protected function restrictGroupRightToScope(
        MediumPermission $permission,
        int $groupId,
        int $rightId,
        ?array $ownLanguages,
        ?array $ownCategories,
    ): bool {
        if ($ownLanguages !== null) {
            if ($ownLanguages === [] || !$permission->setLanguageRestrictions($groupId, $rightId, $ownLanguages)) {
                return false;
            }
        }

        if ($ownCategories !== null) {
            $ownCategories = array_map(intval(...), $ownCategories);
            if ($ownCategories === [] || !$permission->setCategoryRestrictions($groupId, $rightId, $ownCategories)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Whether the acting user may scope the given right to exactly these categories.
     * SuperAdmins and users holding the right without category restriction may assign
     * anything; everyone else only a non-empty subset of their own allowed categories.
     *
     * @param array<int> $categoryIds
     * @throws \phpMyFAQ\Core\Exception
     */
    protected function mayAssignCategories(int $rightId, array $categoryIds): bool
    {
        if ($this->currentUser->isSuperAdmin()) {
            return true;
        }

        $allowedCategories = $this->currentUser->perm->getAllowedCategoriesForRight(
            $this->currentUser->getUserId(),
            $rightId,
        );

        if ($allowedCategories === null) {
            return true;
        }

        // Clearing the restrictions would widen the right to every category,
        // including ones the acting user does not hold.
        if ($categoryIds === []) {
            return false;
        }

        $allowedCategories = array_map(intval(...), $allowedCategories);

        return array_all($categoryIds, static fn(int $categoryId): bool => in_array(
            $categoryId,
            $allowedCategories,
            strict: true,
        ));
    }
}
