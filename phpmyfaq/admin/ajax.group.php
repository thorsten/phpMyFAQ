<?php
/**
 * AJAX: handling of Ajax group calls
 *
 * PHP Version 5.4
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at http://mozilla.org/MPL/2.0/.
 *
 * @category  phpMyFAQ
 * @package   Administration
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2009-2014 phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      http://www.phpmyfaq.de
 * @since     2009-04-06
 */

use Symfony\Component\HttpFoundation\JsonResponse;

if (!defined('IS_VALID_PHPMYFAQ')) {
    $protocol = 'http';
    if (isset($_SERVER['HTTPS']) && strtoupper($_SERVER['HTTPS']) === 'ON'){
        $protocol = 'https';
    }
    header('Location: ' . $protocol . '://' . $_SERVER['HTTP_HOST'] . dirname($_SERVER['SCRIPT_NAME']));
    exit();
}

$response = new JsonResponse;

$ajaxAction = PMF_Filter::filterInput(INPUT_GET, 'ajaxaction', FILTER_SANITIZE_STRING);
$groupId    = PMF_Filter::filterInput(INPUT_GET, 'group_id', FILTER_VALIDATE_INT);

if ($permission['adduser'] || $permission['edituser'] || $permission['deluser']) {
    
    $user      = new PMF_User($faqConfig);
    $userList  = $user->getAllUsers();
    $groupList = ($user->perm instanceof PMF_Perm_Medium) ? $user->perm->getAllGroups() : [];

    switch ($ajaxAction) {
        // Returns all groups
        case 'get_all_groups':
            $groups = [];
            foreach ($groupList as $groupId) {
                $data     = $user->perm->getGroupData($groupId);
                $groups[] = array(
                    'group_id' => $data['group_id'],
                    'name'     => $data['name']
                );
            }
            $response->setData($groups);
            break;
    
        // Return the group data
        case 'get_group_data':
            $response->setData($user->perm->getGroupData($groupId));
            break;
    
        // Return the group rights
        case 'get_group_rights':
            $response->setData($user->perm->getGroupRights($groupId));
            break;
    
        // Return all users
        case 'get_all_users':
            $users = [];
            foreach ($userList as $single_user) {
                $user->getUserById($single_user);
                $users[] = array('user_id' => $user->getUserId(),
                                 'login'   => $user->getLogin());
            }
            echo $response->setData($users);
            break;
    
        // Returns all group members
        case 'get_all_members':
            $memberList = $user->perm->getGroupMembers($groupId);
            $members    = [];
            foreach ($memberList as $single_member) {
                $user->getUserById($single_member);
                $members[] = array('user_id' => $user->getUserId(),
                                   'login'   => $user->getLogin());
            }
            echo $response->setData($members);
            break;
    }
}

$response->send();
