<?php
/**
 * Displays the group management frontend.
 *
 * PHP Version 5.5
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at http://mozilla.org/MPL/2.0/.
 *
 * @category  phpMyFAQ
 * @author    Lars Tiedemann <php@larstiedemann.de>
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2005-2019 phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2005-12-15
 */

if (!defined('IS_VALID_PHPMYFAQ')) {
    $protocol = 'http';
    if (isset($_SERVER['HTTPS']) && strtoupper($_SERVER['HTTPS']) === 'ON') {
        $protocol = 'https';
    }
    header('Location: '.$protocol.'://'.$_SERVER['HTTP_HOST'].dirname($_SERVER['SCRIPT_NAME']));
    exit();
}

if (!$user->perm->checkRight($user->getUserId(), 'editgroup') &&
    !$user->perm->checkRight($user->getUserId(), 'delgroup') &&
    !$user->perm->checkRight($user->getUserId(), 'addgroup')) {
    exit();
}

// set some parameters
$groupSelectSize = 10;
$memberSelectSize = 7;
$descriptionRows = 3;
$descriptionCols = 15;
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
    $message = '';
    $groupAction = $defaultGroupAction;
    $groupId = PMF_Filter::filterInput(INPUT_POST, 'group_id', FILTER_VALIDATE_INT, 0);
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
            $perm->addToGroup((int) $memberId, $groupId);
        }
        $message .= sprintf('<p class="alert alert-success">%s <strong>%s</strong> %s</p>',
            $PMF_LANG['ad_msg_savedsuc_1'],
            $perm->getGroupName($groupId),
            $PMF_LANG['ad_msg_savedsuc_2']);
    }
}

// update group rights
if ($groupAction == 'update_rights' && $user->perm->checkRight($user->getUserId(), 'editgroup')) {
    $message = '';
    $groupAction = $defaultGroupAction;
    $groupId = PMF_Filter::filterInput(INPUT_POST, 'group_id', FILTER_VALIDATE_INT, 0);
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
            $perm->grantGroupRight($groupId, (int) $rightId);
        }
        $message .= sprintf('<p class="alert alert-success">%s <strong>%s</strong> %s</p>',
            $PMF_LANG['ad_msg_savedsuc_1'],
            $perm->getGroupName($groupId),
            $PMF_LANG['ad_msg_savedsuc_2']);
    }
}

