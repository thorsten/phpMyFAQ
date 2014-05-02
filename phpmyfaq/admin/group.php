<?php
/**
 * Displays the group management frontend
 *
 * PHP Version 5.4
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at http://mozilla.org/MPL/2.0/.
 *
 * @category  phpMyFAQ
 * @package   Administration
 * @author    Lars Tiedemann <php@larstiedemann.de>
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2005-2014 phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      http://www.phpmyfaq.de
 * @since     2005-12-15
 */

if (!defined('IS_VALID_PHPMYFAQ')) {
    $protocol = 'http';
    if (isset($_SERVER['HTTPS']) && strtoupper($_SERVER['HTTPS']) === 'ON'){
        $protocol = 'https';
    }
    header('Location: ' . $protocol . '://' . $_SERVER['HTTP_HOST'] . dirname($_SERVER['SCRIPT_NAME']));
    exit();
}

if (!$user->perm->checkRight($user->getUserId(), 'editgroup') &&
    !$user->perm->checkRight($user->getUserId(), 'delgroup') &&
    !$user->perm->checkRight($user->getUserId(), 'addgroup')) {
    exit();
}

// set some parameters
$groupSelectSize    = 10;
$memberSelectSize   = 7;
$descriptionRows    = 3;
$descriptionCols    = 15;
$defaultGroupAction = 'list';

// what shall we do?
// actions defined by url: group_action=
$groupAction = PMF_Filter::filterInput(INPUT_GET, 'group_action', FILTER_SANITIZE_STRING, $defaultGroupAction);
// actions defined by submit button
if (isset($_POST['group_action_deleteConfirm'])) {
    $groupAction = 'delete_confirm';
}
if (isset($_POST['cancel'])) {
    $groupAction = $defaultGroupAction;
}

// update group members
if ($groupAction == 'update_members' && $user->perm->checkRight($user->getUserId(), 'editgroup')) {
    $message      = '';
    $groupAction  = $defaultGroupAction;
    $groupId      = PMF_Filter::filterInput(INPUT_POST, 'group_id', FILTER_VALIDATE_INT, 0);
    $groupMembers = isset($_POST['group_members']) ? $_POST['group_members'] : [];
    
    if ($groupId == 0) {
        $message .= sprintf('<p class="alert alert-danger">%s</p>', $PMF_LANG['ad_user_error_noId']);
    } else {
        $user = new PMF_User($faqConfig);
        $perm = $user->perm;
        if (!$perm->removeAllUsersFromGroup($groupId)) {
            $message .= sprintf('<p class="alert alert-danger">%s</p>', $PMF_LANG['ad_msg_mysqlerr']);
        }
        foreach ($groupMembers as $memberId) {
            $perm->addToGroup((int)$memberId, $groupId);
        }
        $message .= sprintf('<p class="alert alert-success">%s <strong>%s</strong> %s</p>',
            $PMF_LANG['ad_msg_savedsuc_1'],
            $perm->getGroupName($groupId),
            $PMF_LANG['ad_msg_savedsuc_2']);
    }
}

// update group rights
if ($groupAction == 'update_rights' && $user->perm->checkRight($user->getUserId(), 'editgroup')) {
    $message     = '';
    $groupAction = $defaultGroupAction;
    $groupId     = PMF_Filter::filterInput(INPUT_POST, 'group_id', FILTER_VALIDATE_INT, 0);
    if ($groupId == 0) {
        $message .= sprintf('<p class="alert alert-danger">%s</p>', $PMF_LANG['ad_user_error_noId']);
    } else {
        $user = new PMF_User($faqConfig);
        $perm = $user->perm;
        $groupRights = isset($_POST['group_rights']) ? $_POST['group_rights'] : [];
        if (!$perm->refuseAllGroupRights($groupId)) {
            $message .= sprintf('<p class="alert alert-danger">%s</p>', $PMF_LANG['ad_msg_mysqlerr']);
        }
        foreach ($groupRights as $rightId) {
            $perm->grantGroupRight($groupId, (int)$rightId);
        }
        $message .= sprintf('<p class="alert alert-success">%s <strong>%s</strong> %s</p>',
            $PMF_LANG['ad_msg_savedsuc_1'],
            $perm->getGroupName($groupId),
            $PMF_LANG['ad_msg_savedsuc_2']);
    }
}

