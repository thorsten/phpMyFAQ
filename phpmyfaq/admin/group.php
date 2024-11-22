<?php

/**
 * Displays the group management frontend.
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at https://mozilla.org/MPL/2.0/.
 *
 * @package   phpMyFAQ
 * @author    Lars Tiedemann <php@larstiedemann.de>
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @author    Charles Boin <c.boin@h-tube.com>
 * @copyright 2005-2024 phpMyFAQ Team
 * @license   https://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2005-12-15
 */

use phpMyFAQ\Configuration;
use phpMyFAQ\Enums\PermissionType;
use phpMyFAQ\Filter;
use phpMyFAQ\Session\Token;
use phpMyFAQ\Strings;
use phpMyFAQ\Template\Extensions\PermissionTranslationTwigExtension;
use phpMyFAQ\Template\TwigWrapper;
use phpMyFAQ\Translation;
use phpMyFAQ\User;
use phpMyFAQ\User\CurrentUser;

if (!defined('IS_VALID_PHPMYFAQ')) {
    http_response_code(400);
    exit();
}

$faqConfig = Configuration::getConfigurationInstance();
$user = CurrentUser::getCurrentUser($faqConfig);

$templateVars = [];

if (
    !$user->perm->hasPermission($user->getUserId(), PermissionType::GROUP_ADD->value) &&
    !$user->perm->hasPermission($user->getUserId(), PermissionType::GROUP_DELETE->value) &&
    !$user->perm->hasPermission($user->getUserId(), PermissionType::GROUP_EDIT->value)
) {
    require __DIR__ . '/no-permission.php';
}

// set some parameters
$defaultGroupAction = 'list';
$groupActionList = [
    'update_members',
    'update_rights',
    'update_data',
    'delete_confirm',
    'delete',
    'addsave',
    'add',
    'list',
    'import-ldap-groups',
];

// what shall we do?
// actions defined by url: group_action=
$groupAction = Filter::filterInput(INPUT_GET, 'group_action', FILTER_SANITIZE_SPECIAL_CHARS, $defaultGroupAction);

$currentUser = new CurrentUser($faqConfig);

// actions defined by submit button
if (isset($_POST['group_action_deleteConfirm'])) {
    $groupAction = 'delete_confirm';
}
if (isset($_POST['cancel'])) {
    $groupAction = $defaultGroupAction;
}

if (!in_array($groupAction, $groupActionList)) {
    // @Todo: implement Error message
}

// update group members
if (
    $groupAction == 'update_members' &&
    $user->perm->hasPermission($user->getUserId(), PermissionType::GROUP_EDIT->value)
) {
    $message = '';
    $groupAction = $defaultGroupAction;
    $groupId = Filter::filterInput(INPUT_POST, 'group_id', FILTER_VALIDATE_INT);
    $groupMembers = $_POST['group_members'] ?? [];

    if ($groupId == 0) {
        $message .= sprintf('<div class="alert alert-danger">%s</div>', Translation::get('ad_user_error_noId'));
    } else {
        $user = new User($faqConfig);
        $perm = $user->perm;
        if (!$perm->removeAllUsersFromGroup($groupId)) {
            $message .= sprintf('<div class="alert alert-danger">%s</div>', Translation::get('ad_msg_mysqlerr'));
        }
        foreach ($groupMembers as $memberId) {
            $perm->addToGroup((int)$memberId, $groupId);
        }
        $message .= sprintf(
            '<p class="alert alert-success">%s <strong>%s</strong> %s</p>',
            Translation::get('ad_msg_savedsuc_1'),
            $perm->getGroupName($groupId),
            Translation::get('ad_msg_savedsuc_2')
        );
    }
}

// update group rights
if (
    $groupAction == 'update_rights' &&
    $user->perm->hasPermission($user->getUserId(), PermissionType::GROUP_EDIT->value)
) {
    $message = '';
    $groupAction = $defaultGroupAction;
    $groupId = Filter::filterInput(INPUT_POST, 'group_id', FILTER_VALIDATE_INT);
    if ($groupId == 0) {
        $message .= sprintf('<div class="alert alert-danger">%s</div>', Translation::get('ad_user_error_noId'));
    } else {
        $user = new User($faqConfig);
        $perm = $user->perm;
        $groupRights = $_POST['group_rights'] ?? [];
        if (!$perm->refuseAllGroupRights($groupId)) {
            $message .= sprintf('<div class="alert alert-danger">%s</div>', Translation::get('ad_msg_mysqlerr'));
        }
        foreach ($groupRights as $rightId) {
            $perm->grantGroupRight($groupId, (int)$rightId);
        }
        $message .= sprintf(
            '<p class="alert alert-success">%s <strong>%s</strong> %s</p>',
            Translation::get('ad_msg_savedsuc_1'),
            $perm->getGroupName($groupId),
            Translation::get('ad_msg_savedsuc_2')
        );
    }
}

