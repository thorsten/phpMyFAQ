<?php
/**
 * Displays the user management frontend.
 *
 * PHP Version 5.5
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at http://mozilla.org/MPL/2.0/.
 *
 * @category  phpMyFAQ
 *
 * @author    Lars Tiedemann <php@larstiedemann.de>
 * @author    Uwe Pries <uwe.pries@digartis.de>
 * @author    Sarah Hermann <sayh@gmx.de>
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2005-2019 phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 *
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

if ($user->perm->checkRight($user->getUserId(), 'edituser') ||
    $user->perm->checkRight($user->getUserId(), 'deluser') ||
    $user->perm->checkRight($user->getUserId(), 'adduser')) {
?>
    <script src="assets/js/user.js"></script>
<?php

    // set some parameters
    $selectSize = 10;
    $defaultUserAction = 'list';
    $defaultUserStatus = 'active';

    // what shall we do?
    // actions defined by url: user_action=
    $userAction = PMF_Filter::filterInput(INPUT_GET, 'user_action', FILTER_SANITIZE_STRING, $defaultUserAction);
    $currentUser = new PMF_User_CurrentUser($faqConfig);

    // actions defined by submit button
    if (isset($_POST['user_action_deleteConfirm'])) {
        $userAction = 'delete_confirm';
    }
    if (isset($_POST['cancel'])) {
        $userAction = $defaultUserAction;
    }

    // update user rights
    if ($userAction == 'update_rights' && $user->perm->checkRight($user->getUserId(), 'edituser')) {
        $message = '';
        $userAction = $defaultUserAction;
        $userId = PMF_Filter::filterInput(INPUT_POST, 'user_id', FILTER_VALIDATE_INT, 0);
        $csrfOkay = true;
        $csrfToken = PMF_Filter::filterInput(INPUT_POST, 'csrf', FILTER_SANITIZE_STRING);
        if (!isset($_SESSION['phpmyfaq_csrf_token']) || $_SESSION['phpmyfaq_csrf_token'] !== $csrfToken) {
            $csrfOkay = false;
        }
        if (0 === (int) $userId || !$csrfOkay) {
            $message .= sprintf('<p class="alert alert-danger">%s</p>', $PMF_LANG['ad_user_error_noId']);
        } else {
            $user = new PMF_User($faqConfig);
            $perm = $user->perm;
            // @todo: Add PMF_Filter::filterInput[]
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
            $user = new PMF_User_CurrentUser($faqConfig);
        }
    }

    // update user data
    if ($userAction == 'update_data' && $user->perm->checkRight($user->getUserId(), 'edituser')) {
        $message = '';
        $userAction = $defaultUserAction;
        $userId = PMF_Filter::filterInput(INPUT_POST, 'user_id', FILTER_VALIDATE_INT, 0);
        if ($userId == 0) {
            $message .= sprintf('<p class="alert alert-danger">%s</p>', $PMF_LANG['ad_user_error_noId']);
        } else {
            $userData = [];
            $userData['display_name'] = PMF_Filter::filterInput(INPUT_POST, 'display_name', FILTER_SANITIZE_STRING, '');
            $userData['email'] = PMF_Filter::filterInput(INPUT_POST, 'email', FILTER_VALIDATE_EMAIL, '');
            $userData['last_modified'] = PMF_Filter::filterInput(INPUT_POST, 'last_modified', FILTER_SANITIZE_STRING, '');
            $userStatus = PMF_Filter::filterInput(INPUT_POST, 'user_status', FILTER_SANITIZE_STRING, $defaultUserStatus);

            $user = new PMF_User($faqConfig);
            $user->getUserById($userId, true);

            $stats = $user->getStatus();
            // set new password an send email if user is switched to active
            if ($stats == 'blocked' && $userStatus == 'active') {
                if (!$user->activateUser($faqConfig)) {
                    $userStatus == 'invalid_status';
                }
            }

            if (!$user->userdata->set(array_keys($userData), array_values($userData)) or !$user->setStatus($userStatus)) {
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
    if ($userAction == 'delete_confirm' && $user->perm->checkRight($user->getUserId(), 'deluser')) {
        $message = '';
        $user = new PMF_User_CurrentUser($faqConfig);

        $userId = PMF_Filter::filterInput(INPUT_POST, 'user_list_select', FILTER_VALIDATE_INT, 0);
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
        <header class="row">
            <div class="col-lg-12">
                <h2 class="page-header">
                    <i aria-hidden="true" class="fa fa-users"></i> <?php echo $PMF_LANG['ad_user_deleteUser'] ?> <?php echo $user->getLogin() ?>
                </h2>
            </div>
        </header>
        <p class="alert alert-danger"><?php echo $PMF_LANG['ad_user_del_3'].' '.$PMF_LANG['ad_user_del_1'].' '.$PMF_LANG['ad_user_del_2'];
                ?></p>
        <form action ="?action=user&amp;user_action=delete" method="post" accept-charset="utf-8">
            <input type="hidden" name="user_id" value="<?php echo $userId;
                ?>" />
            <input type="hidden" name="csrf" value="<?php echo $currentUser->getCsrfTokenFromSession();
                ?>" />
            <p class="text-center">
                <button class="btn btn-danger" type="submit">
                    <?php echo $PMF_LANG['ad_gen_yes'];
                ?>
                </button>
                <a class="btn btn-info" href="?action=user">
                    <?php echo $PMF_LANG['ad_gen_no'];
                ?>
                </a>
            </p>
        </form>
<?php

            }
        }
    }

    // delete user
    if ($userAction == 'delete' && $user->perm->checkRight($user->getUserId(), 'deluser')) {
        $message = '';
        $user = new PMF_User($faqConfig);
        $userId = PMF_Filter::filterInput(INPUT_POST, 'user_id', FILTER_VALIDATE_INT, 0);
        $csrfOkay = true;
        $csrfToken = PMF_Filter::filterInput(INPUT_POST, 'csrf', FILTER_SANITIZE_STRING);
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
                $oCat = new PMF_Category($faqConfig, [], false);
                $oCat->setUser($currentAdminUser);
                $oCat->setGroups($currentAdminGroups);
                $oCat->moveOwnership($userId, 1);

                // Remove the user from groups
                if ('medium' == $faqConfig->get('security.permLevel')) {
                    $oPerm = PMF_Perm::selectPerm('medium', $faqConfig);
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
    if ($userAction == 'addsave' && $user->perm->checkRight($user->getUserId(), 'adduser')) {
        $user = new PMF_User($faqConfig);
        $message = '';
        $messages = [];
        $userName = PMF_Filter::filterInput(INPUT_POST, 'user_name', FILTER_SANITIZE_STRING, '');
        $userRealName = PMF_Filter::filterInput(INPUT_POST, 'user_realname', FILTER_SANITIZE_STRING, '');
        $userPassword = PMF_Filter::filterInput(INPUT_POST, 'user_password', FILTER_SANITIZE_STRING, '');
        $userEmail = PMF_Filter::filterInput(INPUT_POST, 'user_email', FILTER_VALIDATE_EMAIL);
        $userPassword = PMF_Filter::filterInput(INPUT_POST, 'user_password', FILTER_SANITIZE_STRING, '');
        $userPasswordConfirm = PMF_Filter::filterInput(INPUT_POST, 'user_password_confirm', FILTER_SANITIZE_STRING, '');
        $csrfToken = PMF_Filter::filterInput(INPUT_POST, 'csrf', FILTER_SANITIZE_STRING);
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

            $mail = new PMF_Mail($faqConfig);
            $mail->setFrom($faqConfig->get('main.administrationMail'));
            $mail->addTo($userEmail, $userName);
            $mail->subject = PMF_Utils::resolveMarkers($PMF_LANG['emailRegSubject'], $faqConfig);
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
    if ($userAction == 'add' && $user->perm->checkRight($user->getUserId(), 'adduser')) {
        ?>
        <header class="row">
            <div class="col-lg-12">
                <h2 class="page-header"><i aria-hidden="true" class="fa fa-users fa-fw"></i> <?php echo $PMF_LANG['ad_adus_adduser'] ?></h2>
            </div>
        </header>

        <div id="user_message"><?php echo $message;
        ?></div>
        <div id="user_create">

            <form class="form-horizontal" action="?action=user&amp;user_action=addsave" method="post" role="form"
                  accept-charset="utf-8">
            <input type="hidden" name="csrf" value="<?php echo $currentUser->getCsrfTokenFromSession();
        ?>">

            <div class="form-group">
                <label class="col-lg-2 control-label" for="user_name"><?php echo $PMF_LANG['ad_adus_name'];
        ?></label>
                <div class="col-lg-3">
                    <input type="text" name="user_name" id="user_name" required tabindex="1" class="form-control"
                           value="<?php echo(isset($userName) ? $userName : '');
        ?>" />
                </div>
            </div>

            <div class="form-group">
                <label class="col-lg-2 control-label" for="user_realname"><?php echo $PMF_LANG['ad_user_realname'];
        ?></label>
                <div class="col-lg-3">
                <input type="text" name="user_realname" id="user_realname" required tabindex="2" class="form-control"
                   value="<?php echo(isset($userRealName) ? $userRealName : '');
        ?>" />
                </div>
            </div>

            <div class="form-group">
                <label class="col-lg-2 control-label" for="user_email"><?php echo $PMF_LANG['ad_entry_email'];
        ?></label>
                <div class="col-lg-3">
                    <input type="email" name="user_email" id="user_email" required tabindex="3" class="form-control"
                           value="<?php echo(isset($userEmail) ? $userEmail : '');
        ?>" />
                </div>
            </div>

            <div class="form-group">
                <label class="col-lg-2 control-label" for="password"><?php echo $PMF_LANG['ad_adus_password'];
        ?></label>
                <div class="col-lg-3">
                    <input type="password" name="user_password" id="password" required tabindex="4" class="form-control"
                           value="<?php echo(isset($userPassword) ? $userPassword : '');
        ?>" />
                </div>
            </div>

             <div class="form-group">
                 <label class="col-lg-2 control-label" for="password_confirm"><?php echo $PMF_LANG['ad_passwd_con'];
        ?></label>
                 <div class="col-lg-3">
                    <input type="password" name="user_password_confirm" id="password_confirm" required class="form-control"
                           tabindex="5" value="<?php echo(isset($userPasswordConfirm) ? $userPasswordConfirm : '');
        ?>" />
                 </div>
            </div>

            <div class="form-group">
                <div class="col-lg-offset-2 col-lg-10">
                    <button class="btn btn-success" type="submit">
                        <?php echo $PMF_LANG['ad_gen_save'];
        ?>
                    </button>
                    <a class="btn btn-info" href="?action=user">
                        <?php echo $PMF_LANG['ad_gen_cancel'];
        ?>
                    </a>
                </div>
            </div>
        </form>
</div> <!-- end #user_create -->
<?php

    }

    // show list of users
    if ($userAction == 'list') {
        ?>
        <header class="row">
            <div class="col-lg-12">
                <h2 class="page-header">
                    <i aria-hidden="true" class="fa fa-users fa-fw"></i> <?php echo $PMF_LANG['ad_user'] ?>
                    <div class="pull-right">
                        <a class="btn btn-success" href="?action=user&amp;user_action=add">
                            <i aria-hidden="true" class="fa fa-plus"></i> <?php echo $PMF_LANG['ad_user_add'] ?>
                        </a>
                        <?php if ($user->perm->checkRight($user->getUserId(), 'edituser')): ?>
                        <a class="btn btn-info" href="?action=user&amp;user_action=listallusers">
                            <i aria-hidden="true" class="fa fa-list"></i> <?php echo $PMF_LANG['list_all_users'] ?>
                        </a>
                        <?php endif ?>
                    </div>
                </h2>
            </div>
        </header>

        <script>
        /* <![CDATA[ */

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
                    '<div class="form-group">' +
                        '<label class="col-lg-3 control-label"><?php echo $PMF_LANG['ad_user_realname'] ?></label>' +
                        '<div class="col-lg-9">' +
                            '<input type="text" name="display_name" value="' + data.display_name + '" class="form-control" required>' +
                        '</div>' +
                    '</div>' +
                    '<div class="form-group">' +
                        '<label class="col-lg-3 control-label"><?php echo $PMF_LANG['ad_entry_email'] ?></label>' +
                        '<div class="col-lg-9">' +
                            '<input type="email" name="email" value="' + data.email + '" class="form-control" required>' +
                        '</div>' +
                    '</div>' +
                    '<div class="form-group">' +
                        '<div class="col-lg-9 col-lg-offset-3">' +
                            '<a class="btn btn-danger pmf-admin-override-password" data-toggle="modal" ' +
                            '   href="#pmf-modal-user-password-override">Override user\'s password</a>' +
                        '</div>' +
                    '</div>' +
                    '<input type="hidden" name="last_modified" value="' + data.last_modified + '">'
                );
            });
        }
        /* ]]> */
        </script>
        <div id="user_message"><?php echo $message;
        ?></div>

        <div class="row">
            <div class="col-lg-4">
                <form name="user_select" id="user_select" action="?action=user&amp;user_action=delete_confirm"
                      class="form-horizontal" method="post" role="form">
                    <input type="hidden" id="user_list_select" name="user_list_select" value="">
                    <div class="panel panel-default">
                        <div class="panel-heading">
                            <i aria-hidden="true" class="fa fa-user"></i> <?php echo $PMF_LANG['msgSearch'] ?>
                        </div>
                        <div class="panel-body">
                            <div class="input-group">
                                <span class="input-group-addon"><i aria-hidden="true" class="fa fa-user"></i></span>
                                <input type="text" id="user_list_autocomplete" name="user_list_search"
                                       class="form-control pmf-user-autocomplete" autocomplete="off"
                                       placeholder="<?php echo $PMF_LANG['ad_auth_user'] ?>">
                                <span class="input-group-btn">
                                    <button class="btn btn-primary" type="submit">
                                        <i aria-hidden="true" class="fa fa-trash"></i>
                                    </button>
                                </span>
                            </div>
                        </div>
                    </div>
                </form>

                <div class="panel panel-default">
                    <div class="panel-heading" id="user_data_legend">
                        <i aria-hidden="true" class="fa fa-user"></i> <?php echo $PMF_LANG['ad_user_profou'] ?>
                    </div>
                    <form action="?action=user&amp;user_action=update_data" method="post" accept-charset="utf-8"
                          class="form-horizontal">
                        <div class="panel-body">
                            <input id="update_user_id" type="hidden" name="user_id" value="0">
                            <input type="hidden" name="csrf" value="<?php echo $currentUser->getCsrfTokenFromSession(); ?>">
                            <div class="form-group">
                                <label for="user_status_select" class="col-lg-3 control-label">
                                    <?php echo $PMF_LANG['ad_user_status'] ?>
                                </label>
                                <div class="col-lg-9">
                                    <select id="user_status_select" class="form-control" name="user_status">
                                        <option value="active"><?php echo $PMF_LANG['ad_user_active'] ?></option>
                                        <option value="blocked"><?php echo $PMF_LANG['ad_user_blocked'] ?></option>
                                        <option value="protected"><?php echo $PMF_LANG['ad_user_protected'] ?></option>
                                    </select>
                                </div>
                            </div>
                            <div id="user_data_table"></div>
                        </div>
                        <div class="panel-footer">
                            <div class="panel-button text-right">
                                <button class="btn btn-success" type="submit">
                                    <i aria-hidden="true" class="fa fa-check"></i> <?php echo $PMF_LANG['ad_gen_save'] ?>
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
            <div class="col-lg-8" id="userRights">
                <form id="rightsForm" action="?action=user&amp;user_action=update_rights" method="post" accept-charset="utf-8">
                    <input type="hidden" name="csrf" value="<?php echo $currentUser->getCsrfTokenFromSession() ?>">
                    <input type="hidden" name="user_id" id="rights_user_id" value="0">

                    <div class="panel panel-default">
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
                                <div class="col-xs-6 form-group">
                                    <div class="checkbox">
                                        <label class="checkbox-inline">
                                            <input id="user_right_<?php echo $right['right_id'] ?>" type="checkbox"
                                                   name="user_rights[]" value="<?php echo $right['right_id'] ?>"
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
                            <button class="btn btn-primary" type="submit">
                                <?php echo $PMF_LANG['ad_gen_save'] ?>
                            </button>
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
                        <h3><?php echo $PMF_LANG['ad_menu_passwd'] ?></h3>
                    </div>
                    <div class="modal-body">
                        <form class="form-horizontal" action="#" method="post" accept-charset="utf-8">
                            <input type="hidden" name="csrf" value="<?php echo $currentUser->getCsrfTokenFromSession() ?>">
                            <input type="hidden" name="user_id" id="modal_user_id" value="<?php echo $userId ?>">

                            <div class="form-group">
                                <label class="col-lg-3 control-label" for="npass">
                                    <?php echo $PMF_LANG['ad_passwd_new'] ?>
                                </label>
                                <div class="col-lg-9">
                                    <input type="password" name="npass" id="npass" class="form-control" required>
                                </div>
                            </div>

                            <div class="form-group">
                                <label class="col-lg-3 control-label" for="bpass">
                                    <?php echo $PMF_LANG['ad_passwd_con'];
        ?>
                                </label>
                                <div class="col-lg-9">
                                    <input type="password" name="bpass" id="bpass" class="form-control" required>
                                </div>
                            </div>

                        </form>
                    </div>
                    <div class="modal-footer">
                        <button class="btn btn-primary pmf-user-password-override-action">
                            Override password
                        </button>
                    </div>
                </div>
            </div>
        </div>