// update group data
if ($groupAction == 'update_data' && $user->perm->checkRight($user->getUserId(), 'editgroup')) {
    $message     = '';
    $groupAction = $defaultGroupAction;
    $groupId     = PMF_Filter::filterInput(INPUT_POST, 'group_id', FILTER_VALIDATE_INT, 0);
    if ($groupId == 0) {
        $message .= sprintf('<p class="alert alert-danger">%s</p>', $PMF_LANG['ad_user_error_noId']);
    } else {
        $groupData  = [];
        $dataFields = array('name', 'description', 'auto_join');
        foreach ($dataFields as $field) {
            $groupData[$field] = PMF_Filter::filterInput(INPUT_POST, $field, FILTER_SANITIZE_STRING, '');
        }
        $user = new PMF_User($faqConfig);
        $perm = $user->perm;
        if (!$perm->changeGroup($groupId, $groupData)) {
            $message .= sprintf('<p class="alert alert-danger">%s<br />%s</p>', $PMF_LANG['ad_msg_mysqlerr'], $perm->_db->error());
        } else {
            $message .= sprintf('<p class="alert alert-success">%s <strong>%s</strong> %s</p>',
                $PMF_LANG['ad_msg_savedsuc_1'],
                $perm->getGroupName($groupId),
                $PMF_LANG['ad_msg_savedsuc_2']);
        }
    }
}

// delete group confirmation
if ($groupAction == 'delete_confirm' && $user->perm->checkRight($user->getUserId(), 'delgroup')) {
    $message = '';
    $user    = new PMF_User_CurrentUser($faqConfig);
    $perm    = $user->perm;
    $groupId = PMF_Filter::filterInput(INPUT_POST, 'group_list_select', FILTER_VALIDATE_INT, 0);
    if ($groupId <= 0) {
        $message .= sprintf('<p class="alert alert-error">%s</p>', $PMF_LANG['ad_user_error_noId']);
        $groupAction = $defaultGroupAction;
    } else {
        $twig->loadTemplate('group/delete_confirm.twig')
            ->display(
                array(
                    'PMF_LANG'  => $PMF_LANG,
                    'csrfToken' => $user->getCsrfTokenFromSession(),
                    'groupData' => $perm->getGroupData($groupId),
                    'groupId'   => $groupId
                )
            );
    }
}

if ($groupAction == 'delete' && $user->perm->checkRight($user->getUserId(), 'delgroup')) {
    $message   = '';
    $user      = new PMF_User($faqConfig);
    $groupId   = PMF_Filter::filterInput(INPUT_POST, 'group_id', FILTER_VALIDATE_INT, 0);
    $csrfOkay  = true;
    $csrfToken = PMF_Filter::filterInput(INPUT_POST, 'csrf', FILTER_SANITIZE_STRING);
    if (!isset($_SESSION['phpmyfaq_csrf_token']) || $_SESSION['phpmyfaq_csrf_token'] !== $csrfToken) {
        $csrfOkay = false;
    }
    $groupAction = $defaultGroupAction;
    if ($groupId <= 0) {
        $message .= sprintf('<p class="alert alert-danger">%s</p>', $PMF_LANG['ad_user_error_noId']);
    } else {
        if (!$user->perm->deleteGroup($groupId) && !$csrfOkay) {
            $message .= sprintf('<p class="alert alert-danger">%s</p>', $PMF_LANG['ad_group_error_delete']);
        } else {
            $message .= sprintf('<p class="alert alert-success">%s</p>', $PMF_LANG['ad_group_deleted']);
        }
        $userError = $user->error();
        if ($userError != "") {
            $message .= sprintf('<p class="alert alert-danger">%s</p>', $userError);
        }
    }

}