// update group data
if ($groupAction == 'update_data' && $user->perm->checkRight($user->getUserId(), 'editgroup')) {
    $message = '';
    $groupAction = $defaultGroupAction;
    $groupId = PMF_Filter::filterInput(INPUT_POST, 'group_id', FILTER_VALIDATE_INT, 0);
    if ($groupId == 0) {
        $message .= sprintf('<p class="alert alert-danger">%s</p>', $PMF_LANG['ad_user_error_noId']);
    } else {
        $groupData = [];
        $dataFields = array('name', 'description', 'auto_join');
        foreach ($dataFields as $field) {
            $groupData[$field] = PMF_Filter::filterInput(INPUT_POST, $field, FILTER_SANITIZE_STRING, '');
        }
        $user = new PMF_User($faqConfig);
        $perm = $user->perm;
        if (!$perm->changeGroup($groupId, $groupData)) {
            $message .= sprintf(
            '<p class="alert alert-danger">%s<br />%s</p>',
            $PMF_LANG['ad_msg_mysqlerr'],
            $db->error()
            );
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
    $user = new PMF_User_CurrentUser($faqConfig);
    $perm = $user->perm;
    $groupId = PMF_Filter::filterInput(INPUT_POST, 'group_list_select', FILTER_VALIDATE_INT, 0);
    if ($groupId <= 0) {
        $message    .= sprintf('<p class="alert alert-danger">%s</p>', $PMF_LANG['ad_user_error_noId']);
        $groupAction = $defaultGroupAction;
    } else {
        $group_data = $perm->getGroupData($groupId);
        ?>
        <header class="row">
            <div class="col-lg-12">
                <h2 class="page-header">
                    <i aria-hidden="true" class="fa fa-users fa-fw"></i>
                    <?php echo $PMF_LANG['ad_group_deleteGroup'] ?> "<?php echo $group_data['name'] ?>"
                </h2>
            </div>
        </header>

        <div class="row">
            <div class="col-lg-12">
                <p><?php echo $PMF_LANG['ad_group_deleteQuestion'] ?></p>
                <form action ="?action=group&amp;group_action=delete" method="post">
                    <input type="hidden" name="group_id" value="<?php echo $groupId ?>">
                    <input type="hidden" name="csrf" value="<?php echo $user->getCsrfTokenFromSession()?>">
                    <p>
                        <button class="btn btn-inverse" type="submit" name="cancel">
                            <?php echo $PMF_LANG['ad_gen_cancel'] ?>
                        </button>
                        <button class="btn btn-primary" type="submit">
                            <?php echo $PMF_LANG['ad_gen_save'] ?>
                        </button>
                    </p>
                </form>
            </div>
        </div>
<?php

    }
}

if ($groupAction == 'delete' && $user->perm->checkRight($user->getUserId(), 'delgroup')) {
    $message = '';
    $user = new PMF_User($faqConfig);
    $groupId = PMF_Filter::filterInput(INPUT_POST, 'group_id', FILTER_VALIDATE_INT, 0);
    $csrfOkay = true;
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
        if ($userError != '') {
            $message .= sprintf('<p class="alert alert-danger">%s</p>', $userError);
        }
    }
}

if ($groupAction == 'addsave' && $user->perm->checkRight($user->getUserId(), 'addgroup')) {
    $user = new PMF_User($faqConfig);
    $message = '';
    $messages = [];
    $group_name = PMF_Filter::filterInput(INPUT_POST, 'group_name', FILTER_SANITIZE_STRING, '');
    $group_description = PMF_Filter::filterInput(INPUT_POST, 'group_description', FILTER_SANITIZE_STRING, '');
    $group_auto_join = PMF_Filter::filterInput(INPUT_POST, 'group_auto_join', FILTER_SANITIZE_STRING, '');
    $csrfOkay = true;
    $csrfToken = PMF_Filter::filterInput(INPUT_POST, 'csrf', FILTER_SANITIZE_STRING);

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
            'name' => $group_name,
            'description' => $group_description,
            'auto_join' => $group_auto_join,
        );

        if ($user->perm->addGroup($group_data) <= 0) {
            $messages[] = $PMF_LANG['ad_adus_dberr'];
        }
    }
    // no errors, show list
    if (count($messages) == 0) {
        $groupAction = $defaultGroupAction;
        $message = sprintf('<p class="alert alert-success">%s</p>', $PMF_LANG['ad_group_suc']);
    // display error messages and show form again
    } else {
        $groupAction = 'add';
        $message = '<p class="alert alert-danger">';
        foreach ($messages as $err) {
            $message .= $err.'<br />';
        }
        $message .= '</p>';
    }
}

if (!isset($message)) {
    $message = '';
}

// show new group form
if ($groupAction == 'add' && $user->perm->checkRight($user->getUserId(), 'addgroup')) {
    $user = new PMF_User_CurrentUser($faqConfig);
    ?>
        <header class="row">
            <div class="col-lg-12">
                <h2 class="page-header"><i aria-hidden="true" class="fa fa-users fa-fw"></i> <?php echo $PMF_LANG['ad_group_add']?></h2>
            </div>
        </header>

        <div class="row">
            <div class="col-lg-12">
                <div id="user_message"><?php echo $message;
    ?></div>
                <form class="form-horizontal" name="group_create" action="?action=group&amp;group_action=addsave" method="post">
                    <input type="hidden" name="csrf" value="<?php echo $user->getCsrfTokenFromSession();
    ?>" />

                    <div class="form-group">
                        <label class="col-lg-2 control-label" for="group_name"><?php echo $PMF_LANG['ad_group_name'];
    ?></label>
                        <div class="col-lg-3">
                            <input type="text" name="group_name" id="group_name" autofocus class="form-control"
                                   value="<?php echo(isset($group_name) ? $group_name : '');
    ?>" tabindex="1">
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="col-lg-2 control-label" for="group_description"><?php echo $PMF_LANG['ad_group_description'];
    ?></label>
                        <div class="col-lg-3">
                            <textarea name="group_description" id="group_description" cols="<?php echo $descriptionCols;
    ?>"
                                      rows="<?php echo $descriptionRows;
    ?>" tabindex="2"  class="form-control"
                                ><?php echo(isset($group_description) ? $group_description : '');
    ?></textarea>
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="col-lg-2 control-label" for="group_auto_join"><?php echo $PMF_LANG['ad_group_autoJoin'];
    ?></label>
                        <div class="col-lg-3">
                            <div class="checkbox">
                                <label>
                                    <input type="checkbox" name="group_auto_join" id="group_auto_join" value="1" tabindex="3"
                                    <?php echo((isset($group_auto_join) && $group_auto_join) ? ' checked="checked"' : '');
    ?>>
                                </label>
                            </div>
                        </div>
                    </div>

                    <div class="form-group">
                        <div class="col-lg-offset-2 col-lg-3">
                            <button class="btn btn-primary" type="submit">
                                <?php echo $PMF_LANG['ad_gen_save'];
    ?>
                            </button>
                            <button class="btn btn-info" type="reset" name="cancel">
                                <?php echo $PMF_LANG['ad_gen_cancel'];
    ?>
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
<?php

} // end if ($groupAction == 'add')