// update group data
if (
    $groupAction == 'update_data' &&
    $user->perm->hasPermission($user->getUserId(), PermissionType::GROUP_EDIT->value)
) {
    $message = '';
    $groupAction = $defaultGroupAction;
    $groupId = Filter::filterInput(INPUT_POST, 'group_id', FILTER_VALIDATE_INT);
    if ($groupId == 0) {
        $message .= sprintf('<div class="alert alert-danger">%s</div>', Translation::get('ad_user_error_noId'));
    } else {
        $groupData = [];
        $dataFields = ['name', 'description', 'auto_join'];
        foreach ($dataFields as $field) {
            $groupData[$field] = Filter::filterInput(INPUT_POST, $field, FILTER_SANITIZE_SPECIAL_CHARS, '');
        }
        $user = new User($faqConfig);
        $perm = $user->perm;
        if (!$perm->changeGroup($groupId, $groupData)) {
            $message .= sprintf(
                '<div class="alert alert-danger">%s %s</div>',
                Translation::get('ad_msg_mysqlerr'),
                $faqConfig->getDb()->error()
            );
        } else {
            $message .= sprintf(
                '<p class="alert alert-success">%s <strong>%s</strong> %s</p>',
                Translation::get('ad_msg_savedsuc_1'),
                $perm->getGroupName($groupId),
                Translation::get('ad_msg_savedsuc_2')
            );
        }
    }
}

// delete group confirmation
if (
    $groupAction == 'delete_confirm' &&
    $user->perm->hasPermission($user->getUserId(), PermissionType::GROUP_DELETE->value)
) {
    $message = '';
    $user = new CurrentUser($faqConfig);
    $perm = $user->perm;
    $groupId = Filter::filterInput(INPUT_POST, 'group_list_select', FILTER_VALIDATE_INT, 0);
    if ($groupId <= 0) {
        $message .= sprintf('<div class="alert alert-danger">%s</div>', Translation::get('ad_user_error_noId'));
        $groupAction = $defaultGroupAction;
    } else {
        $groupData = $perm->getGroupData($groupId);
        $showDeleteGroupForm = true;
        $templateVars = [
            ...$templateVars,
            'ad_group_deleteGroup' => Translation::get('ad_group_deleteGroup'),
            'groupName' => Strings::htmlentities($groupData['name']),
            'ad_group_deleteQuestion' => Translation::get('ad_group_deleteQuestion'),
            'groupId' => $groupId,
            'csrfDeleteGroup' => Token::getInstance($container->get('session'))->getTokenString('delete-group'),
            'ad_gen_no' => Translation::get('ad_gen_no'),
            'ad_gen_yes' => Translation::get('ad_gen_yes'),
            'showDeleteGroupForm' => $showDeleteGroupForm
        ];
    }
}

if ($groupAction == 'delete' && $user->perm->hasPermission($user->getUserId(), PermissionType::GROUP_DELETE->value)) {
    $message = '';
    $user = new User($faqConfig);
    $groupId = Filter::filterInput(INPUT_POST, 'group_id', FILTER_VALIDATE_INT);
    $csrfOkay = true;
    $csrfToken = Filter::filterInput(INPUT_POST, 'csrf', FILTER_SANITIZE_SPECIAL_CHARS);
    if (!Token::getInstance()->verifyToken('delete-group', $csrfToken)) {
        $csrfOkay = false;
    }
    $groupAction = $defaultGroupAction;
    if ($groupId <= 0) {
        $message .= sprintf('<div class="alert alert-danger">%s</div>', Translation::get('ad_user_error_noId'));
    } else {
        if (!$user->perm->deleteGroup($groupId) && !$csrfOkay) {
            $message .= sprintf('<div class="alert alert-danger">%s</div>', Translation::get('ad_group_error_delete'));
        } else {
            $message .= sprintf('<div class="alert alert-danger">%s</div>', Translation::get('ad_group_deleted'));
        }
        $userError = $user->error();
        if ($userError != '') {
            $message .= sprintf('<p class="alert alert-danger">%s</p>', $userError);
        }
    }
}

