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
 * @copyright 2023-2025 phpMyFAQ Team
 * @license   https://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2023-10-27
 */

namespace phpMyFAQ\Controller\Administration\Api;

use phpMyFAQ\Administration\Report;
use phpMyFAQ\Auth;
use phpMyFAQ\Category;
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

        $currentUser = CurrentUser::getCurrentUser($this->configuration);

        $filtered = Filter::filterVar($request->query->get('filter'), FILTER_SANITIZE_SPECIAL_CHARS);

        if ('' === $filtered) {
            $allUsers = $currentUser->getAllUsers(false);
            $userData = [];
            foreach ($allUsers as $allUser) {
                $currentUser->getUserById($allUser, true);
                $user = new stdClass();
                $user->id = $currentUser->getUserId();
                $user->status = $currentUser->getStatus();
                $user->isSuperAdmin = $currentUser->isSuperAdmin();
                $user->isVisible = $currentUser->getUserData('is_visible');
                $user->displayName = Report::sanitize($currentUser->getUserData('display_name'));
                $user->userName = Report::sanitize($currentUser->getLogin());
                $user->email = $currentUser->getUserData('email');
                $user->authSource = $currentUser->getUserAuthSource();
                $userData[] = $user;
            }

            return $this->json($userData, Response::HTTP_OK);
        }

        $allUsers = [];
        foreach ($currentUser->searchUsers($filtered) as $singleUser) {
            $users = new stdClass();
            $users->label = $singleUser['login'];
            $users->value = (int)$singleUser['user_id'];
            $allUsers[] = $users;
        }

        return $this->json($allUsers, Response::HTTP_OK);
    }

    /**
     * @throws Exception
     */
    #[Route('admin/api/user/users/csv')]
    public function csvExport(): Response
    {
        $this->userHasUserPermission();

        $currentUser = CurrentUser::getCurrentUser($this->configuration);
        $allUsers = $currentUser->getAllUsers(false);

        $handle = fopen('php://temp', 'r+');
        fputcsv(
            $handle,
            ['ID', 'Status', 'Super Admin', 'Visible', 'Display Name', 'Username', 'Email', 'Auth Source'],
            ',',
            '"',
            '\\',
            PHP_EOL
        );

        foreach ($allUsers as $allUser) {
            $currentUser->getUserById($allUser, true);
            fputcsv(
                $handle,
                [
                    $currentUser->getUserId(),
                    $currentUser->getStatus(),
                    $currentUser->isSuperAdmin() ? 'true' : 'false',
                    $currentUser->getUserData('is_visible') ? 'true' : 'false',
                    Report::sanitize($currentUser->getUserData('display_name')),
                    Report::sanitize($currentUser->getLogin()),
                    $currentUser->getUserData('email'),
                    $currentUser->getUserAuthSource(),
                ],
                ',',
                '"',
                '\\',
                PHP_EOL
            );
        }

        rewind($handle);

        $content = stream_get_contents($handle);

        fclose($handle);

        $response = new Response($content);
        $response->headers->set('Content-Type', 'text/csv');
        $response->headers->set('Content-Disposition', 'attachment; filename="users.csv"');

        return $response;
    }

    /**
     * @throws Exception|\Exception
     */
    #[Route('admin/api/user/data')]
    public function userData(Request $request): JsonResponse
    {
        $this->userHasUserPermission();

        $user = $this->container->get('phpmyfaq.user.current_user');

        $user->getUserById($request->get('userId'), true);

        $userData = $user->userdata->get('*');
        if (is_array($userData)) {
            $userData['userId'] = $user->getUserId();
            $userData['status'] = $user->getStatus();
            $userData['login'] = $user->getLogin();
            $userData['displayName'] = $userData['display_name'];
            $userData['isSuperadmin'] = $user->isSuperAdmin();
            $userData['authSource'] = $user->getUserAuthSource();
            $userData['isVisible'] = $userData['is_visible'];
            $userData['twoFactorEnabled'] = $userData['twofactor_enabled'];
            $userData['lastModified'] = $userData['last_modified'];
        } else {
            $userData = [];
        }

        return $this->json($userData, Response::HTTP_OK);
    }

    /**
     * @throws Exception
     */
    #[Route('admin/api/user/permissions')]
    public function userPermissions(Request $request): JsonResponse
    {
        $this->userHasUserPermission();

        $currentUser = CurrentUser::getCurrentUser($this->configuration);

        $userId = $request->get('userId');
        $currentUser->getUserById($userId, true);

        return $this->json($currentUser->perm->getUserRights($userId), Response::HTTP_OK);
    }

    /**
     * @throws Exception
     * @throws \Exception
     */
    #[Route('admin/api/user/activate')]
    public function activate(Request $request): JsonResponse
    {
        $this->userHasUserPermission();

        $currentUser = CurrentUser::getCurrentUser($this->configuration);

        $data = json_decode($request->getContent());
        if (!Token::getInstance($this->container->get('session'))->verifyToken('activate-user', $data->csrfToken)) {
            return $this->json(['error' => Translation::get('msgNoPermission')], Response::HTTP_UNAUTHORIZED);
        }

        $userId = Filter::filterVar($data->userId, FILTER_VALIDATE_INT);

        $currentUser->getUserById($userId, true);
        try {
            if ($currentUser->activateUser()) {
                return $this->json(['success' => $currentUser->getStatus()], Response::HTTP_OK);
            }

            return $this->json(['error' => $currentUser->getStatus()], Response::HTTP_BAD_REQUEST);
        } catch (TransportExceptionInterface | \Exception $exception) {
            return $this->json(['error' => $exception->getMessage()], Response::HTTP_BAD_REQUEST);
        }
    }

    /**
     * @throws Exception
     * @throws \Exception
     */
    #[Route('admin/api/user/overwrite-password')]
    public function overwritePassword(Request $request): JsonResponse
    {
        $this->userHasUserPermission();

        $currentUser = CurrentUser::getCurrentUser($this->configuration);

        $data = json_decode($request->getContent());

        $userId = Filter::filterVar($data->userId, FILTER_VALIDATE_INT);
        $csrfToken = Filter::filterVar($data->csrf, FILTER_SANITIZE_SPECIAL_CHARS);
        $newPassword = Filter::filterVar($data->newPassword, FILTER_SANITIZE_SPECIAL_CHARS);
        $retypedPassword = Filter::filterVar($data->passwordRepeat, FILTER_SANITIZE_SPECIAL_CHARS);

        if (!Token::getInstance($this->container->get('session'))->verifyToken('overwrite-password', $csrfToken)) {
            return $this->json(['error' => Translation::get('msgNoPermission')], Response::HTTP_UNAUTHORIZED);
        }

        if (strlen((string) $newPassword) <= 7 || strlen((string) $retypedPassword) <= 7) {
            return $this->json(['error' => Translation::get('msgPasswordTooShort')], Response::HTTP_BAD_REQUEST);
        }

        $currentUser->getUserById($userId, true);

        $auth = new Auth($this->configuration);
        $authSource = $auth->selectAuth($currentUser->getAuthSource('name'));
        $authSource->getEncryptionContainer($currentUser->getAuthData('encType'));

        if ($newPassword === $retypedPassword) {
            if (!$currentUser->changePassword($newPassword)) {
                return $this->json(['error' => Translation::get('ad_passwd_fail')], Response::HTTP_BAD_REQUEST);
            }

            return $this->json(['success' => Translation::get('ad_passwdsuc')], Response::HTTP_OK);
        }

        return $this->json(['error' => Translation::get('msgPasswordsMustBeEqual')], Response::HTTP_BAD_REQUEST);
    }

    /**
     * @throws Exception
     */
    #[Route('admin/api/user/delete')]
    public function deleteUser(Request $request): JsonResponse
    {
        $this->userHasPermission(PermissionType::USER_DELETE);

        $currentUser = CurrentUser::getCurrentUser($this->configuration);

        $data = json_decode($request->getContent());

        if (!Token::getInstance($this->container->get('session'))->verifyToken('delete-user', $data->csrfToken)) {
            return $this->json(['error' => Translation::get('msgNoPermission')], Response::HTTP_UNAUTHORIZED);
        }

        $userId = Filter::filterVar($data->userId, FILTER_VALIDATE_INT);

        $currentUser->getUserById($userId, true);
        if ($currentUser->getStatus() === 'protected' || $userId === 1) {
            return $this->json(
                ['error' => Translation::get('ad_user_error_protectedAccount')],
                Response::HTTP_BAD_REQUEST
            );
        }

        if (!$currentUser->deleteUser()) {
            return $this->json(['error' => Translation::get('ad_user_error_delete')], Response::HTTP_BAD_REQUEST);
        }

        $category = new Category($this->configuration, [], false);
        $category->moveOwnership((int) $userId, 1);

        // Remove the user from groups
        if ('basic' !== $this->configuration->get('security.permLevel')) {
            $permissions = Permission::createPermission('medium', $this->configuration);
            $permissions->removeFromAllGroups($userId);
        }

        return $this->json(['success' => Translation::get('ad_user_deleted')], Response::HTTP_OK);
    }

    /**
     * @throws Exception
     */
    #[Route('admin/api/user/add')]
    public function addUser(Request $request): JsonResponse
    {
        $this->userHasUserPermission();

        $data = json_decode($request->getContent());

        if (!Token::getInstance($this->container->get('session'))->verifyToken('add-user', $data->csrf)) {
            return $this->json(['error' => Translation::get('msgNoPermission')], Response::HTTP_UNAUTHORIZED);
        }

        $errorMessage = [];

        $userName = Filter::filterVar($data->userName, FILTER_SANITIZE_SPECIAL_CHARS);
        $userRealName = Filter::filterVar($data->realName, FILTER_SANITIZE_SPECIAL_CHARS);
        $userEmail = Filter::filterVar($data->email, FILTER_VALIDATE_EMAIL);
        $automaticPassword = Filter::filterVar($data->automaticPassword, FILTER_VALIDATE_BOOLEAN);
        $userPassword = Filter::filterVar($data->password, FILTER_SANITIZE_SPECIAL_CHARS);
        $userPasswordConfirm = Filter::filterVar($data->passwordConfirm, FILTER_SANITIZE_SPECIAL_CHARS);
        $userIsSuperAdmin = Filter::filterVar($data->isSuperAdmin, FILTER_VALIDATE_BOOLEAN);

        $newUser = new User($this->configuration);

        if (!$newUser->isValidLogin($userName)) {
            $errorMessage[] = Translation::get('ad_user_error_loginInvalid');
        }

        if ($newUser->getUserByLogin($userName, false)) {
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
                $mailHelper = new MailHelper($this->configuration);
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

        if (!Token::getInstance($this->container->get('session'))->verifyToken('update-user-data', $data->csrfToken)) {
            return $this->json(['error' => Translation::get('msgNoPermission')], Response::HTTP_UNAUTHORIZED);
        }

        $userId = Filter::filterVar($data->userId, FILTER_VALIDATE_INT, 0);
        if ($userId === 0) {
            return $this->json(['error' => Translation::get('ad_user_error_noId')], Response::HTTP_BAD_REQUEST);
        }

        $userData = [];
        $userData['display_name'] = Filter::filterVar($data->display_name, FILTER_SANITIZE_SPECIAL_CHARS);
        $userData['email'] = Filter::filterVar($data->email, FILTER_VALIDATE_EMAIL);
        $userData['last_modified'] = Filter::filterVar($data->last_modified, FILTER_SANITIZE_SPECIAL_CHARS);
        $userStatus = Filter::filterVar($data->user_status, FILTER_SANITIZE_SPECIAL_CHARS, 'active');
        $isSuperAdmin = Filter::filterVar($data->is_superadmin, FILTER_SANITIZE_SPECIAL_CHARS);
        $deleteTwoFactor = Filter::filterVar($data->overwrite_twofactor, FILTER_SANITIZE_SPECIAL_CHARS);
        $deleteTwoFactor = $deleteTwoFactor === 'on';

        $user = new User($this->configuration);
        $user->getUserById($userId, true);

        $stats = $user->getStatus();

        // reset two-factor authentication if required
        if ($deleteTwoFactor) {
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

        if (!$user->userdata->set(array_keys($userData), array_values($userData)) || !$user->setStatus($userStatus)) {
            return $this->json(['error' => 'ad_msg_mysqlerr'], Response::HTTP_BAD_REQUEST);
        }

        $success = Translation::get('ad_msg_savedsuc_1') . ' ' .
            Strings::htmlentities($user->getLogin(), ENT_QUOTES) . ' ' .
            Translation::get('ad_msg_savedsuc_2');
        return $this->json(['success' => $success], Response::HTTP_OK);
    }

    /**
     * @throws Exception
     * @throws \Exception
     */
    #[Route('admin/api/user/update-rights')]
    public function updateUserRights(Request $request): JsonResponse
    {
        $this->userHasPermission(PermissionType::USER_EDIT);

        $data = json_decode($request->getContent());

        if (
            !Token::getInstance($this->container->get('session'))
                ->verifyToken('update-user-rights', $data->csrfToken)
        ) {
            return $this->json(['error' => Translation::get('msgNoPermission')], Response::HTTP_UNAUTHORIZED);
        }

        $userId = Filter::filterVar($data->userId, FILTER_VALIDATE_INT, 0);

        if (0 === (int)$userId) {
            return $this->json(['error' => Translation::get('ad_user_error_noId')], Response::HTTP_BAD_REQUEST);
        }

        $user = new User($this->configuration);
        $user->getUserById($userId);
        $userRights = Filter::filterVar($data->userRights, FILTER_SANITIZE_SPECIAL_CHARS, []);
        if (!$user->perm->refuseAllUserRights($userId)) {
            return $this->json(['error' => Translation::get('ad_msg_mysqlerr')], Response::HTTP_BAD_REQUEST);
        }

        foreach ($userRights as $userRight) {
            $user->perm->grantUserRight($userId, $userRight);
        }

        $user->terminateSessionId();
        $success = Translation::get('ad_msg_savedsuc_1') .
            ' ' . Strings::htmlentities($user->getLogin(), ENT_QUOTES) . ' ' .
            Translation::get('ad_msg_savedsuc_2');

        return $this->json(['success' => $success], Response::HTTP_OK);
    }
}
