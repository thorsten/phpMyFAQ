<?php
/**
 * Displays the group management frontend.
 *
 *
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

use phpMyFAQ\Filter;
use phpMyFAQ\User;
use phpMyFAQ\User\CurrentUser;

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
$groupActionList = [
    'update_members',
    'update_rights',
    'update_data',
    'delete_confirm',
    'delete',
    'addsave',
    'add',
    'list'
];

// what shall we do?
// actions defined by url: group_action=
$groupAction = Filter::filterInput(INPUT_GET, 'group_action', FILTER_SANITIZE_STRING, $defaultGroupAction);

// actions defined by submit button
if (isset($_POST['group_action_deleteConfirm'])) {
    $groupAction = 'delete_confirm';
}
if (isset($_POST['cancel'])) {
    $groupAction = $defaultGroupAction;
}

if (!in_array($groupAction, $groupActionList)){
    // @Todo: implement Error message
}

// update group members
if ($groupAction == 'update_members' && $user->perm->checkRight($user->getUserId(), 'editgroup')) {
    $message = '';
    $groupAction = $defaultGroupAction;
    $groupId = Filter::filterInput(INPUT_POST, 'group_id', FILTER_VALIDATE_INT, 0);
    $groupMembers = isset($_POST['group_members']) ? $_POST['group_members'] : [];

    if ($groupId == 0) {
        $message .= sprintf('<p class="alert alert-danger">%s</p>', $PMF_LANG['ad_user_error_noId']);
    } else {
        $user = new User($faqConfig);
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
    $groupId = Filter::filterInput(INPUT_POST, 'group_id', FILTER_VALIDATE_INT, 0);
    if ($groupId == 0) {
        $message .= sprintf('<p class="alert alert-danger">%s</p>', $PMF_LANG['ad_user_error_noId']);
    } else {
        $user = new User($faqConfig);
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
    $groupId = Filter::filterInput(INPUT_POST, 'group_id', FILTER_VALIDATE_INT, 0);
    if ($groupId == 0) {
        $message .= sprintf('<p class="alert alert-danger">%s</p>', $PMF_LANG['ad_user_error_noId']);
    } else {
        $groupData = [];
        $dataFields = array('name', 'description', 'auto_join');
        foreach ($dataFields as $field) {
            $groupData[$field] = Filter::filterInput(INPUT_POST, $field, FILTER_SANITIZE_STRING, '');
        }
        $user = new User($faqConfig);
        $perm = $user->perm;
        if (!$perm->changeGroup($groupId, $groupData)) {
            $message .= sprintf(
            '<p class="alert alert-danger">%s<br>%s</p>',
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
    $user = new CurrentUser($faqConfig);
    $perm = $user->perm;
    $groupId = Filter::filterInput(INPUT_POST, 'group_list_select', FILTER_VALIDATE_INT, 0);
    if ($groupId <= 0) {
        $message    .= sprintf('<p class="alert alert-danger">%s</p>', $PMF_LANG['ad_user_error_noId']);
        $groupAction = $defaultGroupAction;
    } else {
        $groupData = $perm->getGroupData($groupId);
        ?>
        <header class="row">
            <div class="col-lg-12">
                <h2 class="page-header">
                    <i aria-hidden="true" class="fas fa-users fa-fw"></i>
                    <?= $PMF_LANG['ad_group_deleteGroup'] ?> "<?= $groupData['name'] ?>"
                </h2>
            </div>
        </header>

        <div class="row">
            <div class="col-lg-12">
                <p><?= $PMF_LANG['ad_group_deleteQuestion'] ?></p>
                <form action ="?action=group&amp;group_action=delete" method="post">
                    <input type="hidden" name="group_id" value="<?= $groupId ?>">
                    <input type="hidden" name="csrf" value="<?= $user->getCsrfTokenFromSession()?>">
                    <p>
                        <button class="btn btn-inverse" type="submit" name="cancel">
                            <?= $PMF_LANG['ad_gen_cancel'] ?>
                        </button>
                        <button class="btn btn-primary" type="submit">
                            <?= $PMF_LANG['ad_gen_save'] ?>
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
    $user = new User($faqConfig);
    $groupId = Filter::filterInput(INPUT_POST, 'group_id', FILTER_VALIDATE_INT, 0);
    $csrfOkay = true;
    $csrfToken = Filter::filterInput(INPUT_POST, 'csrf', FILTER_SANITIZE_STRING);
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
    $user = new User($faqConfig);
    $message = '';
    $messages = [];
    $groupName = Filter::filterInput(INPUT_POST, 'group_name', FILTER_SANITIZE_STRING, '');
    $groupDescription = Filter::filterInput(INPUT_POST, 'group_description', FILTER_SANITIZE_STRING, '');
    $groupAutoJoin = Filter::filterInput(INPUT_POST, 'group_auto_join', FILTER_SANITIZE_STRING, '');
    $csrfOkay = true;
    $csrfToken = Filter::filterInput(INPUT_POST, 'csrf', FILTER_SANITIZE_STRING);

    if (!isset($_SESSION['phpmyfaq_csrf_token']) || $_SESSION['phpmyfaq_csrf_token'] !== $csrfToken) {
        $csrfOkay = false;
    }
    // check group name
    if ($groupName == '') {
        $messages[] = $PMF_LANG['ad_group_error_noName'];
    }
    // ok, let's go
    if (count($messages) == 0 && $csrfOkay) {
        // create group
        $groupData = array(
            'name' => $groupName,
            'description' => $groupDescription,
            'auto_join' => $groupAutoJoin,
        );

        if ($user->perm->addGroup($groupData) <= 0) {
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
            $message .= $err.'<br>';
        }
        $message .= '</p>';
    }
}

if (!isset($message)) {
    $message = '';
}

// show new group form
if ($groupAction == 'add' && $user->perm->checkRight($user->getUserId(), 'addgroup')) {
    $user = new CurrentUser($faqConfig);
    ?>

        <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
          <h1 class="h2">
            <i aria-hidden="true" class="fas fa-users"></i>
              <?= $PMF_LANG['ad_group_add'] ?>
          </h1>
        </div>

        <div class="row">
            <div class="col-lg-12">
                <div id="user_message"><?= $message ?></div>
                <form  name="group_create" action="?action=group&amp;group_action=addsave" method="post">
                    <input type="hidden" name="csrf" value="<?= $user->getCsrfTokenFromSession() ?>">

                    <div class="form-group row">
                        <label class="col-lg-2 form-control-label" for="group_name"><?= $PMF_LANG['ad_group_name'] ?></label>
                        <div class="col-lg-3">
                            <input type="text" name="group_name" id="group_name" autofocus class="form-control"
                                   value="<?=(isset($groupName) ? $groupName : '') ?>" tabindex="1">
                        </div>
                    </div>

                    <div class="form-group row">
                        <label class="col-lg-2 form-control-label" for="group_description"><?= $PMF_LANG['ad_group_description'] ?></label>
                        <div class="col-lg-3">
                            <textarea name="group_description" id="group_description" cols="<?= $descriptionCols ?>"
                                      rows="<?= $descriptionRows ?>" tabindex="2"  class="form-control"
                                ><?=(isset($groupDescription) ? $groupDescription : '') ?></textarea>
                        </div>
                    </div>

                    <div class="form-group row">
                        <label class="col-lg-2 form-control-label" for="group_auto_join"><?= $PMF_LANG['ad_group_autoJoin'] ?></label>
                        <div class="col-lg-3">
                            <div class="checkbox">
                                <label>
                                    <input type="checkbox" name="group_auto_join" id="group_auto_join" value="1" tabindex="3"
                                    <?=((isset($groupAutoJoin) && $groupAutoJoin) ? ' checked="checked"' : '') ?>>
                                </label>
                            </div>
                        </div>
                    </div>

                    <div class="form-group row">
                        <div class="col-lg-offset-2 col-lg-3">
                            <button class="btn btn-primary" type="submit">
                                <?= $PMF_LANG['ad_gen_save'] ?>
                            </button>
                            <button class="btn btn-info" type="reset" name="cancel">
                                <?= $PMF_LANG['ad_gen_cancel'] ?>
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
        <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
          <h1 class="h2">
            <i aria-hidden="true" class="fas fa-users"></i>
              <?= $PMF_LANG['ad_menu_group_administration'] ?>
          </h1>
          <div class="btn-toolbar mb-2 mb-md-0">
            <div class="btn-group mr-2">
              <a class="btn btn-sm btn-outline-success" href="?action=group&amp;group_action=add">
                  <?= $PMF_LANG['ad_group_add_link'] ?>
              </a>
            </div>
          </div>
        </div>

        <script src="assets/js/user.js"></script>
        <script src="assets/js/groups.js"></script>

  <div id="user_message"><?= $message ?></div>

  <div class="row">

    <div class="col-lg-4" id="group_list">
      <div class="card">
        <form id="group_select" name="group_select" action="?action=group&amp;group_action=delete_confirm"
              method="post">
          <div class="card-header">
              <?= $PMF_LANG['ad_groups'] ?>
          </div>
          <div class="card-body">
            <select name="group_list_select" id="group_list_select" class="form-control"
                    size="<?= $groupSelectSize ?>" tabindex="1">
            </select>
          </div>
          <div class="card-footer">
            <div class="card-button text-right">
              <button class="btn btn-danger" type="submit">
                  <?= $PMF_LANG['ad_gen_delete'] ?>
              </button>
            </div>
          </div>
        </form>
      </div>

      <div id="group_data" class="card">
        <div class="card-header">
            <?= $PMF_LANG['ad_group_details'] ?>
        </div>
        <form action="?action=group&group_action=update_data" method="post">
          <input id="update_group_id" type="hidden" name="group_id" value="0">
          <div class="card-body">
            <div class="form-group row">
              <label class="col-lg-3 form-control-label" for="update_group_name">
                  <?= $PMF_LANG['ad_group_name'] ?>
              </label>
              <div class="col-lg-9">
                <input id="update_group_name" type="text" name="name" class="form-control"
                       tabindex="1" value="<?= (isset($groupName) ? $groupName : '') ?>">
              </div>
            </div>
            <div class="form-group row">
              <label class="col-lg-3 form-control-label" for="update_group_description">
                  <?= $PMF_LANG['ad_group_description'] ?>
              </label>
              <div class="col-lg-9">
                                    <textarea id="update_group_description" name="description" class="form-control"
                                              rows="<?= $descriptionRows ?>"
                                              tabindex="2"><?php
                                        echo(isset($groupDescription) ? $groupDescription : '') ?></textarea>
              </div>
            </div>
            <div class="form-group row">
              <div class="col-lg-offset-3 col-lg-9">
                <div class="checkbox">
                  <label>
                    <input id="update_group_auto_join" type="checkbox" name="auto_join" value="1"
                           tabindex="3"<?php
                    echo((isset($groupAutoJoin) && $groupAutoJoin) ? ' checked' : '') ?>>
                      <?= $PMF_LANG['ad_group_autoJoin'] ?>
                  </label>
                </div>
              </div>
            </div>
          </div>
          <div class="card-footer">
            <div class="card-button text-right">
              <button class="btn btn-primary" type="submit">
                  <?= $PMF_LANG['ad_gen_save'] ?>
              </button>
            </div>
          </div>
        </form>
      </div>
    </div>

    <div class="col-lg-4" id="groupMemberships">
      <form id="group_membership" name="group_membership" method="post"
            action="?action=group&amp;group_action=update_members">
        <input id="update_member_group_id" type="hidden" name="group_id" value="0">
        <div class="card">
          <div class="card-header">
              <?= $PMF_LANG['ad_group_membership'] ?>
          </div>
          <div class="card-body">
            <div class="form-group row">
              <div class="text-right">
                                <span class="select_all">
                                    <a class="btn btn-primary btn-sm"
                                       href="javascript:selectSelectAll('group_user_list')">
                                        <i aria-hidden="true" class="fas fa-user-plus"></i>
                                    </a>
                                </span>
                <span class="unselect_all">
                                    <a class="btn btn-primary btn-sm"
                                       href="javascript:selectUnselectAll('group_user_list')">
                                        <i aria-hidden="true" class="fas fa-user-minus"></i>
                                    </a>
                                </span>
              </div>
            </div>

            <div class="form-group row">
              <select id="group_user_list" class="form-control" size="<?= $memberSelectSize ?>"
                      multiple>
                <option value="0">...user list...</option>
              </select>
            </div>

            <div class="form-group row">
              <div class="text-center">
                <input class="btn btn-success pmf-add-member" type="button"
                       value="<?= $PMF_LANG['ad_group_addMember'] ?>">
                <input class="btn btn-danger pmf-remove-member" type="button"
                       value="<?= $PMF_LANG['ad_group_removeMember'] ?>">
              </div>
            </div>
        </div>

        <ul class="list-group list-group-flush">
            <li class="list-group-item bg-light"><?= $PMF_LANG['ad_group_members']; ?></li>
        </ul>

        <div class="card-body">
            <div class="form-group row">
              <div class="float-right">
                <span class="select_all">
                    <a class="btn btn-primary btn-sm"
                       href="javascript:selectSelectAll('group_member_list')">
                        <i aria-hidden="true" class="fas fa-user-plus"></i>
                    </a>
                </span>
                <span class="unselect_all">
                  <a class="btn btn-primary btn-sm"
                     href="javascript:selectUnselectAll('group_member_list')">
                      <i aria-hidden="true" class="fas fa-user-minus"></i>
                  </a>
                </span>
              </div>
            </div>

            <div class="form-group row">
              <select id="group_member_list" name="group_members[]" class="form-control" multiple
                      size="<?= $memberSelectSize ?>">
                <option value="0">...member list...</option>
              </select>
            </div>
          </div>
          <div class="card-footer">
            <div class="card-button text-right">
              <button class="btn btn-primary" onclick="javascript:selectSelectAll('group_member_list')" type="submit">
                  <?= $PMF_LANG['ad_gen_save'] ?>
              </button>
            </div>
          </div>
        </div>
      </form>
    </div>

    <div class="col-lg-4" id="groupDetails">

      <div id="groupRights" class="card">
        <form id="rightsForm" action="?action=group&amp;group_action=update_rights" method="post">
          <input id="rights_group_id" type="hidden" name="group_id" value="0">
          <div class="card-header" id="user_rights_legend">
            <i aria-hidden="true" class="fas fa-lock"></i> <?= $PMF_LANG['ad_group_rights'] ?>
            <span class="float-right">
              <a class="btn btn-secondary btn-sm" href="#" id="checkAll">
                <?= $PMF_LANG['ad_user_checkall'] ?> / <?= $PMF_LANG['ad_user_uncheckall'] ?>
              </a>
            </span>
          </div>

          <div class="card-body">
            <?php foreach ($user->perm->getAllRightsData() as $right): ?>
              <div class="form-check">
                <input id="group_right_<?= $right['right_id'] ?>" type="checkbox"
                       name="group_rights[]" value="<?= $right['right_id'] ?>"
                       class="form-check-input permission">
                <label class="form-check-label">
                    <?php
                    if (isset($PMF_LANG['rightsLanguage'][$right['name']])) {
                        echo $PMF_LANG['rightsLanguage'][$right['name']];
                    } else {
                        echo $right['description'];
                    }
                    ?>
                </label>
              </div>
            <?php endforeach; ?>
          </div>
          <div class="card-footer">
            <div class="card-button text-right">
              <button class="btn btn-primary" type="submit">
                  <?= $PMF_LANG['ad_gen_save'] ?>
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
