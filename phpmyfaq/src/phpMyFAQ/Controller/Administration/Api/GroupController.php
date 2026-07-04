<?php

/**
 * The Admin Group Controller
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at https://mozilla.org/MPL/2.0/.
 *
 * @package   phpMyFAQ
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2023-2026 phpMyFAQ Team
 * @license   https://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2023-10-27
 */

declare(strict_types=1);

namespace phpMyFAQ\Controller\Administration\Api;

use phpMyFAQ\Administration\Category;
use phpMyFAQ\Core\Exception;
use phpMyFAQ\Enums\AdminLogType;
use phpMyFAQ\Enums\PermissionType;
use phpMyFAQ\Filter;
use phpMyFAQ\Permission\MediumPermission;
use phpMyFAQ\Session\Token;
use phpMyFAQ\Translation;
use phpMyFAQ\User\CurrentUser;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class GroupController extends AbstractAdministrationApiController
{
    /**
     * @throws Exception
     */
    #[Route(path: 'group/groups', name: 'admin.api.group.groups', methods: ['GET'])]
    public function listGroups(): JsonResponse
    {
        $this->userHasGroupPermission();

        $currentUser = CurrentUser::getCurrentUser($this->configuration);

        $groupList = $currentUser->perm instanceof MediumPermission
            ? $currentUser->perm->getAllGroups($currentUser)
            : [];

        $groups = [];
        foreach ($groupList as $groupId) {
            $data = $currentUser->perm->getGroupData((int) $groupId);
            $groups[] = [
                'group_id' => $data['group_id'],
                'name' => $data['name'],
            ];
        }

        return $this->json($groups, Response::HTTP_OK);
    }

    /**
     * @throws Exception
     */
    #[Route(path: 'group/users', name: 'admin.api.group.users', methods: ['GET'])]
    public function listUsers(): JsonResponse
    {
        $this->userHasGroupPermission();

        $currentUser = CurrentUser::getCurrentUser($this->configuration);

        $users = [];
        foreach ($currentUser->getAllUsers(true, false) as $singleUser) {
            $currentUser->getUserById($singleUser, true);
            $users[] = [
                'user_id' => $currentUser->getUserId(),
                'login' => $currentUser->getLogin(),
            ];
        }

        return $this->json($users, Response::HTTP_OK);
    }

    /**
     * @throws Exception
     */
    #[Route(path: 'group/data/{groupId}', name: 'admin.api.group.data', methods: ['GET'])]
    public function groupData(Request $request): JsonResponse
    {
        $this->userHasGroupPermission();

        $currentUser = CurrentUser::getCurrentUser($this->configuration);

        $groupId = (int) $request->attributes->get('groupId');

        return $this->json($currentUser->perm->getGroupData($groupId), Response::HTTP_OK);
    }

    /**
     * @throws Exception
     */
    #[Route(path: 'group/members/{groupId}', name: 'admin.api.group.members', methods: ['GET'])]
    public function listMembers(Request $request): JsonResponse
    {
        $this->userHasGroupPermission();

        $currentUser = CurrentUser::getCurrentUser($this->configuration);

        $groupId = (int) $request->attributes->get('groupId');

        $members = [];
        foreach ($currentUser->perm->getGroupMembers($groupId) as $groupMember) {
            $currentUser->getUserById((int) $groupMember, true);
            $members[] = [
                'user_id' => $currentUser->getUserId(),
                'login' => $currentUser->getLogin(),
            ];
        }

        return $this->json($members, Response::HTTP_OK);
    }

    /**
     * @throws Exception
     */
    #[Route(path: 'group/permissions/{groupId}', name: 'admin.api.group.permissions', methods: ['GET'])]
    public function listPermissions(Request $request): JsonResponse
    {
        $this->userHasGroupPermission();

        $currentUser = CurrentUser::getCurrentUser($this->configuration);

        $groupId = (int) $request->attributes->get('groupId');

        return $this->json($currentUser->perm->getGroupRights($groupId), Response::HTTP_OK);
    }

    /**
     * @throws Exception
     */
    #[Route(
        path: 'group/category-restrictions/{groupId}',
        name: 'admin.api.group.category-restrictions',
        methods: ['GET'],
    )]
    public function listCategoryRestrictions(Request $request): JsonResponse
    {
        $this->userHasGroupPermission();

        $currentUser = CurrentUser::getCurrentUser($this->configuration);

        $groupId = (int) $request->attributes->get('groupId');

        if (!$currentUser->perm instanceof MediumPermission) {
            return $this->json(new \stdClass(), Response::HTTP_OK);
        }

        return $this->json(
            $currentUser->perm->getAllCategoryRestrictions($groupId) ?: new \stdClass(),
            Response::HTTP_OK,
        );
    }

    /**
     * @throws Exception
     */
    #[Route(path: 'group/category-restrictions', name: 'admin.api.group.category-restrictions.save', methods: ['POST'])]
    public function saveCategoryRestrictions(Request $request): JsonResponse
    {
        $this->userHasGroupPermission();

        $currentUser = CurrentUser::getCurrentUser($this->configuration);

        if (!$currentUser->perm instanceof MediumPermission) {
            return $this->json(['error' => 'Group permissions are not enabled.'], Response::HTTP_BAD_REQUEST);
        }

        $data = json_decode($request->getContent(), true);
        if (!is_array($data)) {
            return $this->json(['error' => 'Invalid JSON payload.'], Response::HTTP_BAD_REQUEST);
        }

        if (!Token::getInstance($this->session)->verifyToken('save-category-restrictions', $data['csrfToken'] ?? '')) {
            return $this->json(['error' => 'Invalid CSRF token.'], Response::HTTP_FORBIDDEN);
        }

        $groupId = (int) ($data['groupId'] ?? 0);
        $rightId = (int) ($data['rightId'] ?? 0);

        if ($groupId <= 0 || $rightId <= 0) {
            return $this->json(['error' => 'Invalid group or right ID.'], Response::HTTP_BAD_REQUEST);
        }

        $rawCategoryIds = $data['categoryIds'] ?? [];
        if (!is_array($rawCategoryIds)) {
            return $this->json(['error' => 'categoryIds must be an array.'], Response::HTTP_BAD_REQUEST);
        }

        $categoryIds = array_values(array_filter(
            array_map('intval', $rawCategoryIds),
            static fn(int $id): bool => $id > 0,
        ));

        $success = $currentUser->perm->setCategoryRestrictions($groupId, $rightId, $categoryIds);

        if (!$success) {
            return $this->json([
                'error' => 'Failed to save category restrictions.',
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }

        return $this->json(['success' => true], Response::HTTP_OK);
    }

    /**
     * @throws Exception
     */
    #[Route(path: 'group/update', name: 'admin.api.group.update', methods: ['POST'])]
    public function updateGroup(Request $request): JsonResponse
    {
        $this->userHasPermission(PermissionType::GROUP_EDIT);

        $data = json_decode($request->getContent(), associative: true);
        if (!is_array($data)) {
            return $this->json(['error' => 'Invalid JSON payload.'], Response::HTTP_BAD_REQUEST);
        }

        if (!Token::getInstance($this->session)->verifyToken('update-group', $data['csrfToken'] ?? '')) {
            return $this->json(['error' => 'Invalid CSRF token.'], Response::HTTP_FORBIDDEN);
        }

        $groupId = (int) ($data['groupId'] ?? 0);
        if ($groupId <= 0) {
            return $this->json(['error' => 'Invalid group ID.'], Response::HTTP_BAD_REQUEST);
        }

        $name = Filter::filterVar($data['name'] ?? '', FILTER_SANITIZE_SPECIAL_CHARS, '');
        if (trim((string) $name) === '') {
            return $this->json(['error' => Translation::get('ad_group_error_noName')], Response::HTTP_BAD_REQUEST);
        }

        $autoJoin = (bool) ($data['autoJoin'] ?? false);

        // Enabling auto-join makes every newly registered user inherit this group's rights,
        // so a non-SuperAdmin may only enable it on a group whose rights they fully hold
        // (same escalation rule as membership management, fail closed).
        if ($autoJoin && !$this->currentUser->isSuperAdmin()) {
            if (!$this->currentUser->perm instanceof MediumPermission) {
                return $this->json([
                    'error' => 'Cannot enable auto-join without group permission support.',
                ], Response::HTTP_FORBIDDEN);
            }

            $actingUserId = $this->currentUser->getUserId();
            foreach ($this->currentUser->perm->getGroupRights($groupId) as $groupRight) {
                if (!$this->currentUser->perm->hasPermission($actingUserId, (int) $groupRight)) {
                    return $this->json([
                        'error' => 'Cannot enable auto-join on a group whose rights you do not hold.',
                    ], Response::HTTP_FORBIDDEN);
                }
            }
        }

        $currentUser = CurrentUser::getCurrentUser($this->configuration);
        if (!$currentUser->perm instanceof MediumPermission) {
            return $this->json(['error' => 'Group permissions are not enabled.'], Response::HTTP_BAD_REQUEST);
        }

        $groupData = [
            'name' => $name,
            'description' => Filter::filterVar($data['description'] ?? '', FILTER_SANITIZE_SPECIAL_CHARS, ''),
            'auto_join' => $autoJoin,
        ];

        if (!$currentUser->perm->changeGroup($groupId, $groupData)) {
            return $this->json(['error' => Translation::get('ad_msg_mysqlerr')], Response::HTTP_INTERNAL_SERVER_ERROR);
        }

        $this->adminLog?->log($this->currentUser, AdminLogType::GROUP_EDIT->value . ':' . $groupId);

        return $this->json([
            'success' => sprintf(
                '%s %s %s',
                Translation::get('ad_msg_savedsuc_1'),
                $currentUser->perm->getGroupName($groupId),
                Translation::get('ad_msg_savedsuc_2'),
            ),
        ], Response::HTTP_OK);
    }

    /**
     * @throws Exception
     */
    #[Route(path: 'group/members', name: 'admin.api.group.members.update', methods: ['POST'])]
    public function updateMembers(Request $request): JsonResponse
    {
        $this->userHasPermission(PermissionType::GROUP_EDIT);

        $data = json_decode($request->getContent(), associative: true);
        if (!is_array($data)) {
            return $this->json(['error' => 'Invalid JSON payload.'], Response::HTTP_BAD_REQUEST);
        }

        if (!Token::getInstance($this->session)->verifyToken('update-group-members', $data['csrfToken'] ?? '')) {
            return $this->json(['error' => 'Invalid CSRF token.'], Response::HTTP_FORBIDDEN);
        }

        $groupId = (int) ($data['groupId'] ?? 0);
        if ($groupId <= 0) {
            return $this->json(['error' => 'Invalid group ID.'], Response::HTTP_BAD_REQUEST);
        }

        $rawMemberIds = $data['memberIds'] ?? [];
        if (!is_array($rawMemberIds)) {
            return $this->json(['error' => 'memberIds must be an array.'], Response::HTTP_BAD_REQUEST);
        }

        $memberIds = array_values(array_filter(
            array_map('intval', $rawMemberIds),
            static fn(int $id): bool => $id > 0,
        ));

        // A non-SuperAdmin may only manage membership of a group whose rights they fully hold
        // themselves. Otherwise an administrator with the delegable GROUP_EDIT right could join
        // themselves (or anyone else) to a privileged group and inherit rights they do not possess
        // (privilege escalation via group membership inheritance).
        if (!$this->currentUser->isSuperAdmin()) {
            // Fail closed: if the permission backend cannot enumerate group rights, we cannot prove
            // the acting user holds them, so the operation must be denied rather than allowed.
            if (!$this->currentUser->perm instanceof MediumPermission) {
                return $this->json([
                    'error' => 'Cannot manage group membership without group permission support.',
                ], Response::HTTP_FORBIDDEN);
            }

            $actingUserId = $this->currentUser->getUserId();
            foreach ($this->currentUser->perm->getGroupRights($groupId) as $groupRight) {
                if (!$this->currentUser->perm->hasPermission($actingUserId, (int) $groupRight)) {
                    return $this->json([
                        'error' => 'Cannot manage a group whose rights you do not hold.',
                    ], Response::HTTP_FORBIDDEN);
                }
            }
        }

        $currentUser = CurrentUser::getCurrentUser($this->configuration);
        if (!$currentUser->perm instanceof MediumPermission) {
            return $this->json(['error' => 'Group permissions are not enabled.'], Response::HTTP_BAD_REQUEST);
        }

        if (!$currentUser->perm->removeAllUsersFromGroup($groupId)) {
            return $this->json(['error' => Translation::get('ad_msg_mysqlerr')], Response::HTTP_INTERNAL_SERVER_ERROR);
        }

        $failed = false;
        foreach ($memberIds as $memberId) {
            if (!$currentUser->perm->addToGroup($memberId, $groupId)) {
                $failed = true;
            }
        }

        if ($failed) {
            return $this->json(['error' => Translation::get('ad_msg_mysqlerr')], Response::HTTP_INTERNAL_SERVER_ERROR);
        }

        $this->adminLog?->log($this->currentUser, AdminLogType::GROUP_EDIT->value . ' (members):' . $groupId);

        return $this->json([
            'success' => sprintf(
                '%s %s %s',
                Translation::get('ad_msg_savedsuc_1'),
                $currentUser->perm->getGroupName($groupId),
                Translation::get('ad_msg_savedsuc_2'),
            ),
        ], Response::HTTP_OK);
    }

    /**
     * @throws Exception
     */
    #[Route(path: 'group/permissions', name: 'admin.api.group.permissions.update', methods: ['POST'])]
    public function updatePermissions(Request $request): JsonResponse
    {
        $this->userHasPermission(PermissionType::GROUP_EDIT);

        $data = json_decode($request->getContent(), associative: true);
        if (!is_array($data)) {
            return $this->json(['error' => 'Invalid JSON payload.'], Response::HTTP_BAD_REQUEST);
        }

        if (!Token::getInstance($this->session)->verifyToken('update-group-permissions', $data['csrfToken'] ?? '')) {
            return $this->json(['error' => 'Invalid CSRF token.'], Response::HTTP_FORBIDDEN);
        }

        $groupId = (int) ($data['groupId'] ?? 0);
        if ($groupId <= 0) {
            return $this->json(['error' => 'Invalid group ID.'], Response::HTTP_BAD_REQUEST);
        }

        $rawRightIds = $data['rightIds'] ?? [];
        if (!is_array($rawRightIds)) {
            return $this->json(['error' => 'rightIds must be an array.'], Response::HTTP_BAD_REQUEST);
        }

        $rightIds = array_values(array_filter(array_map('intval', $rawRightIds), static fn(int $id): bool => $id > 0));

        // A non-SuperAdmin may only assign rights they hold themselves. This prevents an
        // administrator with the delegable GROUP_EDIT right from granting privileges they do not
        // possess to a group (privilege escalation via group membership inheritance).
        if (!$this->currentUser->isSuperAdmin()) {
            $actingUserId = $this->currentUser->getUserId();
            foreach ($rightIds as $rightId) {
                if (!$this->currentUser->perm->hasPermission($actingUserId, $rightId)) {
                    return $this->json(['error' => 'Cannot grant a right you do not hold.'], Response::HTTP_FORBIDDEN);
                }
            }
        }

        $currentUser = CurrentUser::getCurrentUser($this->configuration);
        if (!$currentUser->perm instanceof MediumPermission) {
            return $this->json(['error' => 'Group permissions are not enabled.'], Response::HTTP_BAD_REQUEST);
        }

        if (!$currentUser->perm->refuseAllGroupRights($groupId)) {
            return $this->json(['error' => Translation::get('ad_msg_mysqlerr')], Response::HTTP_INTERNAL_SERVER_ERROR);
        }

        $failed = false;
        foreach ($rightIds as $rightId) {
            if (!$currentUser->perm->grantGroupRight($groupId, $rightId)) {
                $failed = true;
            }
        }

        if ($failed) {
            return $this->json(['error' => Translation::get('ad_msg_mysqlerr')], Response::HTTP_INTERNAL_SERVER_ERROR);
        }

        $this->adminLog?->log($this->currentUser, AdminLogType::GROUP_CHANGE_PERMISSIONS->value . ':' . $groupId);

        return $this->json([
            'success' => sprintf(
                '%s %s %s',
                Translation::get('ad_msg_savedsuc_1'),
                $currentUser->perm->getGroupName($groupId),
                Translation::get('ad_msg_savedsuc_2'),
            ),
        ], Response::HTTP_OK);
    }

    /**
     * @throws Exception
     */
    #[Route(path: 'group/delete', name: 'admin.api.group.delete', methods: ['POST'])]
    public function deleteGroup(Request $request): JsonResponse
    {
        $this->userHasPermission(PermissionType::GROUP_DELETE);

        $data = json_decode($request->getContent(), associative: true);
        if (!is_array($data)) {
            return $this->json(['error' => 'Invalid JSON payload.'], Response::HTTP_BAD_REQUEST);
        }

        if (!Token::getInstance($this->session)->verifyToken('delete-group', $data['csrfToken'] ?? '')) {
            return $this->json(['error' => 'Invalid CSRF token.'], Response::HTTP_FORBIDDEN);
        }

        $groupId = (int) ($data['groupId'] ?? 0);
        if ($groupId <= 0) {
            return $this->json(['error' => 'Invalid group ID.'], Response::HTTP_BAD_REQUEST);
        }

        $currentUser = CurrentUser::getCurrentUser($this->configuration);
        if (!$currentUser->perm instanceof MediumPermission) {
            return $this->json(['error' => 'Group permissions are not enabled.'], Response::HTTP_BAD_REQUEST);
        }

        if (!$currentUser->perm->deleteGroup($groupId)) {
            return $this->json([
                'error' => Translation::get('ad_group_error_delete'),
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }

        $this->adminLog?->log($this->currentUser, AdminLogType::GROUP_DELETE->value . ':' . $groupId);

        return $this->json(['success' => Translation::get('ad_group_deleted')], Response::HTTP_OK);
    }

    /**
     * @throws Exception
     */
    #[Route(path: 'group/categories', name: 'admin.api.group.categories', methods: ['GET'])]
    public function listCategories(): JsonResponse
    {
        $this->userHasGroupPermission();

        $category = new Category($this->configuration);
        $allCategories = $category->loadCategories();

        // loadCategories() returns a flat list ordered by position only, which
        // interleaves subcategories with unrelated parents. Re-order depth-first
        // so every subcategory directly follows its parent, and expose the
        // nesting level so the UI can indent accordingly.
        $categories = [];
        $listed = [];
        $appendChildren = static function (int $parentId, int $level) use (
            &$appendChildren,
            $allCategories,
            &$categories,
            &$listed,
        ): void {
            foreach ($allCategories as $cat) {
                if ((int) $cat['parent_id'] !== $parentId || isset($listed[(int) $cat['id']])) {
                    continue;
                }

                $listed[(int) $cat['id']] = true;
                $categories[] = [
                    'id' => $cat['id'],
                    'name' => $cat['name'],
                    'parent_id' => $cat['parent_id'],
                    'level' => $level,
                ];
                $appendChildren((int) $cat['id'], $level + 1);
            }
        };
        $appendChildren(0, 0);

        // Categories whose parent is inaccessible (deleted or filtered out by
        // permissions) would otherwise vanish — append them as roots.
        foreach ($allCategories as $cat) {
            if (!isset($listed[(int) $cat['id']])) {
                $categories[] = [
                    'id' => $cat['id'],
                    'name' => $cat['name'],
                    'parent_id' => $cat['parent_id'],
                    'level' => 0,
                ];
            }
        }

        return $this->json($categories, Response::HTTP_OK);
    }
}
