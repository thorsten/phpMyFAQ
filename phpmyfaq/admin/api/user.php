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
use phpMyFAQ\Helper\MailHelper;
use phpMyFAQ\Permission;
use phpMyFAQ\Report;
use phpMyFAQ\Session\Token;
use phpMyFAQ\Strings;
use phpMyFAQ\Translation;
use phpMyFAQ\User;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;

if (!defined('IS_VALID_PHPMYFAQ')) {
    http_response_code(400);
    exit();
}

//
// Create Request & Response
//
$response = new JsonResponse();
$request = Request::createFromGlobals();

$ajaxAction = Filter::filterVar($request->query->get('ajaxaction'), FILTER_SANITIZE_SPECIAL_CHARS);
$userId = Filter::filterInput(INPUT_GET, 'user_id', FILTER_VALIDATE_INT);
$userSearch = Filter::filterInput(INPUT_GET, 'q', FILTER_SANITIZE_SPECIAL_CHARS);
$csrfToken = Filter::filterInput(INPUT_GET, 'csrf', FILTER_SANITIZE_SPECIAL_CHARS);

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
            $response->setStatusCode(Response::HTTP_OK);
            $response->setData($allUsers);
            $response->send();
            break;

        case 'get_user_data':
            $user->getUserById($userId, true);
            $userdata = $user->userdata->get('*');
            if (is_array($userdata)) {
                $userdata['status'] = $user->getStatus();
                $userdata['login'] = Strings::htmlentities($user->getLogin(), ENT_COMPAT);
                $userdata['is_superadmin'] = $user->isSuperAdmin();
                $userdata['auth_source'] = $user->getUserAuthSource();
            } else {
                $userdata = [];
            }
            $response->setStatusCode(Response::HTTP_OK);
            $response->setData($userdata);
            $response->send();
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
                $userObject->displayName = Report::sanitize($user->getUserData('display_name'));
                $userObject->userName = Report::sanitize($user->getLogin());
                $userObject->email = $user->getUserData('email');
                $userObject->authSource = $user->getUserAuthSource();
                $userData[] = $userObject;
            }
            $response->setStatusCode(Response::HTTP_OK);
            $response->setData($userData);
            $response->send();
            break;

        case 'get_user_rights':
            $user->getUserById($userId, true);
            $response->setData($user->perm->getUserRights($userId));
            $response->send();
            break;

        case 'activate_user':
            $postData = json_decode(file_get_contents('php://input', true));

            if (!Token::getInstance()->verifyToken('user', $postData->csrfToken)) {
                $response->setStatusCode(Response::HTTP_UNAUTHORIZED);
                $response->setData(['error' => Translation::get('err_NotAuth')]);
                $response->send();
                exit(1);
            }

            $userId = Filter::filterVar($postData->userId, FILTER_VALIDATE_INT);

            $user->getUserById($userId, true);
            try {
                if ($user->activateUser()) {
                    $response->setStatusCode(Response::HTTP_OK);
                    $response->setData(['success' => $user->getStatus()]);
                } else {
                    $response->setStatusCode(Response::HTTP_BAD_REQUEST);
                    $response->setData(['error' => $user->getStatus()]);
                }
            } catch (TransportExceptionInterface $e) {
                $response->setStatusCode(Response::HTTP_BAD_REQUEST);
                $response->setData(['error' => $e->getMessage()]);
            }
            $response->send();
            break;

        case 'add_user':
            if (!Token::getInstance()->verifyToken('add-user', $csrfToken)) {
                $response->setStatusCode(Response::HTTP_UNAUTHORIZED);
                $response->setData(['error' => Translation::get('err_NotAuth')]);
                $response->send();
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

                $response->setStatusCode(Response::HTTP_OK);
                $response->setData($successMessage);
                $response->send();
                exit(1);
            }

            $response->setStatusCode(Response::HTTP_BAD_REQUEST);
            $response->setData($errorMessage);
            $response->send();
            break;

        case 'delete_user':
            $deleteData = json_decode(file_get_contents('php://input', true));

            if (!Token::getInstance()->verifyToken('delete-user', $deleteData->csrfToken)) {
                $response->setStatusCode(Response::HTTP_UNAUTHORIZED);
                $response->setData(['error' => Translation::get('err_NotAuth')]);
                $response->send();
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
            $response->setStatusCode(Response::HTTP_OK);
            $response->setData($message);
            $response->send();
            break;

        case 'overwrite_password':
            $postData = json_decode(file_get_contents('php://input', true));

            $userId = Filter::filterVar($postData->userId, FILTER_VALIDATE_INT);
            $newPassword = Filter::filterVar($postData->newPassword, FILTER_SANITIZE_SPECIAL_CHARS);
            $retypedPassword = Filter::filterVar($postData->passwordRepeat, FILTER_SANITIZE_SPECIAL_CHARS);

            if (!Token::getInstance()->verifyToken('add-user', $csrfToken)) {
                $response->setStatusCode(Response::HTTP_BAD_REQUEST);
                $response->setData(['error' => Translation::get('err_NotAuth')]);
                $response->send();
                exit(1);
            }

            if (strlen($newPassword) <= 7 || strlen($retypedPassword) <= 7) {
                $response->setStatusCode(Response::HTTP_BAD_REQUEST);
                $response->setData(['error' => Translation::get('ad_passwd_fail')]);
                $response->send();
                exit(1);
            }

            $user->getUserById($userId, true);

            $auth = new Auth($faqConfig);
            $authSource = $auth->selectAuth($user->getAuthSource('name'));
            $authSource->selectEncType($user->getAuthData('encType'));

            if ($newPassword === $retypedPassword) {
                if (!$user->changePassword($newPassword)) {
                    $response->setStatusCode(Response::HTTP_BAD_REQUEST);
                    $response->setData(['error' => Translation::get('ad_passwd_fail')]);
                    $response->send();
                }
                $response->setStatusCode(Response::HTTP_OK);
                $response->setData(['success' => Translation::get('ad_passwdsuc')]);
            } else {
                $response->setStatusCode(Response::HTTP_BAD_REQUEST);
                $response->setData(['error' => Translation::get('ad_passwd_fail')]);
            }
            $response->send();
            break;
    }
}