if ($groupAction == 'addsave' && $user->perm->hasPermission($user->getUserId(), PermissionType::GROUP_ADD->value)) {
    $user = new User($faqConfig);
    $message = '';
    $messages = [];
    $groupName = Filter::filterInput(INPUT_POST, 'group_name', FILTER_SANITIZE_SPECIAL_CHARS, '');
    $groupDescription = Filter::filterInput(INPUT_POST, 'group_description', FILTER_SANITIZE_SPECIAL_CHARS, '');
    $groupAutoJoin = Filter::filterInput(INPUT_POST, 'group_auto_join', FILTER_SANITIZE_SPECIAL_CHARS, '');
    $csrfOkay = true;
    $csrfToken = Filter::filterInput(INPUT_POST, 'csrf', FILTER_SANITIZE_SPECIAL_CHARS);

    if (!Token::getInstance()->verifyToken('add-group', $csrfToken)) {
        $csrfOkay = false;
    }
    // check group name
    if ($groupName == '') {
        $messages[] = Translation::get('ad_group_error_noName');
    }
    // ok, let's go
    if (count($messages) == 0 && $csrfOkay) {
        // create group
        $groupData = [
            'name' => $groupName,
            'description' => $groupDescription,
            'auto_join' => $groupAutoJoin,
        ];

        if ($user->perm->addGroup($groupData) <= 0) {
            $messages[] = Translation::get('ad_adus_dberr');
        }
    }

    // no errors, show list
    if (count($messages) === 0) {
        $groupAction = $defaultGroupAction;
        $message = sprintf('<div class="alert alert-success">%s</div>', Translation::get('ad_group_suc'));
        // display error messages and show form again
    } else {
        $groupAction = 'add';
        $message = '<p class="alert alert-danger">';
        foreach ($messages as $err) {
            $message .= $err . '<br>';
        }
        $message .= '</p>';
    }
}

if (!isset($message)) {
    $message = '';
}

// show new group form
if ($groupAction == 'add' && $user->perm->hasPermission($user->getUserId(), PermissionType::GROUP_ADD->value)) {
    $user = new CurrentUser($faqConfig);
    $templateVars = [
        ...$templateVars,
        'ad_group_add' => Translation::get('ad_group_add'),
        'csrfAddGroup' => Token::getInstance()->getTokenString('add-group'),
        'ad_group_name' => Translation::get('ad_group_name'),
        'groupName' => $groupName ?? '',
        'ad_group_description' => Translation::get('ad_group_description'),
        'groupDescription' => $groupDescription ?? '',
        'ad_group_autoJoin' => Translation::get('ad_group_autoJoin'),
        'autoJoinCheckbox' => ((isset($groupAutoJoin) && $groupAutoJoin) ? ' checked' : ''),
        'ad_gen_cancel' => Translation::get('ad_gen_cancel'),
        'ad_gen_save' => Translation::get('ad_gen_save'),
        'groupAction' => $groupAction
    ];
}

// show list of users
if ('list' === $groupAction) {
    $templateVars = [
        ...$templateVars,
        'ad_menu_group_administration' => Translation::get('ad_menu_group_administration'),
        'ad_group_add_link' => Translation::get('ad_group_add_link'),
        'message' => $message,
        'ad_groups' => Translation::get('ad_groups'),
        'ad_gen_delete' => Translation::get('ad_gen_delete'),
        'ad_group_details' => Translation::get('ad_group_details'),
        'ad_group_name' => Translation::get('ad_group_name'),
        'groupName' => $groupName ?? '',
        'ad_group_description' => Translation::get('ad_group_description'),
        'groupDescription' => $groupDescription ?? '',
        'autoJoinCheckbox' => ((isset($groupAutoJoin) && $groupAutoJoin) ? ' checked' : ''),
        'ad_group_autoJoin' => Translation::get('ad_group_autoJoin'),
        'ad_gen_save' => Translation::get('ad_gen_save'),
        'ad_group_membership' => Translation::get('ad_group_membership'),
        'ad_group_addMember' => Translation::get('ad_group_addMember'),
        'ad_group_members' => Translation::get('ad_group_members'),
        'ad_group_removeMember' => Translation::get('ad_group_removeMember'),
        'ad_group_rights' => Translation::get('ad_group_rights'),
        'ad_user_checkall' => Translation::get('ad_user_checkall'),
        'ad_user_uncheckall' => Translation::get('ad_user_uncheckall'),
        'rightData' => $user->perm->getAllRightsData(),
        'groupAction' => $groupAction
    ];
}

$twig = new TwigWrapper(PMF_ROOT_DIR . '/assets/templates');
$twig->addExtension(new PermissionTranslationTwigExtension());
$template = $twig->loadTemplate('@admin/user/group.twig');

echo $template->render($templateVars);
