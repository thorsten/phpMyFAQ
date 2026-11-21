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
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

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
     * Returns true if the acting user may hand out the given right restricted to exactly the
     * given categories and languages.
     *
     * An empty restriction list means "unrestricted", so writing one is a privilege *grant*,
     * not a narrowing. A non-SuperAdmin may therefore only hand out a scope they hold
     * themselves, and they hold it only where one of their own grants covers the category
     * and the language together: holding "German" through one grant and "category 1" through
     * another does not allow handing out "category 2 in English", nor an unrestricted right.
     *
     * @param array<int>    $categoryIds empty means every category
     * @param array<string> $languages   empty means every language
     * @throws \phpMyFAQ\Core\Exception
     */
    protected function mayAssignScope(int $rightId, array $categoryIds, array $languages): bool
    {
        if ($this->currentUser->isSuperAdmin()) {
            return true;
        }

        return $this->currentUser
            ->perm
            ->getScopeForRight($this->currentUser->getUserId(), $rightId)
            ->covers($categoryIds === [] ? null : $categoryIds, $languages === [] ? null : $languages);
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

        return $this->mayAssignScope(
            $rightId,
            $permission->getCategoryRestrictions($groupId, $rightId),
            $permission->getLanguageRestrictions($groupId, $rightId),
        );
    }

    /**
     * Denies a group-write operation unless the acting user may act on every right the group
     * carries, and returns null when it may proceed.
     *
     * A non-SuperAdmin may only operate on a group whose rights they hold themselves, in at least
     * the language and category scope the group holds them in. Group rights are inherited by every
     * member, so without this an administrator holding a delegable group-management right could
     * reach privileges they do not already possess (privilege escalation via group membership
     * inheritance), or destroy privileges they could never grant.
     *
     * @throws \phpMyFAQ\Core\Exception
     */
    protected function denyUnlessGroupRightsHeld(
        int $groupId,
        string $unsupportedError,
        string $forbiddenError,
    ): ?JsonResponse {
        if ($this->currentUser->isSuperAdmin()) {
            return null;
        }

        // Fail closed: if the permission backend cannot enumerate group rights, we cannot prove
        // the acting user holds them, so the operation must be denied rather than allowed.
        $actingPermission = $this->currentUser->perm;
        if (!$actingPermission instanceof MediumPermission) {
            return $this->json(['error' => $unsupportedError], Response::HTTP_FORBIDDEN);
        }

        foreach ($actingPermission->getGroupRights($groupId) as $groupRight) {
            if (!$this->holdsGroupRightInFullScope($actingPermission, $groupId, (int) $groupRight)) {
                return $this->json(['error' => $forbiddenError], Response::HTTP_FORBIDDEN);
            }
        }

        return null;
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
}
