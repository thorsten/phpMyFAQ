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
            $json = $this->json(
                ['error' => Translation::get('ad_user_error_protectedAccount')],
                Response::HTTP_BAD_REQUEST
            );
        } elseif (!$user->deleteUser()) {
            $json = $this->json(['error' => Translation::get('ad_user_error_delete')], Response::HTTP_BAD_REQUEST);
        } else {
            $category = new Category($configuration, [], false);
            $category->moveOwnership((int) $userId, 1);

            // Remove the user from groups
            if ('basic' !== $configuration->get('security.permLevel')) {
                $permissions = Permission::selectPerm('medium', $configuration);
                $permissions->removeFromAllGroups($userId);
            }

            $json = $this->json(['success' => Translation::get('ad_user_deleted')], Response::HTTP_OK);
        }

        return $json;
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

                return $this->json(['success' => Translation::get('ad_adus_suc')], Response::HTTP_OK);
            }
        }

        return $this->json($errorMessage, Response::HTTP_BAD_REQUEST);
    }

    #[Route('admin/api/user/edit')]
    public function editUser(Request $request): JsonResponse
    {
        $this->userHasPermission(PermissionType::USER_EDIT);

        $data = json_decode($request->getContent());

        if (!Token::getInstance()->verifyToken('update-user-data', $data->csrfToken)) {
            return $this->json(['error' => Translation::get('err_NotAuth')], Response::HTTP_UNAUTHORIZED);
        }

        $userId = Filter::filterVar($data->userId, FILTER_VALIDATE_INT, 0);
        if ($userId === 0) {
            return $this->json(['error' => Translation::get('ad_user_error_noId')], Response::HTTP_BAD_REQUEST);
        } else {
            $userData = [];
            $userData['display_name'] = Filter::filterVar($data->display_name, FILTER_SANITIZE_SPECIAL_CHARS);
            $userData['email'] = Filter::filterVar($data->email, FILTER_VALIDATE_EMAIL);
            $userData['last_modified'] = Filter::filterVar($data->last_modified, FILTER_SANITIZE_SPECIAL_CHARS);
            $userStatus = Filter::filterVar($data->user_status, FILTER_SANITIZE_SPECIAL_CHARS, 'active');
            $isSuperAdmin = Filter::filterVar($data->is_superadmin, FILTER_SANITIZE_SPECIAL_CHARS);
            $deleteTwofactor = Filter::filterVar($data->overwrite_twofactor, FILTER_SANITIZE_SPECIAL_CHARS);
            $deleteTwofactor = $deleteTwofactor === 'on';

            $user = new User($this->configuration);
            $user->getUserById($userId, true);

            $stats = $user->getStatus();

            // reset two-factor authentication if required
            if ($deleteTwofactor) {
                $user->setUserData(['secret' => '', 'twofactor_enabled' => 0]);
            }

            // set new password and sent email if a user is switched to active
            if ($stats == 'blocked' && $userStatus == 'active') {
                if (!$user->activateUser()) {
                    $userStatus = 'invalid_status';
                }
            }

            // Set super-admin flag
            $user->setSuperAdmin($isSuperAdmin);

            if (
                !$user->userdata->set(array_keys($userData), array_values($userData)) || !$user->setStatus(
                    $userStatus
                )
            ) {
                return $this->json(['error' => 'ad_msg_mysqlerr'], Response::HTTP_BAD_REQUEST);
            } else {
                $success = Translation::get('ad_msg_savedsuc_1') . ' ' .
                    Strings::htmlentities($user->getLogin(), ENT_QUOTES) . ' ' .
                    Translation::get('ad_msg_savedsuc_2');
                return $this->json(['success' => $success], Response::HTTP_OK);
            }
        }
    }

    #[Route('admin/api/user/update-rights')]
    public function updateUserRights(Request $request): JsonResponse
    {
        $this->userHasPermission(PermissionType::USER_EDIT);

        $data = json_decode($request->getContent());

        if (!Token::getInstance()->verifyToken('update-user-rights', $data->csrfToken)) {
            return $this->json(['error' => Translation::get('err_NotAuth')], Response::HTTP_UNAUTHORIZED);
        }

        $userId = Filter::filterVar($data->userId, FILTER_VALIDATE_INT, 0);

        if (0 === (int)$userId) {
            return $this->json(['error' => Translation::get('ad_user_error_noId')], Response::HTTP_BAD_REQUEST);
        } else {
            $user = new User($this->configuration);
            $userRights = Filter::filterVar($data->userRights, FILTER_SANITIZE_SPECIAL_CHARS, []);
            if (!$user->perm->refuseAllUserRights($userId)) {
                return $this->json(['error' => Translation::get('ad_msg_mysqlerr')], Response::HTTP_BAD_REQUEST);
            }
            foreach ($userRights as $rightId) {
                $user->perm->grantUserRight($userId, $rightId);
            }

            $user->terminateSessionId();
            $success = Translation::get('ad_msg_savedsuc_1') .
                ' ' . Strings::htmlentities($user->getLogin(), ENT_QUOTES) . ' ' .
                Translation::get('ad_msg_savedsuc_2');
            return $this->json(['success' => $success], Response::HTTP_OK);
        }
    }
}
