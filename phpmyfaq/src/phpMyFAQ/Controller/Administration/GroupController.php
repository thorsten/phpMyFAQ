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
 * @copyright 2023-2024 phpMyFAQ Team
 * @license   https://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2023-10-27
 */

namespace phpMyFAQ\Controller\Administration;

use phpMyFAQ\Configuration;
use phpMyFAQ\Controller\AbstractController;
use phpMyFAQ\Permission\MediumPermission;
use phpMyFAQ\User\CurrentUser;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class GroupController extends AbstractController
{
    #[Route('admin/api/group/groups')]
    public function listGroups(): JsonResponse
    {
        $this->userHasGroupPermission();

        $response = new JsonResponse();
        $user = CurrentUser::getCurrentUser(Configuration::getConfigurationInstance());

        $groupList = ($user->perm instanceof MediumPermission) ? $user->perm->getAllGroups($user) : [];

        $groups = [];
        foreach ($groupList as $groupId) {
            $data = $user->perm->getGroupData($groupId);
            $groups[] = [
                'group_id' => $data['group_id'],
                'name' => $data['name'],
            ];
        }
        $response->setStatusCode(Response::HTTP_OK);
        $response->setData($groups);

        return $response;
    }

    #[Route('admin/api/group/users')]
    public function listUsers(Request $request): JsonResponse
    {
        $this->userHasGroupPermission();

        $response = new JsonResponse();
        $user = CurrentUser::getCurrentUser(Configuration::getConfigurationInstance());

        $users = [];
        foreach ($user->getAllUsers(true, false) as $singleUser) {
            $user->getUserById($singleUser, true);
            $users[] = [
                'user_id' => $user->getUserId(),
                'login' => $user->getLogin(),
            ];
        }
        $response->setStatusCode(Response::HTTP_OK);
        $response->setData($users);

        return $response;
    }

    #[Route('admin/api/group/data')]
    public function groupData(Request $request): JsonResponse
    {
        $this->userHasGroupPermission();

        $response = new JsonResponse();
        $user = CurrentUser::getCurrentUser(Configuration::getConfigurationInstance());

        $groupId = $request->get('groupId');

        $response->setStatusCode(Response::HTTP_OK);
        $response->setData($user->perm->getGroupData($groupId));

        return $response;
    }

    #[Route('admin/api/group/members')]
    public function listMembers(Request $request): JsonResponse
    {
        $this->userHasGroupPermission();

        $response = new JsonResponse();
        $user = CurrentUser::getCurrentUser(Configuration::getConfigurationInstance());

        $groupId = $request->get('groupId');

        $members = [];
        foreach ($user->perm->getGroupMembers($groupId) as $singleMember) {
            $user->getUserById($singleMember, true);
            $members[] = [
                'user_id' => $user->getUserId(),
                'login' => $user->getLogin(),
            ];
        }

        $response->setStatusCode(Response::HTTP_OK);
        $response->setData($members);

        return $response;
    }

    #[Route('admin/api/group/permissions')]
    public function listPermissions(Request $request): JsonResponse
    {
        $this->userHasGroupPermission();

        $response = new JsonResponse();
        $user = CurrentUser::getCurrentUser(Configuration::getConfigurationInstance());

        $groupId = $request->get('groupId');

        $response->setStatusCode(Response::HTTP_OK);
        $response->setData($user->perm->getGroupRights($groupId));

        return $response;
    }
}
