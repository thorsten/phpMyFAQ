<?php

/**
 * AJAX: handling of Ajax user calls.
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at http://mozilla.org/MPL/2.0/.
 *
 * @package   phpMyFAQ
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2009-2022 phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2009-04-04
 */

use phpMyFAQ\Auth;
use phpMyFAQ\Category;
use phpMyFAQ\Core\Exception;
use phpMyFAQ\Filter;
use phpMyFAQ\Helper\HttpHelper;
use phpMyFAQ\Helper\MailHelper;
use phpMyFAQ\Permission;
use phpMyFAQ\Report;
use phpMyFAQ\Strings;
use phpMyFAQ\User;

if (!defined('IS_VALID_PHPMYFAQ')) {
    http_response_code(400);
    exit();
}

$ajaxAction = Filter::filterInput(INPUT_GET, 'ajaxaction', FILTER_UNSAFE_RAW);
$userId = Filter::filterInput(INPUT_GET, 'user_id', FILTER_VALIDATE_INT);
$userSearch = Filter::filterInput(INPUT_GET, 'q', FILTER_UNSAFE_RAW);
$csrfToken = Filter::filterInput(INPUT_GET, 'csrf', FILTER_UNSAFE_RAW);

// Send headers
$http = new HttpHelper();
$http->setContentType('application/json');
$http->addHeader();

