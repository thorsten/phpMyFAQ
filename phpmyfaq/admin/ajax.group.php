<?php

/**
 * AJAX: handling of Ajax group calls.
 *
 * 
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at http://mozilla.org/MPL/2.0/.
 *
 * @category  phpMyFAQ
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2009-2018 phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2009-04-06
 */

use phpMyFAQ\Filter;
use phpMyFAQ\Permission\MediumPermission;
use phpMyFAQ\User;

if (!defined('IS_VALID_PHPMYFAQ')) {
    $protocol = 'http';
    if (isset($_SERVER['HTTPS']) && strtoupper($_SERVER['HTTPS']) === 'ON') {
        $protocol = 'https';
    }
    header('Location: '.$protocol.'://'.$_SERVER['HTTP_HOST'].dirname($_SERVER['SCRIPT_NAME']));
    exit();
}

$ajaxAction = Filter::filterInput(INPUT_GET, 'ajaxaction', FILTER_SANITIZE_STRING);
$groupId = Filter::filterInput(INPUT_GET, 'group_id', FILTER_VALIDATE_INT);

if ($user->perm->checkRight($user->getUserId(), 'add_user') ||
    $user->perm->checkRight($user->getUserId(), 'edit_user') ||
    $user->perm->checkRight($user->getUserId(), 'deluser') ||
    $user->perm->checkRight($user->getUserId(), 'editgroup')) {
    
    // pass the user id of the current user so it'll check which group he belongs to
    $groupList = ($user->perm instanceof MediumPermission) ? $user->perm->getAllGroups($user->getUserId()) : [];
    $userList = $user->getAllUsers(true, false);

    if (!$faqConfig->get('main.enableCategoryRestrictions')){
        $user = new User($faqConfig);
        $groupList = ($user->perm instanceof MediumPermission) ? $user->perm->getAllGroups() : [];
    }

    // Returns all groups
    if ('get_all_groups' == $ajaxAction) {
        $groups = [];
        foreach ($groupList as $groupId) {
            $data = $user->perm->getGroupData($groupId);
            $groups[] = array(
                'group_id' => $data['group_id'],
                'name' => $data['name'],
            );
        }
        echo json_encode($groups);
    }

    // Return the group data
    if ('get_group_data' == $ajaxAction) {
        echo json_encode($user->perm->getGroupData($groupId));
    }

    // Return the group rights
    if ('get_group_rights' == $ajaxAction) {
        echo json_encode($user->perm->getGroupRights($groupId));
    }

    // Return all users
    if ('get_all_users' == $ajaxAction) {
        $users = [];
        foreach ($userList as $single_user) {
            $user->getUserById($single_user, true);
            $users[] = array('user_id' => $user->getUserId(),
                             'login' => $user->getLogin(), );
        }
        echo json_encode($users);
    }

    // Returns all group members
    if ('get_all_members' == $ajaxAction) {
        $memberList = $user->perm->getGroupMembers($groupId);
        $members = [];
        foreach ($memberList as $single_member) {
            $user->getUserById($single_member, true);
            $members[] = array('user_id' => $user->getUserId(),
                               'login' => $user->getLogin(), );
        }
        echo json_encode($members);
    }
}