if ($groupAction == 'addsave' && $user->perm->checkRight($user->getUserId(), 'addgroup')) {
    $user              = new PMF_User($faqConfig);
    $message           = '';
    $messages          = [];
    $group_name        = PMF_Filter::filterInput(INPUT_POST, 'group_name', FILTER_SANITIZE_STRING, '');
    $group_description = PMF_Filter::filterInput(INPUT_POST, 'group_description', FILTER_SANITIZE_STRING, '');
    $group_auto_join   = PMF_Filter::filterInput(INPUT_POST, 'group_auto_join', FILTER_SANITIZE_STRING, '');
    $csrfOkay          = true;
    $csrfToken         = PMF_Filter::filterInput(INPUT_POST, 'csrf', FILTER_SANITIZE_STRING);

    if (!isset($_SESSION['phpmyfaq_csrf_token']) || $_SESSION['phpmyfaq_csrf_token'] !== $csrfToken) {
        $csrfOkay = false;
    }
    // check group name
    if ($group_name == '') {
        $messages[] = $PMF_LANG['ad_group_error_noName'];
    }
    // ok, let's go
    if (count($messages) == 0 && $csrfOkay) {
        // create group
        $group_data = array(
            'name'        => $group_name,
            'description' => $group_description,
            'auto_join'   => $group_auto_join
        );

        if ($user->perm->addGroup($group_data) <= 0)
            $messages[] = $PMF_LANG['ad_adus_dberr'];
    }
    // no errors, show list
    if (count($messages) == 0) {
        $groupAction = $defaultGroupAction;
        $message     = sprintf('<p class="alert alert-success">%s</p>', $PMF_LANG['ad_group_suc']);
        // display error messages and show form again
    } else {
        $groupAction = 'add';
        $message     = '<p class="alert alert-error">';
        foreach ($messages as $err) {
            $message .= $err . '<br />';
        }
        $message .= '</p>';
    }
}

if (!isset($message))
    $message = '';

// show new group form
if ($groupAction == 'add' && $user->perm->checkRight($user->getUserId(), 'addgroup')) {
    $user = new PMF_User_CurrentUser($faqConfig);
    $twig->loadTemplate('group/add.twig')
        ->display(
            array(
                'PMF_LANG'         => $PMF_LANG,
                'csrfToken'        => $user->getCsrfTokenFromSession(),
                'descriptionCols'  => $descriptionCols,
                'descriptionRows'  => $descriptionRows,
                'groupAutoJoin'    => !empty($group_auto_join),
                'groupDescription' => isset($group_description) ? $group_description : '',
                'groupName'        => isset($group_name) ? $group_name : '',
                'message'          => $message
            )
        );
} // end if ($groupAction == 'add')

// show list of users
if ($groupAction == 'list') {
    $rightsData = $user->perm->getAllRightsData();

    foreach ($rightsData as $key => $right) {
        if (isset($PMF_LANG['rightsLanguage'][$right['name']])) {
            $rightsData[$key]['description'] = $PMF_LANG['rightsLanguage'][$right['name']];
        }
    }

    $twig->loadTemplate('group/list.twig')
        ->display(
            array(
                'PMF_LANG'         => $PMF_LANG,
                'descriptionCols'  => $descriptionCols,
                'descriptionRows'  => $descriptionRows,
                'groupAutoJoin'    => !empty($group_auto_join),
                'groupDescription' => isset($group_description) ? $group_description : '',
                'groupName'        => isset($group_name) ? $group_name : '',
                'groupSelectSize'  => $groupSelectSize,
                'memberSelectSize' => $memberSelectSize,
                'message'          => $message,
                'rightsData'       => $rightsData
            )
        );
}
