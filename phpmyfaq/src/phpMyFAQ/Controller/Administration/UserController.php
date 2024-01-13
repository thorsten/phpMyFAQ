<?php

/**
 * The Admin User Controller
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at https://mozilla.org/MPL/2.0/.
 *
 * @package   phpMyFAQ
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2023-2024 phpMyFAQ Team
 * @license   https://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2023-10-27
 */

namespace phpMyFAQ\Controller\Administration;

use phpMyFAQ\Administration\Report;
use phpMyFAQ\Auth;
use phpMyFAQ\Category;
use phpMyFAQ\Component\Alert;
use phpMyFAQ\Configuration;
use phpMyFAQ\Controller\AbstractController;
use phpMyFAQ\Core\Exception;
use phpMyFAQ\Filter;
use phpMyFAQ\Helper\MailHelper;
use phpMyFAQ\Permission;
use phpMyFAQ\Session\Token;
use phpMyFAQ\Strings;
use phpMyFAQ\Translation;
use phpMyFAQ\User;
use phpMyFAQ\User\CurrentUser;
use stdClass;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\Routing\Annotation\Route;

class UserController extends AbstractController
{
    #[Route('admin/api/user/users')]
    public function list(Request $request): JsonResponse
    {
        $this->userHasUserPermission();

        $response = new JsonResponse();
        $user = CurrentUser::getCurrentUser(Configuration::getConfigurationInstance());

        $filtered = Filter::filterVar($request->query->get('filter'), FILTER_SANITIZE_SPECIAL_CHARS);

        if ('' === $filtered) {
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
        } else {
            $allUsers = [];
            foreach ($user->searchUsers($filtered) as $singleUser) {
                $users = new stdClass();
                $users->label = $singleUser['login'];
                $users->value = (int)$singleUser['user_id'];
                $allUsers[] = $users;
            }
            $response->setStatusCode(Response::HTTP_OK);
            $response->setData($allUsers);
        }

        return $response;
    }

    #[Route('admin/api/user/data')]
    public function userData(Request $request): JsonResponse
    {
        $this->userHasUserPermission();

        $response = new JsonResponse();
        $user = CurrentUser::getCurrentUser(Configuration::getConfigurationInstance());

        $user->getUserById($request->get('userId'), true);
        $userdata = $user->userdata->get('*');

        if (is_array($userdata)) {
            $userdata['status'] = $user->getStatus();
            $userdata['login'] = Strings::htmlentities($user->getLogin(), ENT_COMPAT);
            $userdata['display_name'] = Strings::htmlentities($userdata['display_name'], ENT_COMPAT);
            $userdata['is_superadmin'] = $user->isSuperAdmin();
            $userdata['auth_source'] = $user->getUserAuthSource();
        } else {
            $userdata = [];
        }

        $response->setStatusCode(Response::HTTP_OK);
        $response->setData($userdata);

        return $response;
    }

    #[Route('admin/api/user/permissions')]
    public function userPermissions(Request $request): JsonResponse
    {
        $this->userHasUserPermission();

        $response = new JsonResponse();
        $user = CurrentUser::getCurrentUser(Configuration::getConfigurationInstance());

        $userId = $request->get('userId');
        $user->getUserById($userId, true);
        $response->setData($user->perm->getUserRights($userId));

        return $response;
    }

    #[Route('admin/api/user/activate')]
    public function activate(Request $request): JsonResponse
    {
        $this->userHasUserPermission();

        $response = new JsonResponse();
        $user = CurrentUser::getCurrentUser(Configuration::getConfigurationInstance());

        $data = json_decode($request->getContent());
        if (!Token::getInstance()->verifyToken('activate-user', $data->csrfToken)) {
            $response->setStatusCode(Response::HTTP_UNAUTHORIZED);
            $response->setData(['error' => Translation::get('err_NotAuth')]);
            return $response;
        }

        $userId = Filter::filterVar($data->userId, FILTER_VALIDATE_INT);

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

        return $response;
    }