<?php

    }

    // show list of all users
    if ($userAction == 'listallusers' && $user->perm->checkRight($user->getUserId(), 'edituser')) {
        $allUsers = $user->getAllUsers();
        $numUsers = count($allUsers);
        $page = PMF_Filter::filterInput(INPUT_GET, 'page', FILTER_VALIDATE_INT, 0);
        $perPage = 10;
        $numPages = ceil($numUsers / $perPage);
        $lastPage = $page * $perPage;
        $firstPage = $lastPage - $perPage;

        $baseUrl = sprintf(
            '%s?action=user&amp;user_action=listallusers&amp;page=%d',
            PMF_Link::getSystemRelativeUri(),
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
        $pagination = new PMF_Pagination($faqConfig, $options);
        ?>
        <header class="row">
            <div class="col-lg-12">
                <h2 class="page-header">
                    <i aria-hidden="true" class="fa fa-users"></i> <?php echo $PMF_LANG['ad_user'];
        ?>
                    <div class="pull-right">
                        <a class="btn btn-success" href="?action=user&amp;user_action=add">
                            <i aria-hidden="true" class="fa fa-plus"></i> <?php echo $PMF_LANG['ad_user_add'];
        ?>
                        </a>
                    </div>
                </h2>
            </div>
        </header>
        <div id="user_message"><?php echo $message;
        ?></div>
        <table class="table table-striped">
        <thead>
            <tr>
                <th><?php echo $PMF_LANG['ad_entry_id'] ?></th>
                <th><?php echo $PMF_LANG['ad_user_status'] ?></th>
                <th><?php echo $PMF_LANG['msgNewContentName'] ?></th>
                <th><?php echo $PMF_LANG['ad_auth_user'] ?></th>
                <th><?php echo $PMF_LANG['msgNewContentMail'] ?></th>
                <th colspan="3">&nbsp;</th>
            </tr>
        </thead>
        <?php if ($perPage < $numUsers): ?>
        <tfoot>
            <tr>
                <td colspan="8"><?php echo $pagination->render();
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
            <tr class="row_user_id_<?php echo $user->getUserId() ?>">
                <td><?php echo $user->getUserId() ?></td>
                <td><i class="<?php
                switch ($user->getStatus()) {
                    case 'active':
                        echo 'fa fa-check';
                        break;
                    case 'blocked':
                        echo 'fa fa-lock';
                        break;
                    case 'protected':
                        echo 'fa fa-thumb-tack';
                        break;
                }
            ?> icon_user_id_<?php echo $user->getUserId() ?>"></i></td>
                <td><?php echo $user->getUserData('display_name') ?></td>
                <td><?php echo $user->getLogin() ?></td>
                <td>
                    <a href="mailto:<?php echo $user->getUserData('email') ?>">
                        <?php echo $user->getUserData('email') ?>
                    </a>
                </td>
                <td>
                    <a href="?action=user&amp;user_id=<?php echo $user->getUserData('user_id')?>" class="btn btn-info">
                        <?php echo $PMF_LANG['ad_user_edit'] ?>
                    </a>
                </td>
                <td>
                    <?php if ($user->getStatus() === 'blocked'): ?>
                        <a onclick="activateUser(this); return false;"
                           href="javascript:;" class="btn btn-success btn_user_id_<?php echo $user->getUserData('user_id') ?>"
                           data-csrf-token="<?php echo $currentUser->getCsrfTokenFromSession() ?>"
                           data-user-id="<?php echo $user->getUserData('user_id') ?>">
                            <?php echo $PMF_LANG['ad_news_set_active'] ?>
                        </a>
                    <?php endif;
            ?>
                </td>
                <td>
                    <?php if ($user->getStatus() !== 'protected'): ?>
                    <a href="javascript:;" onclick="deleteUser(this); return false;" class="btn btn-danger"
                       data-csrf-token="<?php echo $currentUser->getCsrfTokenFromSession() ?>"
                       data-user-id="<?php echo $user->getUserData('user_id') ?>">
                        <?php print $PMF_LANG['ad_user_delete'] ?>
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
            if (confirm('<?php echo $PMF_LANG['ad_user_del_3'] ?>')) {
                var csrf   = $(identifier).data('csrf-token');
                var userId = $(identifier).data('user-id');

                $.getJSON("index.php?action=ajax&ajax=user&ajaxaction=delete_user&user_id=" + userId + "&csrf=" + csrf,
                    function(response) {
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
            if (confirm('<?php echo $PMF_LANG['ad_user_del_3'] ?>')) {
                var csrf   = $(identifier).data('csrf-token');
                var userId = $(identifier).data('user-id');

                $.getJSON("index.php?action=ajax&ajax=user&ajaxaction=activate_user&user_id=" + userId + "&csrf=" + csrf,
                    function() {
                        var icon = $('.icon_user_id_' + userId);
                        icon.toggleClass('fa-lock fa-check');
                        $('.btn_user_id_' + userId).remove();
                    });
            }
        }

        </script>
<?php 
    }
    if (isset($_GET['user_id'])) {
        $userId = PMF_Filter::filterInput(INPUT_GET, 'user_id', FILTER_VALIDATE_INT, 0);
        echo '        <script>updateUser('.$userId.');</script>';
    }
} else {
    echo $PMF_LANG['err_NotAuth'];
}
