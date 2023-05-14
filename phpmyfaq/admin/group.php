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
 * @copyright 2005-2023 phpMyFAQ Team
 * @license   https://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2005-12-15
 */

use phpMyFAQ\Component\Alert;
use phpMyFAQ\Filter;
use phpMyFAQ\Session\Token;
use phpMyFAQ\Strings;
use phpMyFAQ\Translation;
use phpMyFAQ\User;
use phpMyFAQ\User\CurrentUser;

if (!defined('IS_VALID_PHPMYFAQ')) {
    http_response_code(400);
    exit();
}

if (
    !$user->perm->hasPermission($user->getUserId(), 'editgroup') &&
    !$user->perm->hasPermission($user->getUserId(), 'delgroup') &&
    !$user->perm->hasPermission($user->getUserId(), 'addgroup')
) {
    exit();
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
if ($groupAction == 'update_members' && $user->perm->hasPermission($user->getUserId(), 'editgroup')) {
    $message = '';
    $groupAction = $defaultGroupAction;
    $groupId = Filter::filterInput(INPUT_POST, 'group_id', FILTER_VALIDATE_INT, 0);
    $groupMembers = $_POST['group_members'] ?? [];

    if ($groupId == 0) {
        $message .= Alert::danger('ad_user_error_noId');
    } else {
        $user = new User($faqConfig);
        $perm = $user->perm;
        if (!$perm->removeAllUsersFromGroup($groupId)) {
            $message .= Alert::danger('ad_msg_mysqlerr');
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
if ($groupAction == 'update_rights' && $user->perm->hasPermission($user->getUserId(), 'editgroup')) {
    $message = '';
    $groupAction = $defaultGroupAction;
    $groupId = Filter::filterInput(INPUT_POST, 'group_id', FILTER_VALIDATE_INT, 0);
    if ($groupId == 0) {
        $message .= Alert::danger('ad_user_error_noId');
    } else {
        $user = new User($faqConfig);
        $perm = $user->perm;
        $groupRights = $_POST['group_rights'] ?? [];
        if (!$perm->refuseAllGroupRights($groupId)) {
            $message .= Alert::danger('ad_msg_mysqlerr');
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
if ($groupAction == 'update_data' && $user->perm->hasPermission($user->getUserId(), 'editgroup')) {
    $message = '';
    $groupAction = $defaultGroupAction;
    $groupId = Filter::filterInput(INPUT_POST, 'group_id', FILTER_VALIDATE_INT, 0);
    if ($groupId == 0) {
        $message .= Alert::danger('ad_user_error_noId');
    } else {
        $groupData = [];
        $dataFields = ['name', 'description', 'auto_join'];
        foreach ($dataFields as $field) {
            $groupData[$field] = Filter::filterInput(INPUT_POST, $field, FILTER_SANITIZE_SPECIAL_CHARS, '');
        }
        $user = new User($faqConfig);
        $perm = $user->perm;
        if (!$perm->changeGroup($groupId, $groupData)) {
            $message .= Alert::danger('ad_msg_mysqlerr', $faqConfig->getDb()->error());
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
if ($groupAction == 'delete_confirm' && $user->perm->hasPermission($user->getUserId(), 'delgroup')) {
    $message = '';
    $user = new CurrentUser($faqConfig);
    $perm = $user->perm;
    $groupId = Filter::filterInput(INPUT_POST, 'group_list_select', FILTER_VALIDATE_INT, 0);
    if ($groupId <= 0) {
        $message .= Alert::danger('ad_user_error_noId');
        $groupAction = $defaultGroupAction;
    } else {
        $groupData = $perm->getGroupData($groupId);
        ?>
      <header class="row">
        <div class="col-lg-12">
          <h2 class="page-header">
            <i aria-hidden="true" class="fa fa-users fa-fw"></i>
              <?= Translation::get('ad_group_deleteGroup') ?> "<?= Strings::htmlentities($groupData['name']) ?>"
          </h2>
        </div>
      </header>

      <div class="row">
        <div class="col-lg-12">
          <p><?= Translation::get('ad_group_deleteQuestion') ?></p>
          <form action="?action=group&amp;group_action=delete" method="post">
            <input type="hidden" name="group_id" value="<?= $groupId ?>">
            <?= Token::getInstance()->getTokenInput('delete-group') ?>
            <p>
              <button class="btn btn-inverse" type="submit" name="cancel">
                  <?= Translation::get('ad_gen_cancel') ?>
              </button>
              <button class="btn btn-primary" type="submit">
                  <?= Translation::get('ad_gen_save') ?>
              </button>
            </p>
          </form>
        </div>
      </div>
        <?php
    }
}

if ($groupAction == 'delete' && $user->perm->hasPermission($user->getUserId(), 'delgroup')) {
    $message = '';
    $user = new User($faqConfig);
    $groupId = Filter::filterInput(INPUT_POST, 'group_id', FILTER_VALIDATE_INT, 0);
    $csrfOkay = true;
    $csrfToken = Filter::filterInput(INPUT_POST, 'csrf', FILTER_SANITIZE_SPECIAL_CHARS);
    if (!Token::getInstance()->verifyToken('delete-group', $csrfToken)) {
        $csrfOkay = false;
    }
    $groupAction = $defaultGroupAction;
    if ($groupId <= 0) {
        $message .= Alert::danger('ad_user_error_noId');
    } else {
        if (!$user->perm->deleteGroup($groupId) && !$csrfOkay) {
            $message .= Alert::danger('ad_group_error_delete');
        } else {
            $message .= Alert::success('ad_group_deleted');
        }
        $userError = $user->error();
        if ($userError != '') {
            $message .= sprintf('<p class="alert alert-danger">%s</p>', $userError);
        }
    }
}

if ($groupAction == 'addsave' && $user->perm->hasPermission($user->getUserId(), 'addgroup')) {
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
        $message = Alert::success('ad_group_suc');
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
if ($groupAction == 'add' && $user->perm->hasPermission($user->getUserId(), 'addgroup')) {
    $user = new CurrentUser($faqConfig);
    ?>

  <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2">
      <i aria-hidden="true" class="fa fa-users"></i>
        <?= Translation::get('ad_group_add') ?>
    </h1>
  </div>

  <div class="row">
    <div class="col-lg-12">
      <div id="user_message"><?= $message ?></div>
      <form name="group_create" action="?action=group&amp;group_action=addsave" method="post">
        <?= Token::getInstance()->getTokenInput('add-group') ?>

        <div class="row mb-2">
          <label class="col-lg-3 col-form-label" for="group_name">
              <?= Translation::get('ad_group_name') ?>
          </label>
          <div class="col-lg-3">
            <input type="text" name="group_name" id="group_name" autofocus class="form-control"
                   value="<?= ($groupName ?? '') ?>" tabindex="1">
          </div>
        </div>

        <div class="row mb-2">
          <label class="col-lg-3 col-form-label" for="group_description">
              <?= Translation::get('ad_group_description') ?>
          </label>
          <div class="col-lg-3">
            <textarea name="group_description" id="group_description" cols="15" rows="3" tabindex="2"
                      class="form-control"
            ><?= ($groupDescription ?? '') ?></textarea>
          </div>
        </div>

        <div class="row mb-2">
          <label class="col-lg-3 col-form-label" for="group_auto_join">
              <?= Translation::get('ad_group_autoJoin') ?>
          </label>
          <div class="col-lg-3">
            <div class="form-check">
              <label>
                <input type="checkbox" name="group_auto_join" id="group_auto_join" value="1" tabindex="3"
                       <?= ((isset($groupAutoJoin) && $groupAutoJoin) ? ' checked' : '') ?>>
              </label>
            </div>
          </div>
        </div>

        <div class="row">
          <div class="offset-lg-2 col-lg-3">
            <button class="btn btn-info" type="reset" name="cancel">
                <?= Translation::get('ad_gen_cancel') ?>
            </button>
            <button class="btn btn-primary" type="submit">
                <?= Translation::get('ad_gen_save') ?>
            </button>
          </div>
        </div>
      </form>
    </div>
  </div>
    <?php
}

// show list of users
if ('list' === $groupAction) {
    ?>
  <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2">
      <i aria-hidden="true" class="fa fa-users"></i>
        <?= Translation::get('ad_menu_group_administration') ?>
    </h1>
    <div class="btn-toolbar mb-2 mb-md-0">
      <div class="btn-group mr-2">
        <a class="btn btn-sm btn-success" href="?action=group&amp;group_action=add">
          <?= Translation::get('ad_group_add_link') ?>
        </a>
      </div>
    </div>
  </div>

  <div id="user_message"><?= $message ?></div>

  <div class="row">

    <div class="col-lg-4" id="group_list">
      <div class="card mb-4">
        <form id="group_select" name="group_select" action="?action=group&amp;group_action=delete_confirm"
              method="post">
          <h5 class="card-header py-3">
            <i aria-hidden="true" class="fa fa-users"></i> <?= Translation::get('ad_groups') ?>
          </h5>
          <div class="card-body">
            <select name="group_list_select" id="group_list_select" class="form-select"
                    size="10" tabindex="1">
            </select>
          </div>
          <div class="card-footer">
            <div class="card-button text-end">
              <button class="btn btn-danger" type="submit">
                  <?= Translation::get('ad_gen_delete') ?>
              </button>
            </div>
          </div>
        </form>
      </div>

      <div id="group_data" class="card mb-4">
        <h5 class="card-header py-3">
          <i class="fa fa-info-circle" aria-hidden="true"></i> <?= Translation::get('ad_group_details') ?>
        </h5>
        <form action="?action=group&group_action=update_data" method="post">
          <input id="update_group_id" type="hidden" name="group_id" value="0">
          <div class="card-body">
            <div class="row mb-2">
              <label class="col-4 col-form-label" for="update_group_name">
                  <?= Translation::get('ad_group_name') ?>
              </label>
              <div class="col-8">
                <input id="update_group_name" type="text" name="name" class="form-control"
                       tabindex="1" value="<?= ($groupName ?? '') ?>">
              </div>
            </div>
            <div class="row mb-2">
              <label class="col-4 col-form-label" for="update_group_description">
                  <?= Translation::get('ad_group_description') ?>
              </label>
              <div class="col-8">
                <textarea id="update_group_description" name="description" class="form-control" rows="3" tabindex="2"
                ><?= $groupDescription ?? '' ?></textarea>
              </div>
            </div>
            <div class="row mb-2">
              <div class="offset-4">
                <div class="form-check">
                    <input id="update_group_auto_join" type="checkbox" name="auto_join" value="1"
                           class="form-check-input" tabindex="3"<?php
                            echo((isset($groupAutoJoin) && $groupAutoJoin) ? ' checked' : '') ?>>
                    <label class="form-check-label" for="update_group_auto_join">
                        <?= Translation::get('ad_group_autoJoin') ?>
                    </label>
                </div>
              </div>
            </div>
          </div>
          <div class="card-footer">
            <div class="card-button text-end">
              <button class="btn btn-primary" type="submit">
                  <?= Translation::get('ad_gen_save') ?>
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
        <div class="card mb-4">
          <h5 class="card-header py-3">
            <i aria-hidden="true" class="fa fa-user-circle"></i> <?= Translation::get('ad_group_membership') ?>
          </h5>
          <div class="card-body">
            <div class="row">
              <div class="text-center">
                <span class="select_all">
                  <button type="button" class="btn btn-primary btn-sm" id="select_all_group_user_list">
                      <i aria-hidden="true" class="fa fa-user-plus"></i>
                  </button>
                </span>
                <span class="unselect_all">
                  <button type="button" class="btn btn-primary btn-sm" id="unselect_all_group_user_list">
                      <i aria-hidden="true" class="fa fa-user-times"></i>
                  </button>
                </span>
              </div>
            </div>

            <div class="row">
              <select id="group_user_list" class="form-control" size="7"
                      multiple>
                <option value="0">...user list...</option>
              </select>
            </div>

            <div class="row">
              <div class="text-center">
                <input class="btn btn-success pmf-add-member" type="button"
                       value="<?= Translation::get('ad_group_addMember') ?>">
                <input class="btn btn-danger pmf-remove-member" type="button"
                       value="<?= Translation::get('ad_group_removeMember') ?>">
              </div>
            </div>
          </div>

          <ul class="list-group list-group-flush">
            <li class="list-group-item">
              <i aria-hidden="true" class="fa fa-user-circle"></i> <?= Translation::get('ad_group_members'); ?></li>
          </ul>

          <div class="card-body">
            <div class="row">
              <div class="text-center">
                <span class="select_all">
                    <button type="button" class="btn btn-primary btn-sm" id="select_all_members">
                        <i aria-hidden="true" class="fa fa-user-plus"></i>
                    </button>
                </span>
                <span class="unselect_all">
                  <button type="button" class="btn btn-primary btn-sm" id="unselect_all_members">
                      <i aria-hidden="true" class="fa fa-user-times"></i>
                  </button>
                </span>
              </div>
            </div>

            <div class="row">
              <select id="group_member_list" name="group_members[]" class="form-control" multiple size="7">
                <option value="0">...member list...</option>
              </select>
            </div>
          </div>
          <div class="card-footer">
            <div class="card-button text-end">
              <button class="btn btn-primary" onclick="javascript:selectSelectAll('group_member_list')" type="submit">
                  <?= Translation::get('ad_gen_save') ?>
              </button>
            </div>
          </div>
        </div>
      </form>
    </div>

    <div class="col-lg-4" id="groupDetails">

      <div id="groupRights" class="card mb-4">
        <form id="rightsForm" action="?action=group&amp;group_action=update_rights" method="post">
          <input id="rights_group_id" type="hidden" name="group_id" value="0">
          <h5 class="card-header py-3" id="user_rights_legend">
            <i aria-hidden="true" class="fa fa-lock"></i> <?= Translation::get('ad_group_rights') ?>

          </h5>

          <div class="card-body">
              <div class="text-center mb-3">
              <a class="btn btn-primary btn-sm" href="#" id="checkAll">
                <?= Translation::get('ad_user_checkall') ?> / <?= Translation::get('ad_user_uncheckall') ?>
              </a>
            </div>
              <?php foreach ($user->perm->getAllRightsData() as $right) : ?>
                <div class="form-check">
                  <input id="group_right_<?= $right['right_id'] ?>" type="checkbox"
                         name="group_rights[]" value="<?= $right['right_id'] ?>"
                         class="form-check-input permission">
                  <label class="form-check-label" for="group_right_<?= $right['right_id'] ?>">
                      <?php
                      try {
                          echo Translation::get('rightsLanguage::' . $right['name']);
                      } catch (ErrorException) {
                          echo $right['description'];
                      }
                      ?>
                  </label>
                </div>
              <?php endforeach; ?>
          </div>
          <div class="card-footer">
            <div class="card-button text-end">
              <button class="btn btn-primary" type="submit">
                  <?= Translation::get('ad_gen_save') ?>
              </button>
            </div>
          </div>
      </div>
      </form>
    </div>
  </div>
    <?php
}