// show list of users
if ('list' === $groupAction) {
    ?>

        <header class="row">
            <div class="col-lg-12">
                <h2 class="page-header">
                    <i aria-hidden="true" class="fa fa-users"></i> <?php echo $PMF_LANG['ad_menu_group_administration'] ?>
                    <div class="pull-right">
                        <a class="btn btn-success" href="?action=group&amp;group_action=add">
                            <i aria-hidden="true" class="fa fa-plus"></i> <?php echo $PMF_LANG['ad_group_add_link'] ?>
                        </a>
                    </div>
                </h2>
            </div>
        </header>

        <script src="assets/js/user.js"></script>
        <script src="assets/js/groups.js"></script>

        <div id="user_message"><?php echo $message ?></div>

        <div class="row">

            <div class="col-lg-4" id="group_list">
                <form id="group_select" name="group_select" action="?action=group&amp;group_action=delete_confirm"
                      method="post">
                    <div class="panel panel-default">
                        <div class="panel-heading">
                            <?php echo $PMF_LANG['ad_groups'] ?>
                        </div>
                        <div class="panel-body">
                            <select name="group_list_select" id="group_list_select" class="form-control"
                                    size="<?php echo $groupSelectSize ?>" tabindex="1">
                            </select>
                        </div>
                        <div class="panel-footer">
                            <div class="panel-button text-right">
                                <button class="btn btn-danger" type="submit">
                                    <?php echo $PMF_LANG['ad_gen_delete'] ?>
                                </button>
                             </div>
                        </div>
                    </div>
                </form>
            </div>

            <div class="col-lg-4" id="groupMemberships">
                <form id="group_membership" name="group_membership" method="post"
                    action="?action=group&amp;group_action=update_members">
                <input id="update_member_group_id" type="hidden" name="group_id" value="0">
                <div class="panel panel-default">
                    <div class="panel-heading">
                        <?php echo $PMF_LANG['ad_group_membership'] ?>
                    </div>
                    <div class="panel-body">
                        <div class="form-group">
                            <div class="text-right">
                                <span class="select_all">
                                    <a class="btn btn-primary btn-sm"
                                        href="javascript:selectSelectAll('group_user_list')">
                                        <i aria-hidden="true" class="fa fa-toggle-on"></i>
                                    </a>
                                </span>
                                <span class="unselect_all">
                                    <a class="btn btn-primary btn-sm"
                                        href="javascript:selectUnselectAll('group_user_list')">
                                        <i aria-hidden="true" class="fa fa-toggle-off"></i>
                                    </a>
                                </span>
                            </div>
                        </div>

                        <div class="form-group">
                            <select id="group_user_list" class="form-control" size="<?php echo $memberSelectSize ?>"
                                multiple>
                                <option value="0">...user list...</option>
                            </select>
                        </div>

                        <div class="form-group">
                            <div class="text-center">
                                <input class="btn btn-success pmf-add-member" type="button"
                                    value="<?php echo $PMF_LANG['ad_group_addMember'] ?>">
                                <input class="btn btn-danger pmf-remove-member" type="button"
                                    value="<?php echo $PMF_LANG['ad_group_removeMember'] ?>">
                            </div>
                        </div>

                        <div class="form-group">
                            <?php echo $PMF_LANG['ad_group_members'] ?>
                            <div class="pull-right">
                                <span class="select_all">
                                    <a class="btn btn-primary btn-sm"
                                        href="javascript:selectSelectAll('group_member_list')">
                                        <i aria-hidden="true" class="fa fa-toggle-on"></i>
                                    </a>
                                </span>
                                <span class="unselect_all">
                                    <a class="btn btn-primary btn-sm"
                                        href="javascript:selectUnselectAll('group_member_list')">
                                        <i aria-hidden="true" class="fa fa-toggle-off"></i>
                                    </a>
                                </span>
                            </div>
                        </div>

                        <div class="form-group">
                            <select id="group_member_list" name="group_members[]" class="form-control" multiple
                                    size="<?php echo $memberSelectSize ?>">
                                <option value="0">...member list...</option>
                            </select>
                        </div>
                    </div>
                    <div class="panel-footer">
                        <div class="panel-button text-right">
                            <button class="btn btn-primary" onclick="javascript:selectSelectAll('group_member_list')" type="submit">
                                <?php echo $PMF_LANG['ad_gen_save'] ?>
                            </button>
                         </div>
                    </div>
                </div>
                </form>
            </div>

            <div class="col-lg-4" id="groupDetails">
                <div id="group_data" class="panel panel-default">
                    <div class="panel-heading">
                        <?php echo $PMF_LANG['ad_group_details'] ?>
                    </div>
                    <form action="?action=group&group_action=update_data" method="post" class="form-horizontal">
                    <input id="update_group_id" type="hidden" name="group_id" value="0">
                        <div class="panel-body">
                            <div class="form-group">
                                <label class="col-lg-3 control-label" for="update_group_name">
                                    <?php echo $PMF_LANG['ad_group_name'] ?>
                                </label>
                                <div class="col-lg-9">
                                    <input id="update_group_name" type="text" name="name" class="form-control"
                                           tabindex="1" value="<?php echo(isset($group_name) ? $group_name : '') ?>">
                                </div>
                            </div>
                            <div class="form-group">
                                <label class="col-lg-3 control-label" for="update_group_description">
                                    <?php echo $PMF_LANG['ad_group_description'] ?>
                                </label>
                                <div class="col-lg-9">
                                    <textarea id="update_group_description" name="description" class="form-control"
                                              rows="<?php echo $descriptionRows ?>"
                                              tabindex="2"><?php
                                        echo(isset($group_description) ? $group_description : '') ?></textarea>
                                </div>
                            </div>
                            <div class="form-group">
                                <div class="col-lg-offset-3 col-lg-9">
                                    <div class="checkbox">
                                        <label>
                                            <input id="update_group_auto_join" type="checkbox" name="auto_join" value="1"
                                                       tabindex="3"<?php
                                                echo((isset($group_auto_join) && $group_auto_join) ? ' checked' : '') ?>>
                                            <?php echo $PMF_LANG['ad_group_autoJoin'] ?>
                                        </label>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="panel-footer">
                            <div class="panel-button text-right">
                                <button class="btn btn-primary" type="submit">
                                    <?php echo $PMF_LANG['ad_gen_save'] ?>
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
                <div id="groupRights" class="panel panel-default">
                    <form id="rightsForm" action="?action=group&amp;group_action=update_rights" method="post">
                        <input id="rights_group_id" type="hidden" name="group_id" value="0">
                    <div class="panel-heading" id="user_rights_legend">
                        <i aria-hidden="true" class="fa fa-lock"></i> <?php echo $PMF_LANG['ad_user_rights'] ?>
                        <span class="pull-right">
                            <a class="btn btn-default btn-sm" href="#" id="checkAll">
                                <?php echo $PMF_LANG['ad_user_checkall'] ?>
                                /
                                <?php echo $PMF_LANG['ad_user_uncheckall'] ?>
                            </a>
                        </span>
                    </div>

                    <div class="panel-body">
                        <div class="row">
                            <?php foreach ($user->perm->getAllRightsData() as $right): ?>
                            <div class="col-xs-6 form-group pmf-user-permissions">
                                <div class="checkbox">
                                    <label class="checkbox-inline">
                                        <input id="group_right_<?php echo $right['right_id'] ?>" type="checkbox"
                                               name="group_rights[]" value="<?php echo $right['right_id'] ?>"
                                               class="permission">
                                        <?php
                                        if (isset($PMF_LANG['rightsLanguage'][$right['name']])) {
                                            echo $PMF_LANG['rightsLanguage'][$right['name']];
                                        } else {
                                            echo $right['description'];
                                        }
                                        ?>
                                    </label>
                                </div>
                            </div>
                        <?php endforeach; ?>
                        </div>
                    </div>
                    <div class="panel-footer">
                        <div class="panel-button text-right">
                            <button class="btn btn-primary" type="submit">
                                <?php echo $PMF_LANG['ad_gen_save'] ?>
                            </button>
                        </div>
                    </div>
                </div>
                </form>
            </div>
        </div>
    </div>
<?php

}
