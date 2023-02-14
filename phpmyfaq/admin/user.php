<?php

/**
 * Displays the user management frontend.
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at http://mozilla.org/MPL/2.0/.
 *
 * @package phpMyFAQ
 * @author Lars Tiedemann <php@larstiedemann.de>
 * @author Uwe Pries <uwe.pries@digartis.de>
 * @author Sarah Hermann <sayh@gmx.de>
 * @author Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2005-2022 phpMyFAQ Team
 * @license http://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link https://www.phpmyfaq.de
 * @since 2005-12-15
 */

use phpMyFAQ\Category;
use phpMyFAQ\Filter;
use phpMyFAQ\Pagination;
use phpMyFAQ\Permission;
use phpMyFAQ\Strings;
use phpMyFAQ\User;
use phpMyFAQ\User\CurrentUser;

if (!defined('IS_VALID_PHPMYFAQ')) {
    http_response_code(400);
    exit();
}

if (
    $user->perm->hasPermission($user->getUserId(), 'edit_user') || $user->perm->hasPermission(
        $user->getUserId(),
        'delete_user'
    ) || $user->perm->hasPermission($user->getUserId(), 'add_user')
) {

    $userId = Filter::filterInput(INPUT_GET, 'user_id', FILTER_VALIDATE_INT);
    ?>
  <script src="assets/js/user.js"></script>
    <?php

    // set some parameters
    $selectSize = 10;
    $defaultUserAction = 'list';
    $defaultUserStatus = 'active';
    $userActionList = [
        'update_rights',
        'update_data',
        'delete_confirm',
        'delete',
        'addsave',
        'list',
        'listallusers'
    ];

    // what shall we do?
    // actions defined by url: user_action=
    $userAction = Filter::filterInput(INPUT_GET, 'user_action', FILTER_UNSAFE_RAW, $defaultUserAction);
    $currentUser = new CurrentUser($faqConfig);

    // actions defined by submit button
    if (isset($_POST['user_action_deleteConfirm'])) {
        $userAction = 'delete_confirm';
    }
    if (isset($_POST['cancel'])) {
        $userAction = $defaultUserAction;
    }

    // update user rights
    if ($userAction == 'update_rights' && $user->perm->hasPermission($user->getUserId(), 'edit_user')) {
        $message = '';
        $userAction = $defaultUserAction;
        $userId = Filter::filterInput(INPUT_POST, 'user_id', FILTER_VALIDATE_INT, 0);
        $csrfOkay = true;
        $csrfToken = Filter::filterInput(INPUT_POST, 'csrf', FILTER_UNSAFE_RAW);
        if (!isset($_SESSION['phpmyfaq_csrf_token']) || $_SESSION['phpmyfaq_csrf_token'] !== $csrfToken) {
            $csrfOkay = false;
        }
        if (0 === (int)$userId || !$csrfOkay) {
            $message .= sprintf('<p class="alert alert-danger">%s</p>', $PMF_LANG['ad_user_error_noId']);
        } else {
            $user = new User($faqConfig);
            $perm = $user->perm;
            // @todo: Add Filter::filterInput[]
            $userRights = isset($_POST['user_rights']) ? $_POST['user_rights'] : [];
            if (!$perm->refuseAllUserRights($userId)) {
                $message .= sprintf('<p class="alert alert-danger">%s</p>', $PMF_LANG['ad_msg_mysqlerr']);
            }
            foreach ($userRights as $rightId) {
                $perm->grantUserRight($userId, $rightId);
            }
            $idUser = $user->getUserById($userId, true);
            $message .= sprintf(
                '<p class="alert alert-success">%s <strong>%s</strong> %s</p>',
                $PMF_LANG['ad_msg_savedsuc_1'],
                Strings::htmlentities($user->getLogin(), ENT_QUOTES),
                $PMF_LANG['ad_msg_savedsuc_2']
            );
            $message .= '<script>updateUser(' . $userId . ')</script>';
            $user = new CurrentUser($faqConfig);
        }
    }

    // update user data
    if ($userAction == 'update_data' && $user->perm->hasPermission($user->getUserId(), 'edit_user')) {
        $message = '';
        $userAction = $defaultUserAction;
        $userId = Filter::filterInput(INPUT_POST, 'user_id', FILTER_VALIDATE_INT, 0);
        if ($userId == 0) {
            $message .= sprintf('<p class="alert alert-danger">%s</p>', $PMF_LANG['ad_user_error_noId']);
        } else {
            $userData = [];
            $userData['display_name'] = Filter::filterInput(INPUT_POST, 'display_name', FILTER_UNSAFE_RAW);
            $userData['email'] = Filter::filterInput(INPUT_POST, 'email', FILTER_VALIDATE_EMAIL);
            $userData['last_modified'] = Filter::filterInput(INPUT_POST, 'last_modified', FILTER_UNSAFE_RAW);
            $userStatus = Filter::filterInput(INPUT_POST, 'user_status', FILTER_UNSAFE_RAW, $defaultUserStatus);
            $isSuperAdmin = Filter::filterInput(INPUT_POST, 'is_superadmin', FILTER_UNSAFE_RAW);
            $isSuperAdmin = $isSuperAdmin === 'on';

            if (!$user->isSuperAdmin()) {
                $isSuperAdmin = false;
            }

            // Sanity check
            if (is_null($userData['email'])) {
                $message .= sprintf('<p class="alert alert-danger">%s</p>', $PMF_LANG['err_noMailAdress']);
            } else {
                $user = new User($faqConfig);
                $user->getUserById($userId, true);

                $stats = $user->getStatus();

                // set new password an send email if user is switched to active
                if ($stats == 'blocked' && $userStatus == 'active') {
                    if (!$user->activateUser()) {
                        $userStatus = 'invalid_status';
                    }
                }

                // Set super-admin flag
                $user->setSuperAdmin($isSuperAdmin);

                if (
                    !$user->userdata->set(array_keys($userData), array_values($userData)) ||
                    !$user->setStatus($userStatus)
                ) {
                    $message .= sprintf('<p class="alert alert-danger">%s</p>', $PMF_LANG['ad_msg_mysqlerr']);
                } else {
                    $message .= sprintf(
                        '<p class="alert alert-success">%s <strong>%s</strong> %s</p>',
                        $PMF_LANG['ad_msg_savedsuc_1'],
                        Strings::htmlentities($user->getLogin(), ENT_QUOTES),
                        $PMF_LANG['ad_msg_savedsuc_2']
                    );
                    $message .= '<script>updateUser(' . $userId . ');</script>';
                }
            }
        }
    }

    // delete user confirmation
    if ($userAction == 'delete_confirm' && $user->perm->hasPermission($user->getUserId(), 'delete_user')) {
        $message = '';
        $user = new CurrentUser($faqConfig);

        $userId = Filter::filterInput(INPUT_GET, 'user_delete_id', FILTER_VALIDATE_INT, 0);
        if ($userId == 0) {
            $message .= sprintf('<p class="alert alert-danger">%s</p>', $PMF_LANG['ad_user_error_noId']);
            $userAction = $defaultUserAction;
        } else {
            $user->getUserById($userId, true);
            // account is protected
            if ($user->getStatus() == 'protected' || $userId == 1) {
                $message .= sprintf(
                    '<p class="alert alert-danger">%s</p>',
                    $PMF_LANG['ad_user_error_protectedAccount']
                );
                $userAction = $defaultUserAction;
            } else {
                ?>

              <div
                class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h1 class="h2">
                  <i aria-hidden="true" class="fa fa-user"></i>
                    <?= $PMF_LANG['ad_user_deleteUser'] ?> <?= Strings::htmlentities($user->getLogin(), ENT_QUOTES) ?>
                </h1>
              </div>

              <p class="alert alert-danger">
                  <?= $PMF_LANG['ad_user_del_3'] . ' ' . $PMF_LANG['ad_user_del_1'] . ' ' . $PMF_LANG['ad_user_del_2'] ?>
              </p>
              <form action="?action=user&amp;user_action=delete" method="post" accept-charset="utf-8">
                <input type="hidden" name="user_id" value="<?= $userId ?>">
                <input type="hidden" name="csrf" value="<?= $currentUser->getCsrfTokenFromSession() ?>">
                <p class="text-center">
                  <button class="btn btn-danger" type="submit">
                      <?= $PMF_LANG['ad_gen_yes'] ?>
                  </button>
                  <a class="btn btn-info" href="?action=user">
                      <?= $PMF_LANG['ad_gen_no'] ?>
                  </a>
                </p>
              </form>
                <?php
            }
        }
    }

    // delete user
    if ($userAction == 'delete' && $user->perm->hasPermission($user->getUserId(), 'delete_user')) {
        $message = '';
        $user = new User($faqConfig);
        $userId = Filter::filterInput(INPUT_POST, 'user_id', FILTER_VALIDATE_INT, 0);
        $csrfOkay = true;
        $csrfToken = Filter::filterInput(INPUT_POST, 'csrf', FILTER_UNSAFE_RAW);
        $userAction = $defaultUserAction;

        if (!isset($_SESSION['phpmyfaq_csrf_token']) || $_SESSION['phpmyfaq_csrf_token'] !== $csrfToken) {
            $csrfOkay = false;
        }
        $userAction = $defaultUserAction;
        if (0 === (int)$userId || !$csrfOkay) {
            $message .= sprintf('<p class="alert alert-danger">%s</p>', $PMF_LANG['ad_user_error_noId']);
        } else {
            if (!$user->getUserById($userId, true)) {
                $message .= sprintf('<p class="alert alert-danger">%s</p>', $PMF_LANG['ad_user_error_noId']);
            }
            if (!$user->deleteUser()) {
                $message .= sprintf('<p class="alert alert-danger">%s</p>', $PMF_LANG['ad_user_error_delete']);
            } else {
                // Move the categories ownership to admin (id == 1)
                $oCat = new Category($faqConfig, [], false);
                $oCat->setUser($currentAdminUser);
                $oCat->setGroups($currentAdminGroups);
                $oCat->moveOwnership((int) $userId, 1);

                // Remove the user from groups
                if ('basic' !== $faqConfig->get('security.permLevel')) {
                    $oPerm = Permission::selectPerm('medium', $faqConfig);
                    $oPerm->removeFromAllGroups($userId);
                }

                $message .= sprintf('<p class="alert alert-success">%s</p>', $PMF_LANG['ad_user_deleted']);
            }
            $userError = $user->error();
            if ($userError != '') {
                $message .= sprintf('<p class="alert alert-danger">%s</p>', $userError);
            }
        }
    }

    if (!isset($message)) {
        $message = '';
    }

    // show list of users
    if ($userAction === 'list') { ?>
      <div
        class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
        <h1 class="h2">
          <i aria-hidden="true" class="fa fa-user"></i>
            <?= $PMF_LANG['ad_user'] ?>
        </h1>
        <div class="btn-toolbar mb-2 mb-md-0">
          <div class="btn-group mr-2">
              <?php if ($currentUser->perm->hasPermission($user->getUserId(), 'add_user')) : ?>
                <button type="button" class="btn btn-sm btn-primary" data-toggle="modal" data-target="#addUserModal">
                  <i class="fa fa-user-plus" aria-label="true"></i> <?= $PMF_LANG['ad_user_add'] ?>
                </button>
              <?php endif ?>
              <?php if ($currentUser->perm->hasPermission($user->getUserId(), 'edit_user')) : ?>
                <a class="btn btn-sm btn-secondary" href="?action=user&amp;user_action=listallusers">
                  <i class="fa fa-users" aria-label="true"></i> <?= $PMF_LANG['list_all_users'] ?>
                </a>
              <?php endif ?>
          </div>
        </div>
      </div>

      <div id="pmf-user-message"><?= $message ?></div>

      <script>
        /**
         * Returns the user data as JSON object
         *
         * @param user_id User ID
         */
        function getUserData(user_id) {
          $('#user_data_table').empty();
          $.getJSON('index.php?action=ajax&ajax=user&ajaxaction=get_user_data&user_id=' + user_id, function(data) {
            $('#update_user_id').val(data.user_id);
            $('#user_status_select').val(data.status);
            $('#user_list_autocomplete').val(data.login);
            $('#user_list_select').val(data.user_id);
            $('#modal_user_id').val(data.user_id);
            // Append input fields
            $('#user_data_table').append(
              '<div class="form-group row">' +
              '<label class="col-lg-4 col-form-label"><?= $PMF_LANG['ad_user_realname'] ?></label>' +
              '<div class="col-lg-8">' +
              '<input type="text" name="display_name" value="' + data.display_name + '" class="form-control" required>' +
              '</div>' +
              '</div>' +
              '<div class="form-group row">' +
              '<label class="col-lg-4 col-form-label"><?= $PMF_LANG['ad_entry_email'] ?></label>' +
              '<div class="col-lg-8">' +
              '<input type="email" name="email" value="' + data.email + '" class="form-control" required>' +
              '</div>' +
              '</div>' +
              '<div class="form-group row">' +
              '<div class="offset-lg-4 col-lg-8">' +
              '<div class="form-check">' +
              '<input class="form-check-input" type="checkbox" id="is_superadmin" name="is_superadmin">' +
              '<label class="form-check-label" for="is_superadmin"><?= $PMF_LANG['ad_user_is_superadmin'] ?></label>' +
              '</div>' +
              '</div>' +
              '</div>' +
              '<div class="form-group row">' +
              '<div class="offset-lg-4 col-lg-8">' +
              '<a class="btn btn-danger pmf-admin-overwrite-password" data-toggle="modal" ' +
              '   href="#pmf-modal-user-password-overwrite"><?= $PMF_LANG['ad_user_overwrite_passwd'] ?></a>' +
              '</div>' +
              '</div>' +
              '<input type="hidden" name="last_modified" value="' + data.last_modified + '">',
            );
            if (data.is_superadmin) {
              $('#is_superadmin').attr('checked', 'checked');
            }
          });
        }
      </script>

      <div class="row">
        <div class="col-lg-4">
          <form name="user_select" id="user_select" action="?action=user&amp;user_action=delete_confirm"
                method="post" role="form" class="form_inline">
            <div class="card mb-4">
              <h5 class="card-header py-3">
                <i aria-hidden="true" class="fa fa-search"></i> <?= $PMF_LANG['msgSearch'] ?>
              </h5>
              <div class="card-body">
                <div class="input-group">
                  <input type="text" id="user_list_autocomplete" name="user_list_search"
                         class="form-control pmf-user-autocomplete" autocomplete="off"
                         placeholder="<?= $PMF_LANG['ad_auth_user'] ?>">
                </div>
              </div>
            </div>
          </form>
        </div>

        <div class="col-lg-4">
          <div class="card mb-4">
            <h5 class="card-header py-3" id="user_data_legend">
              <i aria-hidden="true" class="fa fa-user"></i> <?= $PMF_LANG['ad_user_profou'] ?>
            </h5>
            <form action="?action=user&amp;user_action=update_data" method="post">
              <div class="card-body">
                <input id="update_user_id" type="hidden" name="user_id" value="0">
                <input type="hidden" name="csrf" value="<?= $currentUser->getCsrfTokenFromSession(); ?>">
                <div class="form-group row">
                  <label for="user_status_select" class="col-lg-4 col-form-label">
                      <?= $PMF_LANG['ad_user_status'] ?>
                  </label>
                  <div class="col-lg-8">
                    <select id="user_status_select" class="form-control" name="user_status">
                      <option value="active"><?= $PMF_LANG['ad_user_active'] ?></option>
                      <option value="blocked"><?= $PMF_LANG['ad_user_blocked'] ?></option>
                      <option value="protected"><?= $PMF_LANG['ad_user_protected'] ?></option>
                    </select>
                  </div>
                </div>
                <div id="user_data_table"></div>
              </div>
              <div class="card-footer">
                <div class="card-button text-right">
                  <button class="btn btn-success" type="submit">
                    <i aria-hidden="true" class="fa fa-check"></i> <?= $PMF_LANG['ad_gen_save'] ?>
                  </button>
                </div>
              </div>
            </form>
          </div>
        </div>
        <div class="col-lg-4" id="userRights">
          <form id="rightsForm" action="?action=user&amp;user_action=update_rights" method="post"
                accept-charset="utf-8">
            <input type="hidden" name="csrf" value="<?= $currentUser->getCsrfTokenFromSession() ?>">
            <input type="hidden" name="user_id" id="rights_user_id" value="0">

            <div class="card mb-4">
              <h5 class="card-header py-3" id="user_rights_legend">
                <i aria-hidden="true" class="fa fa-lock"></i> <?= $PMF_LANG['ad_user_rights'] ?>
              </h5>
              <div class="card-body">
                <div class="text-center mb-3">
                  <a class="btn btn-primary btn-sm" href="#" id="checkAll">
                    <?= $PMF_LANG['ad_user_checkall'] ?>
                    /
                    <?= $PMF_LANG['ad_user_uncheckall'] ?>
                  </a>
                </div>
                  <?php foreach ($user->perm->getAllRightsData() as $right) : ?>
                    <div class="form-check">
                      <input id="user_right_<?= $right['right_id'] ?>" type="checkbox"
                             name="user_rights[]" value="<?= $right['right_id'] ?>"
                             class="form-check-input permission">
                      <label class="form-check-label" for="user_right_<?= $right['right_id'] ?>">
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
                  <button class="btn btn-success" type="submit">
                      <?= $PMF_LANG['ad_gen_save'] ?>
                  </button>
                </div>
              </div>
            </div>
          </form>
        </div>
      </div>

      <div class="modal fade" id="pmf-modal-user-password-overwrite">
        <div class="modal-dialog">
          <div class="modal-content">
            <div class="modal-header">
              <h4><?= $PMF_LANG['ad_menu_passwd'] ?></h4>
              <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                <span aria-hidden="true">&times;</span>
              </button>
            </div>
            <div class="modal-body">
              <form action="#" method="post" accept-charset="utf-8" autocomplete="off">
                <input type="hidden" name="csrf" value="<?= $currentUser->getCsrfTokenFromSession() ?>">
                <input type="hidden" name="user_id" id="modal_user_id" value="<?= $userId ?>">

                <div class="form-group row">
                  <label class="col-lg-4 col-form-label" for="npass">
                      <?= $PMF_LANG['ad_passwd_new'] ?>
                  </label>
                  <div class="col-lg-8">
                    <input type="password" autocomplete="off" name="npass" id="npass" class="form-control" minlength="8"
                           required>
                  </div>
                </div>

                <div class="form-group row">
                  <label class="col-lg-4 col-form-label" for="bpass">
                      <?= $PMF_LANG['ad_passwd_con'] ?>
                  </label>
                  <div class="col-lg-8">
                    <input type="password" autocomplete="off" name="bpass" id="bpass" class="form-control" minlength="8"
                           required>
                  </div>
                </div>

              </form>
            </div>
            <div class="modal-footer">
              <button class="btn btn-primary pmf-user-password-overwrite-action">
                  <?= $PMF_LANG['ad_user_overwrite_passwd'] ?>
              </button>
            </div>
          </div>
        </div>
      </div>
        <?php
    }

    // show list of all users
    if ($userAction == 'listallusers' && $user->perm->hasPermission($user->getUserId(), 'edit_user')) {
        $allUsers = $user->getAllUsers(false);
        $numUsers = count($allUsers);
        $page = Filter::filterInput(INPUT_GET, 'page', FILTER_VALIDATE_INT, 0);
        $perPage = 10;
        $numPages = ceil($numUsers / $perPage);
        $lastPage = $page * $perPage;
        $firstPage = $lastPage - $perPage;

        $baseUrl = sprintf(
            '%sadmin/?action=user&amp;user_action=listallusers&amp;page=%d',
            $faqConfig->getDefaultUrl(),
            $page
        );

        // Pagination options
        $options = [
            'baseUrl' => $baseUrl,
            'total' => $numUsers,
            'perPage' => $perPage,
            'useRewrite' => false,
            'pageParamName' => 'page',
        ];
        $pagination = new Pagination($faqConfig, $options);
        ?>

      <div
        class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
        <h1 class="h2">
          <i aria-hidden="true" class="fa fa-user"></i>
            <?= $PMF_LANG['ad_user'] ?>
        </h1>
        <div class="btn-toolbar mb-2 mb-md-0">
          <div class="btn-group mr-2">
              <?php if ($currentUser->perm->hasPermission($user->getUserId(), 'add_user')) : ?>
                <button type="button" class="btn btn-sm btn-primary" data-toggle="modal" data-target="#addUserModal">
                  <i class="fa fa-user-plus" aria-label="true"></i> <?= $PMF_LANG['ad_user_add'] ?>
                </button>
              <?php endif ?>
              <button type="button" class="btn btn-sm btn-secondary" id="pmf-button-export-users">
                <i class="fa fa-download" aria-label="true"></i> Export users as CSV
              </button>
          </div>
        </div>
      </div>

      <div id="pmf-user-message"><?= $message ?></div>

      <table class="table table-striped">
        <thead class="thead-dark">
        <tr>
          <th><?= $PMF_LANG['ad_entry_id'] ?></th>
          <th><?= $PMF_LANG['ad_user_status'] ?></th>
          <th><?= $PMF_LANG['ad_user_is_superadmin'] ?></th>
          <th><?= $PMF_LANG['ad_user_is_visible'] ?></th>
          <th><?= $PMF_LANG['msgNewContentName'] ?></th>
          <th><?= $PMF_LANG['ad_auth_user'] ?></th>
          <th><?= $PMF_LANG['msgNewContentMail'] ?></th>
          <th colspan="3">&nbsp;</th>
        </tr>
        </thead>
          <?php if ($perPage < $numUsers) : ?>
            <tfoot>
            <tr>
              <td colspan="8"><?= $pagination->render() ?></td>
            </tr>
            </tfoot>
          <?php endif;
            ?>
        <tbody>
        <?php
        $counter = $displayedCounter = 0;
        foreach ($allUsers as $listedUserId) {
            $user->getUserById($listedUserId, true);

            if ($displayedCounter >= $perPage) {
                continue;
            }
            ++$counter;
            if ($counter <= $firstPage) {
                continue;
            }
            ++$displayedCounter;

            ?>
          <tr class="row_user_id_<?= $user->getUserId() ?>">
            <td><?= $user->getUserId() ?></td>
            <td class="text-center"><i class="fa <?php
            switch ($user->getStatus()) {
                case 'active':
                    echo 'fa-check-circle-o';
                    break;
                case 'blocked':
                    echo 'fa-ban';
                    break;
                case 'protected':
                    echo 'fa-lock';
                    break;
            }
            ?> icon_user_id_<?= $user->getUserId() ?>"></i></td>
            <td class="text-center">
              <i class="fa <?= $user->isSuperAdmin() ? 'fa-user-secret' : 'fa-user-times' ?>"></i>
            </td>
            <td class="text-center">
              <i class="fa <?= $user->getUserData('is_visible') ? 'fa-user' : 'fa-user-o' ?>"></i>
            </td>
            <td><?= Strings::htmlentities($user->getUserData('display_name'), ENT_QUOTES) ?></td>
            <td><?= Strings::htmlentities($user->getLogin(), ENT_QUOTES) ?></td>
            <td>
              <a href="mailto:<?= $user->getUserData('email') ?>">
                  <?= $user->getUserData('email') ?>
              </a>
            </td>
            <td>
              <a href="?action=user&amp;user_id=<?= $user->getUserData('user_id') ?>" class="btn btn-info">
                <i class="fa fa-pencil"></i> <?= $PMF_LANG['ad_user_edit'] ?>
              </a>
            </td>
            <td>
                <?php if ($user->getStatus() === 'blocked') : ?>
                  <a onclick="activateUser(this); return false;"
                     href="#" class="btn btn-success btn_user_id_<?= $user->getUserData('user_id') ?>"
                     data-csrf-token="<?= $currentUser->getCsrfTokenFromSession() ?>"
                     data-user-id="<?= $user->getUserData('user_id') ?>">
                      <?= $PMF_LANG['ad_news_set_active'] ?>
                  </a>
                <?php endif;
                ?>
            </td>
            <td>
                <?php if ($user->getStatus() !== 'protected') : ?>
                  <a href="#" onclick="deleteUser(this); return false;" class="btn btn-danger"
                     data-csrf-token="<?= $currentUser->getCsrfTokenFromSession() ?>"
                     data-user-id="<?= $user->getUserData('user_id') ?>">
                    <i class="fa fa-trash"></i> <?= $PMF_LANG['ad_user_delete'] ?>
                  </a>
                <?php endif;
                ?>
            </td>
          </tr>
            <?php
        }
        ?>
        </tbody>
      </table>
        <?php
    }
    ?>

  <!-- Modal to add a new user -->
  <div class="modal fade" id="addUserModal" tabindex="-1" role="dialog" aria-labelledby="addUserModalLabel"
       aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title" id="addUserModalLabel">
            <i aria-hidden="true" class="fa fa-user-plus"></i> <?= $PMF_LANG['ad_adus_adduser'] ?>
          </h5>
          <button type="button" class="close" data-dismiss="modal" aria-label="Close">
            <span aria-hidden="true">&times;</span>
          </button>
        </div>
        <div class="modal-body">
          <form action="#" method="post" role="form" id="pmf-add-user-form" class="needs-validation" autocomplete="off"
                novalidate>

            <input type="hidden" id="add_user_csrf" name="add_user_csrf"
                   value="<?= $currentUser->getCsrfTokenFromSession() ?>">

            <div class="alert alert-danger d-none" id="pmf-add-user-error-message"></div>

            <div class="form-group row">
              <label class="col-lg-4 col-form-label" for="add_user_name"><?= $PMF_LANG['ad_adus_name'] ?></label>
              <div class="col-lg-8">
                <input type="text" name="add_user_name" id="add_user_name" required tabindex="1" class="form-control">
              </div>
            </div>

            <div class="form-group row">
              <label class="col-lg-4 col-form-label"
                     for="add_user_realname"><?= $PMF_LANG['ad_user_realname'] ?></label>
              <div class="col-lg-8">
                <input type="text" name="add_user_realname" id="add_user_realname" required tabindex="2"
                       class="form-control">
              </div>
            </div>

            <div class="form-group row">
              <label class="col-lg-4 col-form-label" for="add_user_email"><?= $PMF_LANG['ad_entry_email'] ?></label>
              <div class="col-lg-8">
                <input type="email" name="user_email" id="add_user_email" required tabindex="3" class="form-control">
              </div>
            </div>

            <div class="form-group row">
              <div class="col-lg-4"></div>
              <div class="col-lg-8">
                <div class="form-check">
                  <input class="form-check-input" type="checkbox" id="add_user_automatic_password"
                         name="add_user_automatic_password">
                  <label class="form-check-label" for="add_user_automatic_password">
                      <?= $PMF_LANG['ad_add_user_change_password'] ?>
                  </label>
                </div>
              </div>
            </div>

            <div id="add_user_show_password_inputs">
              <div class="form-group row">
                <label class="col-lg-4 col-form-label"
                       for="add_user_password"><?= $PMF_LANG['ad_adus_password'] ?></label>
                <div class="col-lg-8">
                  <input type="password" name="add_user_password" id="add_user_password" class="form-control"
                         minlength="8" autocomplete="off" tabindex="4">
                </div>
              </div>

              <div class="form-group row">
                <label class="col-lg-4 col-form-label"
                       for="add_user_password_confirm"><?= $PMF_LANG['ad_passwd_con'] ?></label>
                <div class="col-lg-8">
                  <input type="password" name="add_user_password_confirm" id="add_user_password_confirm"
                         minlength="8" class="form-control" autocomplete="off" tabindex="5">
                </div>
              </div>
            </div>

            <?php if ($user->isSuperAdmin()) { ?>
            <div class="form-group row">
              <div class="col-lg-4"></div>
              <div class="col-lg-8">
                <div class="form-check">
                  <input class="form-check-input" type="checkbox" id="add_user_is_superadmin" name="user_is_superadmin">
                  <label class="form-check-label" for="add_user_is_superadmin">
                      <?= $PMF_LANG['ad_user_is_superadmin'] ?>
                  </label>
                </div>
              </div>
            </div>
            <?php } ?>

          </form>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-dismiss="modal">
              <?= $PMF_LANG['ad_gen_cancel'] ?>
          </button>
          <button type="button" class="btn btn-primary" id="pmf-add-user-action">
              <?= $PMF_LANG['ad_gen_save'] ?>
          </button>
        </div>
      </div>
    </div>
  </div>

  <script>
    /**
     * Ajax call to delete user
     *
     * @param identifier
     */
    function deleteUser(identifier) {
      if (confirm('<?= $PMF_LANG['ad_user_del_3'] ?>')) {
        const csrf = $(identifier).data('csrf-token');
        const userId = $(identifier).data('user-id');

        $.getJSON('index.php?action=ajax&ajax=user&ajaxaction=delete_user&user_id=' + userId + '&csrf=' + csrf,
          (response) => {
            $('#pmf-user-message').html(response);
            $('.row_user_id_' + userId).fadeOut('slow');
          });
      }
    }

    /**
     * Ajax call to delete user
     *
     * @param identifier
     */
    function activateUser(identifier) {
      if (confirm('<?= $PMF_LANG['ad_user_active'] ?>')) {
        const csrf = $(identifier).data('csrf-token');
        const userId = $(identifier).data('user-id');
        $.getJSON('index.php?action=ajax&ajax=user&ajaxaction=activate_user&user_id=' + userId + '&csrf=' + csrf,
          () => {
            const icon = $('.icon_user_id_' + userId);
            icon.toggleClass('fa-lock fa-check');
            $('.btn_user_id_' + userId).remove();
          });
      }
    }

  </script>

    <?php

    if (isset($userId)) {
        echo '<script>updateUser(' . $userId . ')</script>';
    }
} else {
    echo $PMF_LANG['err_NotAuth'];
}
