<?php
/**
 * Displays the user management frontend
 *
 * PHP 5.2
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at http://mozilla.org/MPL/2.0/.
 *
 * @category  phpMyFAQ
 * @package   Administration
 * @author    Lars Tiedemann <php@larstiedemann.de>
 * @author    Uwe Pries <uwe.pries@digartis.de>
 * @author    Sarah Hermann <sayh@gmx.de>
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

if ($user->perm->checkRight($user->getUserId(), 'edituser') ||
    $user->perm->checkRight($user->getUserId(), 'deluser') ||
    $user->perm->checkRight($user->getUserId(), 'adduser')) {

    // set some parameters
    $selectSize        = 10;
    $defaultUserAction = 'list';
    $defaultUserStatus = 'active';

    // what shall we do?
    // actions defined by url: user_action=
    $userAction = PMF_Filter::filterInput(INPUT_GET, 'user_action', FILTER_SANITIZE_STRING, $defaultUserAction);
    // actions defined by submit button
    if (isset($_POST['user_action_deleteConfirm'])) {
        $userAction = 'delete_confirm';
    }
    if (isset($_POST['cancel'])) {
        $userAction = $defaultUserAction;
    }

    // update user rights
    if ($userAction == 'update_rights' && $user->perm->checkRight($user->getUserId(), 'edituser')) {
        $message    = '';
        $userAction = $defaultUserAction;
        $userId     = PMF_Filter::filterInput(INPUT_POST, 'user_id', FILTER_VALIDATE_INT, 0);
        $csrfOkay   = true;
        $csrfToken  = PMF_Filter::filterInput(INPUT_POST, 'csrf', FILTER_SANITIZE_STRING);
        if (!isset($_SESSION['phpmyfaq_csrf_token']) || $_SESSION['phpmyfaq_csrf_token'] !== $csrfToken) {
            $csrfOkay = false;
        }
        if ($userId === 0 && !$csrfOkay) {
            $message .= sprintf('<p class="alert alert-danger">%s</p>', $PMF_LANG['ad_user_error_noId']);
        } else {
            $user       = new PMF_User($faqConfig);
            $perm       = $user->perm;
            // @todo: Add PMF_Filter::filterInput[]
            $userRights = isset($_POST['user_rights']) ? $_POST['user_rights'] : [];
            if (!$perm->refuseAllUserRights($userId)) {
                $message .= sprintf('<p class="alert alert-danger">%s</p>', $PMF_LANG['ad_msg_mysqlerr']);
            }
            foreach ($userRights as $rightId) {
                $perm->grantUserRight($userId, $rightId);
            }
            $idUser = $user->getUserById($userId);
            $message .= sprintf('<p class="alert alert-success">%s <strong>%s</strong> %s</p>',
                $PMF_LANG['ad_msg_savedsuc_1'],
                $user->getLogin(),
                $PMF_LANG['ad_msg_savedsuc_2']);
            $message .= '<script type="text/javascript">updateUser(' . $userId . ');</script>';
            $user     = new PMF_User_CurrentUser($faqConfig);
        }
    }

    // update user data
    if ($userAction == 'update_data' && $user->perm->checkRight($user->getUserId(), 'edituser')) {
        $message    = '';
        $userAction = $defaultUserAction;
        $userId     = PMF_Filter::filterInput(INPUT_POST, 'user_id', FILTER_VALIDATE_INT, 0);
        if ($userId == 0) {
            $message .= sprintf('<p class="alert alert-danger">%s</p>', $PMF_LANG['ad_user_error_noId']);
        } else {
            $userData                  = [];
            $userData['display_name']  = PMF_Filter::filterInput(INPUT_POST, 'display_name', FILTER_SANITIZE_STRING, '');
            $userData['email']         = PMF_Filter::filterInput(INPUT_POST, 'email', FILTER_VALIDATE_EMAIL, '');
            $userData['last_modified'] = PMF_Filter::filterInput(INPUT_POST, 'last_modified', FILTER_SANITIZE_STRING, '');
            $userStatus                = PMF_Filter::filterInput(INPUT_POST, 'user_status', FILTER_SANITIZE_STRING, $defaultUserStatus);

            $user = new PMF_User($faqConfig);
            $user->getUserById($userId);

            $stats = $user->getStatus();
            // set new password an send email if user is switched to active
            if ($stats == 'blocked' && $userStatus == 'active') {
                $consonants  = array("b", "c", "d", "f", "g", "h", "j", "k", "l", "m", "n", "p", "r", "s", "t", "v", "w", "x", "y", "z");
                $vowels      = array("a", "e", "i", "o", "u");
                $newPassword = '';
                srand((double)microtime() * 1000000);
                for ($i = 1; $i <= 4; $i++) {
                    $newPassword .= $consonants[rand(0, 19)];
                    $newPassword .= $vowels[rand(0, 4)];
                }
                $user->changePassword($newPassword);

                $mail = new PMF_Mail($faqConfig);
                $mail->addTo($userData['email']);
                $mail->subject = '[%sitename%] Login name / activation';
                $mail->message = sprintf("\nName: %s\nLogin name: %s\nNew password: %s\n\n",
                    $userData['display_name'],
                    $user->getLogin(),
                    $newPassword);
                $result        = $mail->send();
                unset($mail);
            }

            if (!$user->userdata->set(array_keys($userData), array_values($userData)) or !$user->setStatus($userStatus)) {
                $message .= sprintf('<p class="alert alert-danger">%s</p>', $PMF_LANG['ad_msg_mysqlerr']);
            } else {
                $message .= sprintf('<p class="alert alert-success">%s <strong>%s</strong> %s</p>',
                    $PMF_LANG['ad_msg_savedsuc_1'],
                    $user->getLogin(),
                    $PMF_LANG['ad_msg_savedsuc_2']);
                $message .= '<script type="text/javascript">updateUser(' . $userId . ');</script>';
            }
        }
    }

    // delete user confirmation
    if ($userAction == 'delete_confirm' && $user->perm->checkRight($user->getUserId(), 'deluser')) {
        $message = '';
        $user    = new PMF_User_CurrentUser($faqConfig);
        $userId  = PMF_Filter::filterInput(INPUT_POST, 'user_list_select', FILTER_VALIDATE_INT, 0);
        if ($userId == 0) {
            $message .= sprintf('<p class="alert alert-error">%s</p>', $PMF_LANG['ad_user_error_noId']);
            $userAction = $defaultUserAction;
        } else {
            $user->getUserById($userId);
            // account is protected
            if ($user->getStatus() == 'protected' || $userId == 1) {
                $message .= sprintf('<p class="alert alert-error">%s</p>', $PMF_LANG['ad_user_error_protectedAccount']);
                $userAction = $defaultUserAction;
            } else {
                $twig->loadTemplate('user/delete_confirm.twig')
                    ->display(
                        array(
                            'PMF_LANG'  => $PMF_LANG,
                            'csrfToken' => $user->getCsrfTokenFromSession(),
                            'userId'    => $userId,
                            'userLogin' => $user->getLogin()
                        )
                    );
            }
        }
    }

    // delete user
    if ($userAction == 'delete' && $user->perm->checkRight($user->getUserId(), 'deluser')) {
        $message   = '';
        $user      = new PMF_User($faqConfig);
        $userId    = PMF_Filter::filterInput(INPUT_POST, 'user_id', FILTER_VALIDATE_INT, 0);
        $csrfOkay  = true;
        $csrfToken = PMF_Filter::filterInput(INPUT_POST, 'csrf', FILTER_SANITIZE_STRING);
        if (!isset($_SESSION['phpmyfaq_csrf_token']) || $_SESSION['phpmyfaq_csrf_token'] !== $csrfToken) {
            $csrfOkay = false;
        }
        $userAction = $defaultUserAction;
        if ($userId == 0 && !$csrfOkay) {
            $message .= sprintf('<p class="alert alert-danger">%s</p>', $PMF_LANG['ad_user_error_noId']);
        } else {
            if (!$user->getUserById($userId)) {
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
            if ($userError != "") {
                $message .= sprintf('<p class="alert alert-danger">%s</p>', $userError);
            }
        }
    }

    // save new user
    if ($userAction == 'addsave' && $user->perm->checkRight($user->getUserId(), 'adduser')) {
        $user                  = new PMF_User($faqConfig);
        $message               = '';
        $messages              = [];
        $user_name             = PMF_Filter::filterInput(INPUT_POST, 'user_name', FILTER_SANITIZE_STRING, '');
        $user_realname         = PMF_Filter::filterInput(INPUT_POST, 'user_realname', FILTER_SANITIZE_STRING, '');
        $user_password         = PMF_Filter::filterInput(INPUT_POST, 'user_password', FILTER_SANITIZE_STRING, '');
        $user_email            = PMF_Filter::filterInput(INPUT_POST, 'user_email', FILTER_VALIDATE_EMAIL);
        $user_password         = PMF_Filter::filterInput(INPUT_POST, 'user_password', FILTER_SANITIZE_STRING, '');
        $user_password_confirm = PMF_Filter::filterInput(INPUT_POST, 'user_password_confirm', FILTER_SANITIZE_STRING, '');
        $csrfOkay              = true;
        $csrfToken             = PMF_Filter::filterInput(INPUT_POST, 'csrf', FILTER_SANITIZE_STRING);
        if (!isset($_SESSION['phpmyfaq_csrf_token']) || $_SESSION['phpmyfaq_csrf_token'] !== $csrfToken) {
            $csrfOkay = false;
        }

        if ($user_password != $user_password_confirm) {
            $user_password         = '';
            $user_password_confirm = '';
            $messages[]            = $PMF_LANG['ad_user_error_passwordsDontMatch'];
        }

        // check login name
        if (!$user->isValidLogin($user_name)) {
            $user_name  = '';
            $messages[] = $PMF_LANG['ad_user_error_loginInvalid'];
        }
        if ($user->getUserByLogin($user_name)) {
            $user_name  = '';
            $messages[] = $PMF_LANG['ad_adus_exerr'];
        }
        // check realname
        if ($user_realname == '') {
            $user_realname = '';
            $messages[]    = $PMF_LANG['ad_user_error_noRealName'];
        }
        // check e-mail
        if (is_null($user_email)) {
            $user_email = '';
            $messages[] = $PMF_LANG['ad_user_error_noEmail'];
        }

        // ok, let's go
        if (count($messages) == 0 && $csrfOkay) {
            // create user account (login and password)
            if (!$user->createUser($user_name, $user_password)) {
                $messages[] = $user->error();
            } else {
                // set user data (realname, email)
                $user->userdata->set(array('display_name', 'email'), array($user_realname, $user_email));
                // set user status
                $user->setStatus($defaultUserStatus);
            }
        }
        // no errors, show list
        if (count($messages) == 0) {
            $userAction = $defaultUserAction;
            $message    = sprintf('<p class="alert alert-success">%s</p>', $PMF_LANG['ad_adus_suc']);
            // display error messages and show form again
        } else {
            $userAction = 'add';
            $message    = '<p class="alert alert-danger">';
            foreach ($messages as $err) {
                $message .= $err . '<br />';
            }
            $message .= '</p>';
        }
    }

    if (!isset($message)) {
        $message = '';
    }

    // show new user form
    if ($userAction == 'add' && $user->perm->checkRight($user->getUserId(), 'adduser')) {
        $twig->loadTemplate('user/add.twig')
            ->display(
                array(
                    'PMF_LANG'            => $PMF_LANG,
                    'csrfToken'           => $user->getCsrfTokenFromSession(),
                    'userEmail'           => isset($user_email) ? $user_email : '',
                    'userName'            => isset($user_name) ? $user_name : '',
                    'userPassword'        => isset($user_password) ? $user_password : '',
                    'userPasswordConfirm' => isset($user_password_confirm) ? $user_password_confirm : '',
                    'userRealName'        => isset($user_realname) ? $user_realname : ''
                )
            );

    }

    // show list of users
    if ($userAction == 'list') {
        $templateVars = array(
            'PMF_LANG'               => $PMF_LANG,
            'message'                => $message,
            'renderUpdateUserScript' => false,
            'rights'                 => $user->perm->getAllRightsData(),
            'showListAllUsers'       => $permission['edituser']
        );


        if (isset($_GET['user_id'])) {
            $templateVars['renderUpdateUserScript'] = true;
            $templateVars['updateUserId']           = PMF_Filter::filterInput(INPUT_GET, 'user_id', FILTER_VALIDATE_INT, 0);
        }

        $twig->loadTemplate('user/list.twig')
            ->display($templateVars);
    }

    // show list of all users
    if ($userAction == 'listallusers' && $permission$user->perm->checkRight($user->getUserId(), 'edituser')) {
        $templateVars = array(
            'PMF_LANG'          => $PMF_LANG,
            'displayPagination' => false,
            'message'           => $message,
            'users'             => array()
        );

        $allUsers  = $user->getAllUsers();
        $numUsers  = count($allUsers);
        $page      = PMF_Filter::filterInput(INPUT_GET, 'page', FILTER_VALIDATE_INT, 0);
        $perPage   = 10;
        $numPages  = ceil($numUsers / $perPage);
        $lastPage  = $page * $perPage;
        $firstPage = $lastPage - $perPage;

        $baseUrl = sprintf(
            '%s?action=user&amp;user_action=listallusers&amp;page=%d',
            PMF_Link::getSystemRelativeUri(),
            $page
        );

        if ($perPage < $numUsers) {
            // Pagination options
            $options = array(
                'baseUrl'       => $baseUrl,
                'total'         => $numUsers,
                'perPage'       => $perPage,
                'pageParamName' => 'page'
            );
            $pagination = new PMF_Pagination($faqConfig, $options);

            $templateVars['displayPagination'] = true;
            $templateVars['pagination']        = $pagination->render();
        }

        $counter = $displayedCounter = 0;
        foreach ($allUsers as $userId) {
            $user->getUserById($userId);

            if ($displayedCounter >= $perPage) {
                continue;
            }
            $counter++;
            if ($counter <= $firstPage) {
                continue;
            }
            $displayedCounter++;

            $icon = '';
            switch ($user->getStatus()) {
                case 'active':
                    $icon = 'icon-ok';
                    break;
                case 'blocked':
                    $icon = 'icon-lock';
                    break;
                case 'protected':
                    $icon = 'icon-ok-sign';
                    break;
            }

            $templateVars['users'][] = array(
                'id'               => $user->getUserId(),
                'displayName'      => $user->getUserData('display_name'),
                'editUrl'          => '?action=user&amp;user_id=' . $user->getUserData('user_id'),
                'email'            => $user->getUserData('email'),
                'icon'             => $icon,
                'loginName'        => $user->getLogin(),
                'showDeleteButton' => $user->getStatus() !== 'protected',
                'status'           => $user->getStatus()
            );
        }

        $twig->loadTemplate('user/listallusers.twig')
            ->display($templateVars);

        unset($templateVars, $allUsers, $numUsers, $page, $perPage, $numPages, $lastPage, $firstPage, $baseUrl, $options, $pagination, $counter, $displayedCounter, $icon);
    }
} else {
    require 'noperm.php';
}