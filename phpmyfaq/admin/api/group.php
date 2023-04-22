<?php

/**
 * Private phpMyFAQ Admin API: handling of Ajax group calls.
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at https://mozilla.org/MPL/2.0/.
 *
 * @package   phpMyFAQ
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2009-2023 phpMyFAQ Team
 * @license   https://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2009-04-06
 */

use phpMyFAQ\Filter;
use phpMyFAQ\Permission\MediumPermission;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

if (!defined('IS_VALID_PHPMYFAQ')) {
    http_response_code(400);
    exit();
}

//
// Create Request & Response
//
$response = new JsonResponse();
$request = Request::createFromGlobals();

$ajaxAction = Filter::filterVar($request->query->get('ajaxaction'), FILTER_SANITIZE_SPECIAL_CHARS);
$groupId = Filter::filterVar($request->query->get('group_id'), FILTER_VALIDATE_INT);

if (
    $user->perm->hasPermission($user->getUserId(), 'add_user') ||
    $user->perm->hasPermission($user->getUserId(), 'edit_user') ||
    $user->perm->hasPermission($user->getUserId(), 'delete_user') ||
    $user->perm->hasPermission($user->getUserId(), 'editgroup')
) {
    // pass the user id of the current user, so it'll check which group he belongs to
    $groupList = ($user->perm instanceof MediumPermission) ? $user->perm->getAllGroups($user) : [];
    $userList = $user->getAllUsers(true, false);

    // Returns all groups
    if ('get_all_groups' == $ajaxAction) {
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
    }

    // Return the group data
    if ('get_group_data' == $ajaxAction) {
        $response->setStatusCode(Response::HTTP_OK);
        $response->setData($user->perm->getGroupData($groupId));
    }

    // Return the group rights
    if ('get_group_rights' == $ajaxAction) {
        $response->setStatusCode(Response::HTTP_OK);
        $response->setData($user->perm->getGroupRights($groupId));
    }

    // Return all users
    if ('get_all_users' == $ajaxAction) {
        $users = [];
        foreach ($userList as $singleUser) {
            $user->getUserById($singleUser, true);
            $users[] = [
                'user_id' => $user->getUserId(),
                'login' => $user->getLogin(),
            ];
        }
        $response->setStatusCode(Response::HTTP_OK);
        $response->setData($users);
    }

    // Returns all group members
    if ('get_all_members' == $ajaxAction) {
        $memberList = $user->perm->getGroupMembers($groupId);
        $members = [];
        foreach ($memberList as $singleMember) {
            $user->getUserById($singleMember, true);
            $members[] = [
                'user_id' => $user->getUserId(),
                'login' => $user->getLogin(),
            ];
        }
        $response->setStatusCode(Response::HTTP_OK);
        $response->setData($members);
    }

    $response->send();
}
