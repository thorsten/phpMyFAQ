<?php

/**
 * AJAX: handling of Ajax user calls.
 *
 * PHP Version 5.6
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at http://mozilla.org/MPL/2.0/.
 *
 * @category  phpMyFAQ
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2009-2018 phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2009-04-04
 */

use phpMyFAQ\Auth;
use phpMyFAQ\Category;
use phpMyFAQ\Filter;
use phpMyFAQ\Helper\HttpHelper;
use phpMyFAQ\Permission;
use phpMyFAQ\User;

if (!defined('IS_VALID_PHPMYFAQ')) {
    $protocol = 'http';
    if (isset($_SERVER['HTTPS']) && strtoupper($_SERVER['HTTPS']) === 'ON') {
        $protocol = 'https';
    }
    header('Location: '.$protocol.'://'.$_SERVER['HTTP_HOST'].dirname($_SERVER['SCRIPT_NAME']));
    exit();
}

$ajaxAction = Filter::filterInput(INPUT_GET, 'ajaxaction', FILTER_SANITIZE_STRING);
$userId = Filter::filterInput(INPUT_GET, 'user_id', FILTER_VALIDATE_INT);
$userSearch = Filter::filterInput(INPUT_GET, 'q', FILTER_SANITIZE_STRING);
$csrfToken = Filter::filterInput(INPUT_GET, 'csrf', FILTER_SANITIZE_STRING);

// Send headers
$http = new HttpHelper();
$http->setContentType('application/json');
$http->addHeader();

if ($user->perm->checkRight($user->getUserId(), 'add_user') ||
    $user->perm->checkRight($user->getUserId(), 'edit_user') ||
    $user->perm->checkRight($user->getUserId(), 'deluser')) {
    $user = new User($faqConfig);

    switch ($ajaxAction) {

        case 'get_user_list':
            $allUsers = [];
            foreach ($user->searchUsers($userSearch) as $singleUser) {
                $users = new \stdClass();
                $users->user_id = (int)$singleUser['user_id'];
                $users->name = $singleUser['login'];
                $allUsers['results'][] = $users;
            }
            $http->sendJsonWithHeaders($allUsers);
            break;

        case 'get_user_data':
            $user->getUserById($userId, true);
            $userdata = [];
            $userdata = $user->userdata->get('*');
            $userdata['status'] = $user->getStatus();
            $userdata['login'] = $user->getLogin();
            $userdata['is_superadmin'] = $user->isSuperAdmin();
            $http->sendJsonWithHeaders($userdata);
            break;

        case 'get_user_rights':
            $user->getUserById($userId, true);
            $http->sendJsonWithHeaders($user->perm->getUserRights($userId));
            break;

        case 'activate_user':
            if (!isset($_SESSION['phpmyfaq_csrf_token']) || $_SESSION['phpmyfaq_csrf_token'] !== $csrfToken) {
                $http->sendJsonWithHeaders(array('error' => $PMF_LANG['err_NotAuth']));
                exit(1);
            }

            $user->getUserById($userId, true);
            $user->setStatus('active');
            $user->activateUser();
            $http->sendJsonWithHeaders($user->getStatus());
            break;

        case 'delete_user':
            if (!isset($_SESSION['phpmyfaq_csrf_token']) || $_SESSION['phpmyfaq_csrf_token'] !== $csrfToken) {
                $http->sendJsonWithHeaders(array('error' => $PMF_LANG['err_NotAuth']));
                exit(1);
            }

            $user->getUserById($userId, true);
            if ($user->getStatus() == 'protected' || $userId == 1) {
                $message = '<p class="error">'.$PMF_LANG['ad_user_error_protectedAccount'].'</p>';
            } else {
                if (!$user->deleteUser()) {
                    $message = $PMF_LANG['ad_user_error_delete'];
                } else {
                    $category = new Category($faqConfig, [], false);
                    $category->moveOwnership($userId, 1);

                    // Remove the user from groups
                    if ('medium' == $faqConfig->get('security.permLevel')) {
                        $permissions = Permission::selectPerm('medium', $faqConfig);
                        $permissions->removeFromAllGroups($userId);
                    }

                    $message = '<p class="success">'.$PMF_LANG['ad_user_deleted'].'</p>';
                }
            }
            $http->sendJsonWithHeaders($message);
            break;

        case 'overwrite_password':
            $userId = Filter::filterInput(INPUT_POST, 'user_id', FILTER_VALIDATE_INT);
            $csrfToken = Filter::filterInput(INPUT_POST, 'csrf', FILTER_SANITIZE_STRING);
            $newPassword = Filter::filterInput(INPUT_POST, 'npass', FILTER_SANITIZE_STRING);
            $retypedPassword = Filter::filterInput(INPUT_POST, 'bpass', FILTER_SANITIZE_STRING);

            if (!isset($_SESSION['phpmyfaq_csrf_token']) || $_SESSION['phpmyfaq_csrf_token'] !== $csrfToken) {
                $http->sendJsonWithHeaders(array('error' => $PMF_LANG['err_NotAuth']));
                exit(1);
            }

            $user->getUserById($userId, true);
            $auth = new Auth($faqConfig);
            $authSource = $auth->selectAuth($user->getAuthSource('name'));
            $authSource->selectEncType($user->getAuthData('encType'));

            if ($newPassword === $retypedPassword) {
                if (!$user->changePassword($newPassword)) {
                    echo $http->sendJsonWithHeaders(['error' => $PMF_LANG['ad_passwd_fail']]);
                }
                $http->sendJsonWithHeaders(['success' => $PMF_LANG['ad_passwdsuc']]);
            } else {
                $http->sendJsonWithHeaders(['error' => $PMF_LANG['ad_passwd_fail']]);
            }

            break;
    }
}
