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

use phpMyFAQ\Controller\AbstractController;
use phpMyFAQ\Core\Exception;
use phpMyFAQ\Permission\MediumPermission;
use phpMyFAQ\User\CurrentUser;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class GroupController extends AbstractController
{
    /**
     * @throws Exception
     */
    #[Route(path: 'admin/api/group/groups')]
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
    #[Route(path: 'admin/api/group/users')]
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
    #[Route(path: 'admin/api/group/data')]
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
    #[Route(path: 'admin/api/group/members')]
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
    #[Route(path: 'admin/api/group/permissions')]
    public function listPermissions(Request $request): JsonResponse
    {
        $this->userHasGroupPermission();

        $currentUser = CurrentUser::getCurrentUser($this->configuration);

        $groupId = (int) $request->attributes->get('groupId');

        return $this->json($currentUser->perm->getGroupRights($groupId), Response::HTTP_OK);
    }
}