if (
    $user->perm->hasPermission($user->getUserId(), 'add_user') ||
    $user->perm->hasPermission($user->getUserId(), 'edit_user') ||
    $user->perm->hasPermission($user->getUserId(), 'delete_user')
) {
    $user = new User($faqConfig);

    switch ($ajaxAction) {
        case 'get_user_list':
            $allUsers = [];
            foreach ($user->searchUsers($userSearch) as $singleUser) {
                $users = new \stdClass();
                $users->user_id = (int)$singleUser['user_id'];
                $users->name = $singleUser['login'];
                $allUsers[] = $users;
            }
            $http->sendJsonWithHeaders($allUsers);
            break;

        case 'get_user_data':
            $user->getUserById($userId, true);
            $userdata = [];
            $userdata = $user->userdata->get('*');
            $userdata['status'] = $user->getStatus();
            $userdata['login'] = Strings::htmlentities($user->getLogin());
            $userdata['is_superadmin'] = $user->isSuperAdmin();
            $http->sendJsonWithHeaders($userdata);
            break;

        case 'get_all_user_data':
            $allUsers = $user->getAllUsers(false);
            $userData = [];
            foreach ($allUsers as $userId) {
                $user->getUserById($userId, true);
                $userObject = new \stdClass();
                $userObject->id = $user->getUserId();
                $userObject->status = $user->getStatus();
                $userObject->isSuperAdmin = $user->isSuperAdmin();
                $userObject->isVisible = $user->getUserData('is_visible');
                $userObject->displayName = Report::sanitize($user->getUserData('display_name'));
                $userObject->userName = Report::sanitize($user->getLogin());
                $userObject->email = $user->getUserData('email');
                $userData[] = $userObject;
            }
            $http->setStatus(200);
            $http->sendJsonWithHeaders($userData);
            break;

        case 'get_user_rights':
            $user->getUserById($userId, true);
            $http->sendJsonWithHeaders($user->perm->getUserRights($userId));
            break;

        case 'activate_user':
            if (!isset($_SESSION['phpmyfaq_csrf_token']) || $_SESSION['phpmyfaq_csrf_token'] !== $csrfToken) {
                $http->setStatus(400);
                $http->sendJsonWithHeaders(['error' => $PMF_LANG['err_NotAuth']]);
                exit(1);
            }

            $user->getUserById($userId, true);
            $user->activateUser();
            $http->sendJsonWithHeaders($user->getStatus());
            break;

        case 'add_user':
            if (!isset($_SESSION['phpmyfaq_csrf_token']) || $_SESSION['phpmyfaq_csrf_token'] !== $csrfToken) {
                $http->setStatus(400);
                $http->sendJsonWithHeaders(['error' => $PMF_LANG['err_NotAuth']]);
                exit(1);
            }

            $errorMessage = [];
            $successMessage = '';

            $postData = json_decode(file_get_contents('php://input'), true);

            $userName = Filter::filterVar($postData['userName'], FILTER_UNSAFE_RAW);
            $userRealName = Filter::filterVar($postData['realName'], FILTER_UNSAFE_RAW);
            $userEmail = Filter::filterVar($postData['email'], FILTER_VALIDATE_EMAIL);
            $automaticPassword = Filter::filterVar($postData['automaticPassword'], FILTER_VALIDATE_BOOLEAN);
            $userPassword = Filter::filterVar($postData['password'], FILTER_UNSAFE_RAW);
            $userPasswordConfirm = Filter::filterVar($postData['passwordConfirm'], FILTER_UNSAFE_RAW);
            $userIsSuperAdmin = Filter::filterVar($postData['isSuperAdmin'] ?? false, FILTER_VALIDATE_BOOLEAN);

            $newUser = new User($faqConfig);

            if (!$newUser->isValidLogin($userName)) {
                $errorMessage[] = $PMF_LANG['ad_user_error_loginInvalid'];
            }
            if ($newUser->getUserByLogin($userName)) {
                $errorMessage[] = $PMF_LANG['ad_adus_exerr'];
            }
            if ($userRealName === '') {
                $errorMessage[] = $PMF_LANG['ad_user_error_noRealName'];
            }
            if (is_null($userEmail)) {
                $errorMessage[] = $PMF_LANG['ad_user_error_noEmail'];
            }
            if (!$automaticPassword) {
                if (strlen($userPassword) <= 7 || strlen($userPasswordConfirm) <= 7) {
                    $errorMessage[] = $PMF_LANG['ad_passwd_fail'];
                }
            } else {
                $userPassword = ''; // set password to empty, so a new one is created
            }

            if (count($errorMessage) === 0) {
                if (!$newUser->createUser($userName, $userPassword)) {
                    $errorMessage[] = $newUser->error();
                } else {
                    $newUser->userdata->set(['display_name', 'email', 'is_visible'], [$userRealName, $userEmail, 0]);
                    $newUser->setStatus('active');
                    $newUser->setSuperAdmin($userIsSuperAdmin);
                    $mailHelper = new MailHelper($faqConfig);
                    try {
                        $mailHelper->sendMailToNewUser($newUser, $userPassword);
                    } catch (Exception $e) {
                        // @todo catch exception
                    }
                    $successMessage = [ 'data' => $PMF_LANG['ad_adus_suc'] ];
                }

                $http->setStatus(201);
                $http->sendJsonWithHeaders($successMessage);
                exit(1);
            }

            $http->setStatus(400);
            $http->sendJsonWithHeaders($errorMessage);
            break;

        case 'delete_user':
            if (!isset($_SESSION['phpmyfaq_csrf_token']) || $_SESSION['phpmyfaq_csrf_token'] !== $csrfToken) {
                $http->setStatus(400);
                $http->sendJsonWithHeaders(['error' => $PMF_LANG['err_NotAuth']]);
                exit(1);
            }

            $user->getUserById($userId, true);
            if ($user->getStatus() == 'protected' || $userId == 1) {
                $message = '<p class="alert alert-error">' . $PMF_LANG['ad_user_error_protectedAccount'] . '</p>';
            } else {
                if (!$user->deleteUser()) {
                    $message = $PMF_LANG['ad_user_error_delete'];
                } else {
                    $category = new Category($faqConfig, [], false);
                    $category->moveOwnership((int) $userId, 1);

                    // Remove the user from groups
                    if ('basic' !== $faqConfig->get('security.permLevel')) {
                        $permissions = Permission::selectPerm('medium', $faqConfig);
                        $permissions->removeFromAllGroups($userId);
                    }

                    $message = '<p class="alert alert-success">' . $PMF_LANG['ad_user_deleted'] . '</p>';
                }
            }
            $http->sendJsonWithHeaders($message);
            break;

        case 'overwrite_password':
            $userId = Filter::filterInput(INPUT_POST, 'user_id', FILTER_VALIDATE_INT);
            $csrfToken = Filter::filterInput(INPUT_POST, 'csrf', FILTER_UNSAFE_RAW);
            $newPassword = Filter::filterInput(INPUT_POST, 'npass', FILTER_UNSAFE_RAW);
            $retypedPassword = Filter::filterInput(INPUT_POST, 'bpass', FILTER_UNSAFE_RAW);

            if (!isset($_SESSION['phpmyfaq_csrf_token']) || $_SESSION['phpmyfaq_csrf_token'] !== $csrfToken) {
                $http->setStatus(400);
                $http->sendJsonWithHeaders(['error' => $PMF_LANG['err_NotAuth']]);
                exit(1);
            }

            if (strlen($newPassword) <= 7 || strlen($retypedPassword) <= 7) {
                $http->setStatus(400);
                $http->sendJsonWithHeaders(['error' => $PMF_LANG['ad_passwd_fail']]);
                exit(1);
            }

            $user->getUserById($userId, true);
            $auth = new Auth($faqConfig);
            $authSource = $auth->selectAuth($user->getAuthSource('name'));
            $authSource->selectEncType($user->getAuthData('encType'));

            if ($newPassword === $retypedPassword) {
                if (!$user->changePassword($newPassword)) {
                    $http->setStatus(400);
                    $http->sendJsonWithHeaders(['error' => $PMF_LANG['ad_passwd_fail']]);
                }
                $http->sendJsonWithHeaders(['success' => $PMF_LANG['ad_passwdsuc']]);
            } else {
                $http->setStatus(400);
                $http->sendJsonWithHeaders(['error' => $PMF_LANG['ad_passwd_fail']]);
            }

            break;
    }
}
