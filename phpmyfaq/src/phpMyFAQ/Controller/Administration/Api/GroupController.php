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
use phpMyFAQ\Permission\MediumPermission;
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
    #[Route(path: 'group/category-restrictions', name: 'admin.api.group.category-restrictions', methods: ['GET'])]
    public function listCategoryRestrictions(Request $request): JsonResponse
    {
        $this->userHasGroupPermission();

        $currentUser = CurrentUser::getCurrentUser($this->configuration);

        $groupId = (int) $request->attributes->get('groupId');

        if (!$currentUser->perm instanceof MediumPermission) {
            return $this->json([], Response::HTTP_OK);
        }

        return $this->json($currentUser->perm->getAllCategoryRestrictions($groupId), Response::HTTP_OK);
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
        $groupId = (int) ($data['groupId'] ?? 0);
        $rightId = (int) ($data['rightId'] ?? 0);
        $categoryIds = array_map('intval', $data['categoryIds'] ?? []);

        if ($groupId <= 0 || $rightId <= 0) {
            return $this->json(['error' => 'Invalid group or right ID.'], Response::HTTP_BAD_REQUEST);
        }

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
    #[Route(path: 'group/categories', name: 'admin.api.group.categories', methods: ['GET'])]
    public function listCategories(): JsonResponse
    {
        $this->userHasGroupPermission();

        $category = new Category($this->configuration);
        $allCategories = $category->loadCategories();

        $categories = [];
        foreach ($allCategories as $cat) {
            $categories[] = [
                'id' => $cat['id'],
                'name' => $cat['name'],
                'parent_id' => $cat['parent_id'],
            ];
        }

        return $this->json($categories, Response::HTTP_OK);
    }
}
