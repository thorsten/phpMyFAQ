<?php
/**
 * Displays the user management frontend.
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at http://mozilla.org/MPL/2.0/.
 *
 * @category  phpMyFAQ
 * @author    Lars Tiedemann <php@larstiedemann.de>
 * @author    Uwe Pries <uwe.pries@digartis.de>
 * @author    Sarah Hermann <sayh@gmx.de>
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2005-2019 phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2005-12-15
 */

use phpMyFAQ\Category;
use phpMyFAQ\Filter;
use phpMyFAQ\Link;
use phpMyFAQ\Mail;
use phpMyFAQ\Pagination;
use phpMyFAQ\Permission;
use phpMyFAQ\User;
use phpMyFAQ\User\CurrentUser;
use phpMyFAQ\Utils;

if (!defined('IS_VALID_PHPMYFAQ')) {
    $protocol = 'http';
    if (isset($_SERVER['HTTPS']) && strtoupper($_SERVER['HTTPS']) === 'ON') {
        $protocol = 'https';
    }
    header('Location: '.$protocol.'://'.$_SERVER['HTTP_HOST'].dirname($_SERVER['SCRIPT_NAME']));
    exit();
}

if ($user->perm->checkRight($user->getUserId(), 'edit_user') ||
    $user->perm->checkRight($user->getUserId(), 'delete_user') ||
    $user->perm->checkRight($user->getUserId(), 'add_user')) {
?>
    <script src="assets/js/user.js"></script>
<?php

    // set some parameters
    $selectSize = 10;
    $defaultUserAction = 'list';
    $defaultUserStatus = 'active';
    $userActionList = [
        'udpate_rights',
        'update_data',
        'delete_confirm',
        'delete',
        'addsave',
        'add',
        'list',
        'listallusers'
    ];

    // what shall we do?
    // actions defined by url: user_action=
    $userAction = Filter::filterInput(INPUT_GET, 'user_action', FILTER_SANITIZE_STRING, $defaultUserAction);
    $currentUser = new CurrentUser($faqConfig);

    // actions defined by submit button
    if (isset($_POST['user_action_deleteConfirm'])) {
        $userAction = 'delete_confirm';
    }
    if (isset($_POST['cancel'])) {
        $userAction = $defaultUserAction;
    }

    // validate userAction
    if (!in_array($userAction, $userActionList)){
        // @Todo: Implement Error message
    }

    // update user rights
    if ($userAction == 'update_rights' && $user->perm->checkRight($user->getUserId(), 'edit_user')) {
        $message = '';
        $userAction = $defaultUserAction;
        $userId = Filter::filterInput(INPUT_POST, 'user_id', FILTER_VALIDATE_INT, 0);
        $csrfOkay = true;
        $csrfToken = Filter::filterInput(INPUT_POST, 'csrf', FILTER_SANITIZE_STRING);
        if (!isset($_SESSION['phpmyfaq_csrf_token']) || $_SESSION['phpmyfaq_csrf_token'] !== $csrfToken) {
            $csrfOkay = false;
        }
        if (0 === (int) $userId || !$csrfOkay) {
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
            $message .= sprintf('<p class="alert alert-success">%s <strong>%s</strong> %s</p>',
                $PMF_LANG['ad_msg_savedsuc_1'],
                $user->getLogin(),
                $PMF_LANG['ad_msg_savedsuc_2']);
            $message .= '<script>updateUser('.$userId.');</script>';
            $user = new CurrentUser($faqConfig);
        }
    }

    // update user data
    if ($userAction == 'update_data' && $user->perm->checkRight($user->getUserId(), 'edit_user')) {
        $message = '';
        $userAction = $defaultUserAction;
        $userId = Filter::filterInput(INPUT_POST, 'user_id', FILTER_VALIDATE_INT, 0);
        if ($userId == 0) {
            $message .= sprintf('<p class="alert alert-danger">%s</p>', $PMF_LANG['ad_user_error_noId']);
        } else {
            $userData = [];
            $userData['display_name'] = Filter::filterInput(INPUT_POST, 'display_name', FILTER_SANITIZE_STRING, '');
            $userData['email'] = Filter::filterInput(INPUT_POST, 'email', FILTER_VALIDATE_EMAIL, '');
            $userData['last_modified'] = Filter::filterInput(INPUT_POST, 'last_modified', FILTER_SANITIZE_STRING, '');
            $userStatus = Filter::filterInput(INPUT_POST, 'user_status', FILTER_SANITIZE_STRING, $defaultUserStatus);
            $isSuperAdmin = Filter::filterInput(INPUT_POST, 'is_superadmin', FILTER_SANITIZE_STRING);
            $isSuperAdmin = $isSuperAdmin === 'on' ? true : false;

            $user = new User($faqConfig);
            $user->getUserById($userId, true);

            $stats = $user->getStatus();
            // set new password an send email if user is switched to active
            if ($stats == 'blocked' && $userStatus == 'active') {
                if (!$user->activateUser()) {
                    $userStatus = 'invalid_status';
                }
            }

            if (!$user->userdata->set(array_keys($userData), array_values($userData)) ||
                !$user->setStatus($userStatus) /*||
                !$user->setSuperAdmin($isSuperAdmin)*/
            ) {
                $message .= sprintf('<p class="alert alert-danger">%s</p>', $PMF_LANG['ad_msg_mysqlerr']);
            } else {
                $message .= sprintf('<p class="alert alert-success">%s <strong>%s</strong> %s</p>',
                    $PMF_LANG['ad_msg_savedsuc_1'],
                    $user->getLogin(),
                    $PMF_LANG['ad_msg_savedsuc_2']);
                $message .= '<script>updateUser('.$userId.');</script>';
            }
        }
    }

    // delete user confirmation
    if ($userAction == 'delete_confirm' && $user->perm->checkRight($user->getUserId(), 'delete_user')) {
        $message = '';
        $user = new CurrentUser($faqConfig);

        $userId = Filter::filterInput(INPUT_GET, 'user_delete_id', FILTER_VALIDATE_INT, 0);
        if ($userId == 0) {
            $message   .= sprintf('<p class="alert alert-danger">%s</p>', $PMF_LANG['ad_user_error_noId']);
            $userAction = $defaultUserAction;
        } else {
            $user->getUserById($userId, true);
            // account is protected
            if ($user->getStatus() == 'protected' || $userId == 1) {
                $message   .= sprintf('<p class="alert alert-danger">%s</p>', $PMF_LANG['ad_user_error_protectedAccount']);
                $userAction = $defaultUserAction;
            } else {
                ?>

        <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
          <h1 class="h2">
            <i aria-hidden="" class="fas fa-user"></i>
              <?= $PMF_LANG['ad_user_deleteUser'] ?> <?= $user->getLogin() ?>
          </h1>
        </div>

        <p class="alert alert-danger">
            <?= $PMF_LANG['ad_user_del_3'].' '.$PMF_LANG['ad_user_del_1'].' '.$PMF_LANG['ad_user_del_2'] ?>
        </p>
        <form action ="?action=user&amp;user_action=delete" method="post" accept-charset="utf-8">
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
    if ($userAction == 'delete' && $user->perm->checkRight($user->getUserId(), 'delete_user')) {
        $message = '';
        $user = new User($faqConfig);
        $userId = Filter::filterInput(INPUT_POST, 'user_id', FILTER_VALIDATE_INT, 0);
        $csrfOkay = true;
        $csrfToken = Filter::filterInput(INPUT_POST, 'csrf', FILTER_SANITIZE_STRING);
        $userAction = $defaultUserAction;

        if (!isset($_SESSION['phpmyfaq_csrf_token']) || $_SESSION['phpmyfaq_csrf_token'] !== $csrfToken) {
            $csrfOkay = false;
        }
        $userAction = $defaultUserAction;
        if (0 === (int) $userId || !$csrfOkay) {
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
                $oCat->moveOwnership($userId, 1);

                // Remove the user from groups
                if ('medium' == $faqConfig->get('security.permLevel')) {
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

    // save new user
    if ($userAction == 'addsave' && $user->perm->checkRight($user->getUserId(), 'add_user')) {
        $user = new User($faqConfig);
        $message = '';
        $messages = [];
        $userName = Filter::filterInput(INPUT_POST, 'user_name', FILTER_SANITIZE_STRING, '');
        $userRealName = Filter::filterInput(INPUT_POST, 'user_realname', FILTER_SANITIZE_STRING, '');
        $userPassword = Filter::filterInput(INPUT_POST, 'user_password', FILTER_SANITIZE_STRING, '');
        $userEmail = Filter::filterInput(INPUT_POST, 'user_email', FILTER_VALIDATE_EMAIL);
        $userPassword = Filter::filterInput(INPUT_POST, 'user_password', FILTER_SANITIZE_STRING, '');
        $userPasswordConfirm = Filter::filterInput(INPUT_POST, 'user_password_confirm', FILTER_SANITIZE_STRING, '');
        $userIsSuperAdmin = Filter::filterInput(INPUT_POST, 'user_is_superadmin', FILTER_SANITIZE_STRING);
        $csrfToken = Filter::filterInput(INPUT_POST, 'csrf', FILTER_SANITIZE_STRING);
        $csrfOkay = true;

        if (!isset($_SESSION['phpmyfaq_csrf_token']) || $_SESSION['phpmyfaq_csrf_token'] !== $csrfToken) {
            $csrfOkay = false;
        }

        if ($userPassword != $userPasswordConfirm) {
            $userPassword = '';
            $userPasswordConfirm = '';
            $messages[] = $PMF_LANG['ad_user_error_passwordsDontMatch'];
        }

        // check login name
        if (!$user->isValidLogin($userName)) {
            $userName = '';
            $messages[] = $PMF_LANG['ad_user_error_loginInvalid'];
        }
        if ($user->getUserByLogin($userName)) {
            $userName = '';
            $messages[] = $PMF_LANG['ad_adus_exerr'];
        }
        // check realname
        if ($userRealName == '') {
            $userRealName = '';
            $messages[] = $PMF_LANG['ad_user_error_noRealName'];
        }
        // check e-mail
        if (is_null($userEmail)) {
            $userEmail = '';
            $messages[] = $PMF_LANG['ad_user_error_noEmail'];
        }

        // ok, let's go
        if (count($messages) == 0 && $csrfOkay) {
            // create user account (login and password)
            if (!$user->createUser($userName, $userPassword)) {
                $messages[] = $user->error();
            } else {
                // set user data (realname, email)
                $user->userdata->set(array('display_name', 'email'), array($userRealName, $userEmail));
                // set user status
                $user->setStatus($defaultUserStatus);
                $user->setSuperAdmin($userIsSuperAdmin === 'on' ? true : false);
            }
        }

        // no errors, send notification to user and show list
        if (count($messages) == 0) {
            $text = sprintf(
                "You have been registrated as a new user:\n\nName: %s\nLogin name: %s\n\nPassword: %\n\n".
                'Check it out here: %s.',
                $userRealName,
                $userName,
                $user->createPassword(),
                $faqConfig->getDefaultUrl()
            );

            $mail = new Mail($faqConfig);
            $mail->addTo($userEmail, $userName);
            $mail->subject = Utils::resolveMarkers($PMF_LANG['emailRegSubject'], $faqConfig);
            $mail->message = $text;
            $result = $mail->send();

            $userAction = $defaultUserAction;
            $message = sprintf('<p class="alert alert-success">%s</p>', $PMF_LANG['ad_adus_suc']);
            // display error messages and show form again
        } else {
            $userAction = 'add';
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

    // show new user form
    if ($userAction == 'add' && $user->perm->checkRight($user->getUserId(), 'add_user')) {
        ?>

      <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
        <h1 class="h2">
          <i aria-hidden="" class="fas fa-user"></i>
            <?= $PMF_LANG['ad_adus_adduser'] ?>
        </h1>
      </div>

        <div id="user_message"><?= $message ?></div>
        <div id="user_create">

            <form  action="?action=user&amp;user_action=addsave" method="post" role="form"
                  accept-charset="utf-8">
            <input type="hidden" name="csrf" value="<?= $currentUser->getCsrfTokenFromSession() ?>">

            <div class="form-group row">
                <label class="col-lg-2 col-form-label" for="user_name"><?= $PMF_LANG['ad_adus_name'] ?></label>
                <div class="col-lg-3">
                    <input type="text" name="user_name" id="user_name" required tabindex="1" class="form-control"
                           value="<?= (isset($userName) ? $userName : '') ?>">
                </div>
            </div>

            <div class="form-group row">
                <label class="col-lg-2 col-form-label" for="user_realname"><?= $PMF_LANG['ad_user_realname'] ?></label>
                <div class="col-lg-3">
                <input type="text" name="user_realname" id="user_realname" required tabindex="2" class="form-control"
                   value="<?= (isset($userRealName) ? $userRealName : '') ?>">
                </div>
            </div>

            <div class="form-group row">
                <label class="col-lg-2 col-form-label" for="user_email"><?= $PMF_LANG['ad_entry_email'] ?></label>
                <div class="col-lg-3">
                    <input type="email" name="user_email" id="user_email" required tabindex="3" class="form-control"
                           value="<?= (isset($userEmail) ? $userEmail : '') ?>">
                </div>
            </div>

            <div class="form-group row">
                <label class="col-lg-2 col-form-label" for="password"><?= $PMF_LANG['ad_adus_password'] ?></label>
                <div class="col-lg-3">
                    <input type="password" name="user_password" id="password" required tabindex="4" class="form-control"
                           value="<?= (isset($userPassword) ? $userPassword : '') ?>">
                </div>
            </div>

             <div class="form-group row">
                 <label class="col-lg-2 col-form-label" for="password_confirm"><?= $PMF_LANG['ad_passwd_con'] ?></label>
                 <div class="col-lg-3">
                    <input type="password" name="user_password_confirm" id="password_confirm" required class="form-control"
                           tabindex="5" value="<?=(isset($userPasswordConfirm) ? $userPasswordConfirm : '') ?>">
                 </div>
            </div>

            <div class="form-group form-check row">
                <div class="offset-lg-2 col-lg-3">
                    <input class="form-check-input" type="checkbox" id="is_superadmin" name="user_is_superadmin">
                    <label class="form-check-label" for="is_superadmin">
                        <?= $PMF_LANG['ad_user_is_superadmin'] ?>
                    </label>
                </div>
            </div>

            <div class="form-group row">
                <div class="col-lg-offset-2 col-lg-10">
                    <button class="btn btn-success" type="submit">
                        <?= $PMF_LANG['ad_gen_save'] ?>
                    </button>
                    <a class="btn btn-info" href="?action=user">
                        <?= $PMF_LANG['ad_gen_cancel'] ?>
                    </a>
                </div>
            </div>
        </form>
</div> <!-- end #user_create -->
<?php

    }

    // show list of users
    if ($userAction == 'list') { ?>

      <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
        <h1 class="h2">
          <i aria-hidden="" class="fas fa-user"></i>
            <?= $PMF_LANG['ad_user'] ?>
        </h1>
        <div class="btn-toolbar mb-2 mb-md-0">
          <div class="btn-group mr-2">
            <a class="btn btn-sm btn-outline-success" href="?action=user&amp;user_action=add">
              <?= $PMF_LANG['ad_user_add'] ?>
            </a>
            <?php if ($currentUser->perm->checkRight($user->getUserId(), 'edit_user')): ?>
              <a class="btn btn-sm btn-outline-info" href="?action=user&amp;user_action=listallusers">
                <?= $PMF_LANG['list_all_users'] ?>
              </a>
            <?php endif ?>
          </div>
        </div>
      </div>

        <script>
        /**
         * Returns the user data as JSON object
         *
         * @param user_id User ID
         */
        function getUserData(user_id) {
            $('#user_data_table').empty();
            $.getJSON("index.php?action=ajax&ajax=user&ajaxaction=get_user_data&user_id=" + user_id, function(data) {
                $('#update_user_id').val(data.user_id);
                $('#user_status_select').val(data.status);
                $('#user_list_autocomplete').val(data.login);
                $('#user_list_select').val(data.user_id);
                $('#modal_user_id').val(data.user_id);
                // Append input fields
                $('#user_data_table').append(
                    '<div class="form-group row">' +
                        '<label class="col-lg-3 col-form-label"><?= $PMF_LANG['ad_user_realname'] ?></label>' +
                        '<div class="col-lg-9">' +
                            '<input type="text" name="display_name" value="' + data.display_name + '" class="form-control" required>' +
                        '</div>' +
                    '</div>' +
                    '<div class="form-group row">' +
                        '<label class="col-lg-3 col-form-label"><?= $PMF_LANG['ad_entry_email'] ?></label>' +
                        '<div class="col-lg-9">' +
                            '<input type="email" name="email" value="' + data.email + '" class="form-control" required>' +
                        '</div>' +
                    '</div>' +
                    '<div class="form-group form-check row">' +
                        '<div class="offset-lg-3 col-lg-9">' +
                            '<input class="form-check-input" type="checkbox" id="is_superadmin" name="is_superadmin">' +
                            '<label class="form-check-label" for="is_superadmin"><?= $PMF_LANG['ad_user_is_superadmin'] ?></label>' +
                        '</div>' +
                    '</div>' +
                    '<div class="form-group row">' +
                        '<div class="col-lg-9 col-lg-offset-3">' +
                            '<a class="btn btn-danger pmf-admin-override-password" data-toggle="modal" ' +
                            '   href="#pmf-modal-user-password-override"><?= $PMF_LANG['ad_user_override_passwd'] ?></a>' +
                        '</div>' +
                    '</div>' +
                    '<input type="hidden" name="last_modified" value="' + data.last_modified + '">'
                );
                if (data.is_superadmin) {
                    $('#is_superadmin').attr('checked', 'checked');
                }
            });
        }
        </script>

        <div id="user_message"><?= $message ?></div>

        <div class="row">
            <div class="col-lg-4">
                <form name="user_select" id="user_select" action="?action=user&amp;user_action=delete_confirm"
                       method="post" role="form" class="form_inline">
                    <div class="card">
                        <div class="card-header">
                            <i aria-hidden="true" class="fas fa-search"></i> <?= $PMF_LANG['msgSearch'] ?>
                        </div>
                        <div class="card-body">
                            <div class="input-group">
                                <input type="text" id="user_list_autocomplete" name="user_list_search"
                                       class="form-control pmf-user-autocomplete"
                                       placeholder="<?= $PMF_LANG['ad_auth_user'] ?>">
                            </div>
                        </div>
                    </div>
                </form>
            </div>

            <div class="col-lg-4">
                <div class="card">
                    <div class="card-header" id="user_data_legend">
                      <i aria-hidden="true" class="fas fa-user"></i> <?= $PMF_LANG['ad_user_profou'] ?>
                    </div>
                    <form action="?action=user&amp;user_action=update_data" method="post" accept-charset="utf-8"
                          >
                        <div class="card-body">
                            <input id="update_user_id" type="hidden" name="user_id" value="0">
                            <input type="hidden" name="csrf" value="<?= $currentUser->getCsrfTokenFromSession(); ?>">
                            <div class="form-group row">
                                <label for="user_status_select" class="col-lg-3 col-form-label">
                                    <?= $PMF_LANG['ad_user_status'] ?>
                                </label>
                                <div class="col-lg-9">
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
                                    <i aria-hidden="true" class="fas fa-check"></i> <?= $PMF_LANG['ad_gen_save'] ?>
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
            <div class="col-lg-4" id="userRights">
                <form id="rightsForm" action="?action=user&amp;user_action=update_rights" method="post" accept-charset="utf-8">
                    <input type="hidden" name="csrf" value="<?= $currentUser->getCsrfTokenFromSession() ?>">
                    <input type="hidden" name="user_id" id="rights_user_id" value="0">

                    <div class="card">
                        <div class="card-header" id="user_rights_legend">
                            <i aria-hidden="true" class="fas fa-lock"></i> <?= $PMF_LANG['ad_user_rights'] ?>
                            <span class="float-right">
                                <a class="btn btn-secondary btn-sm" href="#" id="checkAll">
                                    <?= $PMF_LANG['ad_user_checkall'] ?>
                                    /
                                    <?= $PMF_LANG['ad_user_uncheckall'] ?>
                                </a>
                            </span>
                        </div>
                        <div class="card-body">
                          <?php foreach ($user->perm->getAllRightsData() as $right): ?>
                              <div class="form-check">
                                <input id="user_right_<?= $right['right_id'] ?>" type="checkbox"
                                       name="user_rights[]" value="<?= $right['right_id'] ?>"
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
                                <button class="btn btn-success" type="submit">
                                    <?= $PMF_LANG['ad_gen_save'] ?>
                                </button>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <div class="modal fade" id="pmf-modal-user-password-override">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <a class="close" data-dismiss="modal">×</a>
                        <h3><?= $PMF_LANG['ad_menu_passwd'] ?></h3>
                    </div>
                    <div class="modal-body">
                        <form  action="#" method="post" accept-charset="utf-8">
                            <input type="hidden" name="csrf" value="<?= $currentUser->getCsrfTokenFromSession() ?>">
                            <input type="hidden" name="user_id" id="modal_user_id" value="<?= $userId ?>">

                            <div class="form-group row">
                                <label class="col-lg-3 col-form-label" for="npass">
                                    <?= $PMF_LANG['ad_passwd_new'] ?>
                                </label>
                                <div class="col-lg-9">
                                    <input type="password" name="npass" id="npass" class="form-control" required>
                                </div>
                            </div>

                            <div class="form-group row">
                                <label class="col-lg-3 col-form-label" for="bpass">
                                    <?= $PMF_LANG['ad_passwd_con'] ?>
                                </label>
                                <div class="col-lg-9">
                                    <input type="password" name="bpass" id="bpass" class="form-control" required>
                                </div>
                            </div>

                        </form>
                    </div>
                    <div class="modal-footer">
                        <button class="btn btn-primary pmf-user-password-override-action">
                            <?= $PMF_LANG['ad_user_override_passwd'] ?>
                        </button>
                    </div>
                </div>
            </div>
        </div>
<?php

    }

    // show list of all users
    if ($userAction == 'listallusers' && $user->perm->checkRight($user->getUserId(), 'edit_user')) {
        $allUsers = $user->getAllUsers(false);
        $numUsers = count($allUsers);
        $page = Filter::filterInput(INPUT_GET, 'page', FILTER_VALIDATE_INT, 0);
        $perPage = 10;
        $numPages = ceil($numUsers / $perPage);
        $lastPage = $page * $perPage;
        $firstPage = $lastPage - $perPage;

        $baseUrl = sprintf(
            '%s?action=user&amp;user_action=listallusers&amp;page=%d',
            Link::getSystemRelativeUri(),
            $page
        );

        // Pagination options
        $options = array(
            'baseUrl' => $baseUrl,
            'total' => $numUsers,
            'perPage' => $perPage,
            'useRewrite' => false,
            'pageParamName' => 'page',
        );
        $pagination = new Pagination($faqConfig, $options);
        ?>

        <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
          <h1 class="h2">
            <i aria-hidden="" class="fas fa-user"></i>
              <?= $PMF_LANG['ad_user'] ?>
          </h1>
          <div class="btn-toolbar mb-2 mb-md-0">
            <div class="btn-group mr-2">
              <a class="btn btn-sm btn-outline-success" href="?action=user&amp;user_action=add">
                  <?= $PMF_LANG['ad_user_add'] ?>
              </a>
            </div>
          </div>
        </div>

        <div id="user_message"><?= $message;
        ?></div>
        <table class="table table-striped">
          <thead class="thead-dark">
            <tr>
                <th><?= $PMF_LANG['ad_entry_id'] ?></th>
                <th><?= $PMF_LANG['ad_user_status'] ?></th>
                <th><?= $PMF_LANG['ad_user_is_superadmin'] ?></th>
                <th><?= $PMF_LANG['msgNewContentName'] ?></th>
                <th><?= $PMF_LANG['ad_auth_user'] ?></th>
                <th><?= $PMF_LANG['msgNewContentMail'] ?></th>
                <th colspan="3">&nbsp;</th>
            </tr>
        </thead>
        <?php if ($perPage < $numUsers): ?>
        <tfoot>
            <tr>
                <td colspan="8"><?= $pagination->render();
        ?></td>
            </tr>
        </tfoot>
        <?php endif;
        ?>
        <tbody>
        <?php
            $counter = $displayedCounter = 0;
        foreach ($allUsers as $userId) {
            $user->getUserById($userId, true);

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
                <td><i class="fas <?php
                switch ($user->getStatus()) {
                    case 'active':
                        echo 'fa-user-check';
                        break;
                    case 'blocked':
                        echo 'fa-ban';
                        break;
                    case 'protected':
                        echo 'fa-user-lock';
                        break;
                }
            ?> icon_user_id_<?= $user->getUserId() ?>"></i></td>
                <td><i class="fas <?= $user->isSuperAdmin() ? 'fa-user-tie' : 'fa-user-minus' ?>"></i></td>
                <td><?= $user->getUserData('display_name') ?></td>
                <td><?= $user->getLogin() ?></td>
                <td>
                    <a href="mailto:<?= $user->getUserData('email') ?>">
                        <?= $user->getUserData('email') ?>
                    </a>
                </td>
                <td>
                    <a href="?action=user&amp;user_id=<?= $user->getUserData('user_id')?>" class="btn btn-info">
                        <?= $PMF_LANG['ad_user_edit'] ?>
                    </a>
                </td>
                <td>
                    <?php if ($user->getStatus() === 'blocked'): ?>
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
                    <?php if ($user->getStatus() !== 'protected'): ?>
                    <a href="#" onclick="deleteUser(this); return false;" class="btn btn-danger"
                       data-csrf-token="<?= $currentUser->getCsrfTokenFromSession() ?>"
                       data-user-id="<?= $user->getUserData('user_id') ?>">
                        <?= $PMF_LANG['ad_user_delete'] ?>
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

            $.getJSON("index.php?action=ajax&ajax=user&ajaxaction=delete_user&user_id=" + userId + "&csrf=" + csrf,
              (response) => {
                $('#user_message').html(response);
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
          if (confirm('<?= $PMF_LANG['ad_user_del_3'] ?>')) {
            const csrf = $(identifier).data('csrf-token');
            const userId = $(identifier).data('user-id');
            $.getJSON("index.php?action=ajax&ajax=user&ajaxaction=activate_user&user_id=" + userId + "&csrf=" + csrf,
              () => {
                const icon = $('.icon_user_id_' + userId);
                icon.toggleClass('fa-lock fa-check');
                $('.btn_user_id_' + userId).remove();
              });
          }
        }

        </script>
<?php 
    }
    if (isset($_GET['user_id'])) {
        $userId = Filter::filterInput(INPUT_GET, 'user_id', FILTER_VALIDATE_INT, 0);
        echo '        <script>updateUser('.$userId.');</script>';
    }
} else {
    echo $PMF_LANG['err_NotAuth'];
}