    /**
     * @throws Exception
     */
    #[Route('admin/api/user/overwrite-password')]
    public function overwritePassword(Request $request): JsonResponse
    {
        $this->userHasUserPermission();

        $response = new JsonResponse();
        $configuration = Configuration::getConfigurationInstance();
        $user = CurrentUser::getCurrentUser($configuration);

        $data = json_decode($request->getContent());

        $userId = Filter::filterVar($data->userId, FILTER_VALIDATE_INT);
        $csrfToken = Filter::filterVar($data->csrf, FILTER_SANITIZE_SPECIAL_CHARS);
        $newPassword = Filter::filterVar($data->newPassword, FILTER_SANITIZE_SPECIAL_CHARS);
        $retypedPassword = Filter::filterVar($data->passwordRepeat, FILTER_SANITIZE_SPECIAL_CHARS);

        if (!Token::getInstance()->verifyToken('overwrite-password', $csrfToken)) {
            $response->setStatusCode(Response::HTTP_UNAUTHORIZED);
            $response->setData(['error' => Translation::get('err_NotAuth')]);
            return $response;
        }

        if (strlen($newPassword) <= 7 || strlen($retypedPassword) <= 7) {
            $response->setStatusCode(Response::HTTP_BAD_REQUEST);
            $response->setData(['error' => Translation::get('ad_passwd_fail')]);
            return $response;
        }

        $user->getUserById($userId, true);

        $auth = new Auth($configuration);
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

        return $response;
    }

    #[Route('admin/api/user/delete')]
    public function deleteUser(Request $request): JsonResponse
    {
        $this->userHasPermission('delete_user');

        $response = new JsonResponse();
        $configuration = Configuration::getConfigurationInstance();
        $user = CurrentUser::getCurrentUser($configuration);

        $data = json_decode($request->getContent());

        if (!Token::getInstance()->verifyToken('delete-user', $data->csrfToken)) {
            $response->setStatusCode(Response::HTTP_UNAUTHORIZED);
            $response->setData(['error' => Translation::get('err_NotAuth')]);
            return $response;
        }

        $userId = Filter::filterVar($data->userId, FILTER_VALIDATE_INT);

        $user->getUserById($userId, true);
        if ($user->getStatus() == 'protected' || $userId == 1) {
            $message = '<p class="alert alert-error">' . Translation::get('ad_user_error_protectedAccount') . '</p>';
        } else {
            if (!$user->deleteUser()) {
                $message = Translation::get('ad_user_error_delete');
            } else {
                $category = new Category($configuration, [], false);
                $category->moveOwnership((int) $userId, 1);

                // Remove the user from groups
                if ('basic' !== $configuration->get('security.permLevel')) {
                    $permissions = Permission::selectPerm('medium', $configuration);
                    $permissions->removeFromAllGroups($userId);
                }

                $message = Translation::get('ad_user_deleted');
            }
        }
        $response->setStatusCode(Response::HTTP_OK);
        $response->setData($message);

        return $response;
    }

    #[Route('admin/api/user/add')]
    public function addUser(Request $request): JsonResponse
    {
        $this->userHasUserPermission();

        $response = new JsonResponse();
        $configuration = Configuration::getConfigurationInstance();

        $data = json_decode($request->getContent());

        if (!Token::getInstance()->verifyToken('add-user', $data->csrf)) {
            $response->setStatusCode(Response::HTTP_UNAUTHORIZED);
            $response->setData(['error' => Translation::get('err_NotAuth')]);
            return $response;
        }

        $errorMessage = [];
        $successMessage = '';

        $userName = Filter::filterVar($data->userName, FILTER_SANITIZE_SPECIAL_CHARS);
        $userRealName = Filter::filterVar($data->realName, FILTER_SANITIZE_SPECIAL_CHARS);
        $userEmail = Filter::filterVar($data->email, FILTER_VALIDATE_EMAIL);
        $automaticPassword = Filter::filterVar($data->automaticPassword, FILTER_VALIDATE_BOOLEAN);
        $userPassword = Filter::filterVar($data->password, FILTER_SANITIZE_SPECIAL_CHARS);
        $userPasswordConfirm = Filter::filterVar($data->passwordConfirm, FILTER_SANITIZE_SPECIAL_CHARS);
        $userIsSuperAdmin = Filter::filterVar($data->isSuperAdmin, FILTER_VALIDATE_BOOLEAN);

        $newUser = new User($configuration);

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
                $mailHelper = new MailHelper($configuration);
                try {
                    $mailHelper->sendMailToNewUser($newUser, $userPassword);
                } catch (Exception | TransportExceptionInterface $e) {
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
            return $response;
        }

        $response->setStatusCode(Response::HTTP_BAD_REQUEST);
        $response->setData($errorMessage);

        return $response;
    }
}
