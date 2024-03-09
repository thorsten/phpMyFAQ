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
use phpMyFAQ\Enums\PermissionType;
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
    /**
     * @throws Exception
     */
    #[Route('admin/api/user/users')]
    public function list(Request $request): JsonResponse
    {
        $this->userHasUserPermission();

        $jsonResponse = new JsonResponse();
        $user = CurrentUser::getCurrentUser(Configuration::getConfigurationInstance());

        $filtered = Filter::filterVar($request->query->get('filter'), FILTER_SANITIZE_SPECIAL_CHARS);

        if ('' === $filtered) {
            $allUsers = $user->getAllUsers(false);
            $userData = [];
            foreach ($allUsers as $allUser) {
                $user->getUserById($allUser, true);
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

            return $this->json($userData, Response::HTTP_OK);
        } else {
            $allUsers = [];
            foreach ($user->searchUsers($filtered) as $singleUser) {
                $users = new stdClass();
                $users->label = $singleUser['login'];
                $users->value = (int)$singleUser['user_id'];
                $allUsers[] = $users;
            }

            return $this->json($allUsers, Response::HTTP_OK);
        }
    }

    /**
     * @throws Exception
     */
    #[Route('admin/api/user/data')]
    public function userData(Request $request): JsonResponse
    {
        $this->userHasUserPermission();

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

        return $this->json($userdata, Response::HTTP_OK);
    }

    /**
     * @throws Exception
     */
    #[Route('admin/api/user/permissions')]
    public function userPermissions(Request $request): JsonResponse
    {
        $this->userHasUserPermission();

        $user = CurrentUser::getCurrentUser(Configuration::getConfigurationInstance());

        $userId = $request->get('userId');
        $user->getUserById($userId, true);

        return $this->json($user->perm->getUserRights($userId), Response::HTTP_OK);
    }

    /**
     * @throws Exception
     */
    #[Route('admin/api/user/activate')]
    public function activate(Request $request): JsonResponse
    {
        $this->userHasUserPermission();

        $user = CurrentUser::getCurrentUser(Configuration::getConfigurationInstance());

        $data = json_decode($request->getContent());
        if (!Token::getInstance()->verifyToken('activate-user', $data->csrfToken)) {
            return $this->json(['error' => Translation::get('err_NotAuth')], Response::HTTP_UNAUTHORIZED);
        }

        $userId = Filter::filterVar($data->userId, FILTER_VALIDATE_INT);

        $user->getUserById($userId, true);
        try {
            if ($user->activateUser()) {
                return $this->json(['success' => $user->getStatus()], Response::HTTP_OK);
            } else {
                return $this->json(['error' => $user->getStatus()], Response::HTTP_BAD_REQUEST);
            }
        } catch (TransportExceptionInterface | \Exception $exception) {
            return $this->json(['error' => $exception->getMessage()], Response::HTTP_BAD_REQUEST);
        }
    }

    /**
     * @throws Exception
     */
    #[Route('admin/api/user/overwrite-password')]
    public function overwritePassword(Request $request): JsonResponse
    {
        $this->userHasUserPermission();

        $jsonResponse = new JsonResponse();
        $configuration = Configuration::getConfigurationInstance();
        $user = CurrentUser::getCurrentUser($configuration);

        $data = json_decode($request->getContent());

        $userId = Filter::filterVar($data->userId, FILTER_VALIDATE_INT);
        $csrfToken = Filter::filterVar($data->csrf, FILTER_SANITIZE_SPECIAL_CHARS);
        $newPassword = Filter::filterVar($data->newPassword, FILTER_SANITIZE_SPECIAL_CHARS);
        $retypedPassword = Filter::filterVar($data->passwordRepeat, FILTER_SANITIZE_SPECIAL_CHARS);

        if (!Token::getInstance()->verifyToken('overwrite-password', $csrfToken)) {
            $jsonResponse->setStatusCode(Response::HTTP_UNAUTHORIZED);
            $jsonResponse->setData(['error' => Translation::get('err_NotAuth')]);
            return $jsonResponse;
        }

        if (strlen((string) $newPassword) <= 7 || strlen((string) $retypedPassword) <= 7) {
            $jsonResponse->setStatusCode(Response::HTTP_BAD_REQUEST);
            $jsonResponse->setData(['error' => Translation::get('ad_passwd_fail')]);
            return $jsonResponse;
        }

        $user->getUserById($userId, true);

        $auth = new Auth($configuration);
        $authSource = $auth->selectAuth($user->getAuthSource('name'));
        $authSource->selectEncType($user->getAuthData('encType'));

        if ($newPassword === $retypedPassword) {
            if (!$user->changePassword($newPassword)) {
                $jsonResponse->setStatusCode(Response::HTTP_BAD_REQUEST);
                $jsonResponse->setData(['error' => Translation::get('ad_passwd_fail')]);
                $jsonResponse->send();
            }

            $jsonResponse->setStatusCode(Response::HTTP_OK);
            $jsonResponse->setData(['success' => Translation::get('ad_passwdsuc')]);
        } else {
            $jsonResponse->setStatusCode(Response::HTTP_BAD_REQUEST);
            $jsonResponse->setData(['error' => Translation::get('ad_passwd_fail')]);
        }

        return $jsonResponse;
    }

    #[Route('admin/api/user/delete')]
    public function deleteUser(Request $request): JsonResponse
    {
        $this->userHasPermission(PermissionType::USER_DELETE);

        $jsonResponse = new JsonResponse();
        $configuration = Configuration::getConfigurationInstance();
        $user = CurrentUser::getCurrentUser($configuration);

        $data = json_decode($request->getContent());

        if (!Token::getInstance()->verifyToken('delete-user', $data->csrfToken)) {
            $jsonResponse->setStatusCode(Response::HTTP_UNAUTHORIZED);
            $jsonResponse->setData(['error' => Translation::get('err_NotAuth')]);
            return $jsonResponse;
        }

        $userId = Filter::filterVar($data->userId, FILTER_VALIDATE_INT);

        $user->getUserById($userId, true);
        if ($user->getStatus() == 'protected' || $userId == 1) {
            $message = '<p class="alert alert-error">' . Translation::get('ad_user_error_protectedAccount') . '</p>';
        } elseif (!$user->deleteUser()) {
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

        $jsonResponse->setStatusCode(Response::HTTP_OK);
        $jsonResponse->setData($message);

        return $jsonResponse;
    }

    /**
     * @throws Exception
     */
    #[Route('admin/api/user/add')]
    public function addUser(Request $request): JsonResponse
    {
        $this->userHasUserPermission();

        $jsonResponse = new JsonResponse();
        $configuration = Configuration::getConfigurationInstance();

        $data = json_decode($request->getContent());

        if (!Token::getInstance()->verifyToken('add-user', $data->csrf)) {
            $jsonResponse->setStatusCode(Response::HTTP_UNAUTHORIZED);
            $jsonResponse->setData(['error' => Translation::get('err_NotAuth')]);
            return $jsonResponse;
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
            if (strlen((string) $userPassword) <= 7 || strlen((string) $userPasswordConfirm) <= 7) {
                $errorMessage[] = Translation::get('ad_passwd_fail');
            }
        } else {
            $userPassword = $newUser->createPassword(8, false);
        }

        if ($errorMessage === []) {
            if (!$newUser->createUser($userName, $userPassword)) {
                $errorMessage[] = $newUser->error();
            } else {
                $newUser->userdata->set(['display_name', 'email', 'is_visible'], [$userRealName, $userEmail, 0]);
                $newUser->setStatus('active');
                $newUser->setSuperAdmin((bool)$userIsSuperAdmin);
                $mailHelper = new MailHelper($configuration);
                try {
                    $mailHelper->sendMailToNewUser($newUser, $userPassword);
                } catch (Exception | TransportExceptionInterface) {
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

            $jsonResponse->setStatusCode(Response::HTTP_OK);
            $jsonResponse->setData($successMessage);
            return $jsonResponse;
        }

        $jsonResponse->setStatusCode(Response::HTTP_BAD_REQUEST);
        $jsonResponse->setData($errorMessage);

        return $jsonResponse;
    }
}
