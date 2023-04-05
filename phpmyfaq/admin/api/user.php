<?php

/**
 * Private phpMyFAQ Admin API: handling of Ajax user calls.
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at https://mozilla.org/MPL/2.0/.
 *
 * @package   phpMyFAQ
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2009-2023 phpMyFAQ Team
 * @license   https://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2009-04-04
 */

use phpMyFAQ\Auth;
use phpMyFAQ\Category;
use phpMyFAQ\Component\Alert;
use phpMyFAQ\Core\Exception;
use phpMyFAQ\Filter;
use phpMyFAQ\Helper\HttpHelper;
use phpMyFAQ\Helper\MailHelper;
use phpMyFAQ\Permission;
use phpMyFAQ\Session\Token;
use phpMyFAQ\Strings;
use phpMyFAQ\Translation;
use phpMyFAQ\User;

if (!defined('IS_VALID_PHPMYFAQ')) {
    http_response_code(400);
    exit();
}

$ajaxAction = Filter::filterInput(INPUT_GET, 'ajaxaction', FILTER_SANITIZE_SPECIAL_CHARS);
$userId = Filter::filterInput(INPUT_GET, 'user_id', FILTER_VALIDATE_INT);
$userSearch = Filter::filterInput(INPUT_GET, 'q', FILTER_SANITIZE_SPECIAL_CHARS);
$csrfToken = Filter::filterInput(INPUT_GET, 'csrf', FILTER_SANITIZE_SPECIAL_CHARS);

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
                $users = new stdClass();
                $users->label = $singleUser['login'];
                $users->value = (int)$singleUser['user_id'];
                $allUsers[] = $users;
            }
            $http->sendJsonWithHeaders($allUsers);
            break;

        case 'get_user_data':
            $user->getUserById($userId, true);
            $userdata = $user->userdata->get('*');
            if (is_array($userdata)) {
                $userdata['status'] = $user->getStatus();
                $userdata['login'] = Strings::htmlentities($user->getLogin());
                $userdata['is_superadmin'] = $user->isSuperAdmin();
            } else {
                $userdata = [];
            }
            $http->sendJsonWithHeaders($userdata);
            break;

        case 'get_all_user_data':
            $allUsers = $user->getAllUsers(false);
            $userData = [];
            foreach ($allUsers as $userId) {
                $user->getUserById($userId, true);
                $userObject = new stdClass();
                $userObject->id = $user->getUserId();
                $userObject->status = $user->getStatus();
                $userObject->isSuperAdmin = $user->isSuperAdmin();
                $userObject->isVisible = $user->getUserData('is_visible');
                $userObject->displayName = $user->getUserData('display_name');
                $userObject->userName = $user->getLogin();
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
            $postData = json_decode(file_get_contents('php://input', true));

            if (!Token::getInstance()->verifyToken('user', $postData->csrfToken)) {
                $http->setStatus(400);
                $http->sendJsonWithHeaders(['error' => Translation::get('err_NotAuth')]);
                exit(1);
            }

            $userId = Filter::filterVar($postData->userId, FILTER_VALIDATE_INT);

            $user->getUserById($userId, true);
            try {
                if ($user->activateUser()) {
                    $http->setStatus(200);
                    $http->sendJsonWithHeaders(['success' => $user->getStatus()]);
                } else {
                    $http->setStatus(400);
                    $http->sendJsonWithHeaders(['error' => $user->getStatus()]);
                }
            } catch (Exception $e) {
                $http->setStatus(400);
                $http->sendJsonWithHeaders(['error' => $e->getMessage()]);
            }
            break;

        case 'add_user':
            if (!Token::getInstance()->verifyToken('add-user', $csrfToken)) {
                $http->setStatus(401);
                $http->sendJsonWithHeaders(['error' => Translation::get('err_NotAuth')]);
                exit(1);
            }

            $errorMessage = [];
            $successMessage = '';

            $postData = json_decode(file_get_contents('php://input'), true);

            $userName = Filter::filterVar($postData['userName'], FILTER_SANITIZE_SPECIAL_CHARS);
            $userRealName = Filter::filterVar($postData['realName'], FILTER_SANITIZE_SPECIAL_CHARS);
            $userEmail = Filter::filterVar($postData['email'], FILTER_VALIDATE_EMAIL);
            $automaticPassword = Filter::filterVar($postData['automaticPassword'], FILTER_VALIDATE_BOOLEAN);
            $userPassword = Filter::filterVar($postData['password'], FILTER_SANITIZE_SPECIAL_CHARS);
            $userPasswordConfirm = Filter::filterVar($postData['passwordConfirm'], FILTER_SANITIZE_SPECIAL_CHARS);
            $userIsSuperAdmin = Filter::filterVar($postData['isSuperAdmin'], FILTER_VALIDATE_BOOLEAN);

            $newUser = new User($faqConfig);

            if (!$newUser->isValidLogin($userName)) {
                $errorMessage[] = Translation::get('ad_user_error_loginInvalid');
            }
            if ($newUser->getUserByLogin($userName)) {
                $errorMessage[] = Translation::get('ad_adus_exerr');
            }
            if ($userRealName === '') {
                $errorMessage[] = Translation::get('ad_user_error_noRealName');
            }
            if (is_null($userEmail)) {
                $errorMessage[] = Translation::get('ad_user_error_noEmail');
            }
            if (!$automaticPassword) {
                if (strlen($userPassword) <= 7 || strlen($userPasswordConfirm) <= 7) {
                    $errorMessage[] = Translation::get('ad_passwd_fail');
                }
            } else {
                $userPassword = $newUser->createPassword(8, false);
            }

            if (count($errorMessage) === 0) {
                if (!$newUser->createUser($userName, $userPassword)) {
                    $errorMessage[] = $newUser->error();
                } else {
                    $newUser->userdata->set(['display_name', 'email', 'is_visible'], [$userRealName, $userEmail, 0]);
                    $newUser->setStatus('active');
                    $newUser->setSuperAdmin((bool)$userIsSuperAdmin);
                    $mailHelper = new MailHelper($faqConfig);
                    try {
                        $mailHelper->sendMailToNewUser($newUser, $userPassword);
                    } catch (Exception $e) {
                        // @todo catch exception
                    }
                    $successMessage = [
                        'success' => Translation::get('ad_adus_suc'),
                        'id' => $newUser->getUserId(),
                        'status' => $newUser->getStatus(),
                        'isSuperAdmin' => (bool)$userIsSuperAdmin,
                        'isVisible' => (bool) $newUser->userdata->get('is_visible'),
                        'realName' => $userRealName,
                        'userName' => $userName,
                        'email' => $userEmail,
                        'editTranslationString' => Translation::get('ad_user_edit')
                    ];
                }

                $http->setStatus(200);
                $http->sendJsonWithHeaders($successMessage);
                exit(1);
            }

            $http->setStatus(400);
            $http->sendJsonWithHeaders($errorMessage);
            break;

        case 'delete_user':
            $deleteData = json_decode(file_get_contents('php://input', true));

            if (!Token::getInstance()->verifyToken('delete-user', $deleteData->csrfToken)) {
                $http->setStatus(401);
                $http->sendJsonWithHeaders(['error' => Translation::get('err_NotAuth')]);
                exit(1);
            }

            $userId = Filter::filterVar($deleteData->userId, FILTER_VALIDATE_INT);

            $user->getUserById($userId, true);
            if ($user->getStatus() == 'protected' || $userId == 1) {
                $message = '<p class="alert alert-error">' . Translation::get('ad_user_error_protectedAccount') . '</p>';
            } else {
                if (!$user->deleteUser()) {
                    $message = Translation::get('ad_user_error_delete');
                } else {
                    $category = new Category($faqConfig, [], false);
                    $category->moveOwnership((int) $userId, 1);

                    // Remove the user from groups
                    if ('basic' !== $faqConfig->get('security.permLevel')) {
                        $permissions = Permission::selectPerm('medium', $faqConfig);
                        $permissions->removeFromAllGroups($userId);
                    }

                    $message = Alert::success('ad_user_deleted');
                }
            }
            $http->setStatus(200);
            $http->sendJsonWithHeaders($message);
            break;

        case 'overwrite_password':
            $json = file_get_contents('php://input', true);
            $postData = json_decode($json);

            $userId = Filter::filterVar($postData->userId, FILTER_VALIDATE_INT);
            $newPassword = Filter::filterVar($postData->newPassword, FILTER_SANITIZE_SPECIAL_CHARS);
            $retypedPassword = Filter::filterVar($postData->passwordRepeat, FILTER_SANITIZE_SPECIAL_CHARS);

            var_dump($csrfToken);

            if (!Token::getInstance()->verifyToken('add-user', $csrfToken)) {
                $http->setStatus(400);
                $http->sendJsonWithHeaders(['error' => Translation::get('err_NotAuth')]);
                exit(1);
            }

            if (strlen($newPassword) <= 7 || strlen($retypedPassword) <= 7) {
                $http->setStatus(400);
                $http->sendJsonWithHeaders(['error' => Translation::get('ad_passwd_fail')]);
                exit(1);
            }

            $user->getUserById($userId, true);
            $auth = new Auth($faqConfig);
            $authSource = $auth->selectAuth($user->getAuthSource('name'));
            $authSource->selectEncType($user->getAuthData('encType'));

            if ($newPassword === $retypedPassword) {
                if (!$user->changePassword($newPassword)) {
                    $http->setStatus(400);
                    $http->sendJsonWithHeaders(['error' => Translation::get('ad_passwd_fail')]);
                }
                $http->setStatus(200);
                $http->sendJsonWithHeaders(['success' => Translation::get('ad_passwdsuc')]);
            } else {
                $http->setStatus(400);
                $http->sendJsonWithHeaders(['error' => Translation::get('ad_passwd_fail')]);
            }

            break;
    }
}
