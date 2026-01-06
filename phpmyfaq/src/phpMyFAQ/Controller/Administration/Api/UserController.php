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
 * @copyright 2023-2026 phpMyFAQ Team
 * @license   https://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2023-10-27
 */

declare(strict_types=1);

namespace phpMyFAQ\Controller\Administration\Api;

use phpMyFAQ\Administration\Report;
use phpMyFAQ\Auth;
use phpMyFAQ\Category;
use phpMyFAQ\Core\Exception;
use phpMyFAQ\Enums\AdminLogType;
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
use Symfony\Component\Routing\Attribute\Route;

final class UserController extends AbstractAdministrationApiController
{
    /**
     * @throws Exception
     */
    #[Route(path: 'admin/api/user/users')]
    public function list(Request $request): JsonResponse
    {
        $this->userHasUserPermission();

        $currentUser = CurrentUser::getCurrentUser($this->configuration);

        $filtered = Filter::filterVar($request->query->get(key: 'filter'), FILTER_SANITIZE_SPECIAL_CHARS);

        if ('' === $filtered) {
            $allUsers = $currentUser->getAllUsers(withoutAnonymous: false);
            $userData = [];
            foreach ($allUsers as $allUser) {
                $currentUser->getUserById($allUser, allowBlockedUsers: true);
                $user = new stdClass();
                $user->id = $currentUser->getUserId();
                $user->status = $currentUser->getStatus();
                $user->isSuperAdmin = $currentUser->isSuperAdmin();
                $user->isVisible = $currentUser->getUserData(field: 'is_visible');
                $user->displayName = Report::sanitize($currentUser->getUserData(field: 'display_name'));
                $user->userName = Report::sanitize($currentUser->getLogin());
                $user->email = $currentUser->getUserData(field: 'email');
                $user->authSource = $currentUser->getUserAuthSource();
                $userData[] = $user;
            }

            return $this->json($userData, Response::HTTP_OK);
        }

        $allUsers = [];
        foreach ($currentUser->searchUsers($filtered) as $singleUser) {
            $users = new stdClass();
            $users->label = $singleUser['login'];
            $users->value = (int) $singleUser['user_id'];
            $allUsers[] = $users;
        }

        return $this->json($allUsers, Response::HTTP_OK);
    }

    /**
     * @throws Exception
     */
    #[Route(path: 'admin/api/user/users/csv')]
    public function csvExport(): Response
    {
        $this->userHasUserPermission();

        $currentUser = CurrentUser::getCurrentUser($this->configuration);
        $allUsers = $currentUser->getAllUsers(withoutAnonymous: false);

        $handle = fopen(filename: 'php://temp', mode: 'r+');
        fputcsv(
            $handle,
            ['ID', 'Status', 'Super Admin', 'Visible', 'Display Name', 'Username', 'Email', 'Auth Source'],
            separator: ',',
            enclosure: '"',
            eol: PHP_EOL,
        );

        foreach ($allUsers as $allUser) {
            $currentUser->getUserById($allUser, allowBlockedUsers: true);
            fputcsv(
                $handle,
                [
                    $currentUser->getUserId(),
                    $currentUser->getStatus(),
                    $currentUser->isSuperAdmin() ? 'true' : 'false',
                    $currentUser->getUserData(field: 'is_visible') ? 'true' : 'false',
                    Report::sanitize($currentUser->getUserData(field: 'display_name')),
                    Report::sanitize($currentUser->getLogin()),
                    $currentUser->getUserData(field: 'email'),
                    $currentUser->getUserAuthSource(),
                ],
                separator: ',',
                enclosure: '"',
                eol: PHP_EOL,
            );
        }

        rewind($handle);

        $content = stream_get_contents($handle);

        fclose($handle);

        $this->adminLog->log($this->currentUser, AdminLogType::DATA_EXPORT_USERS->value);

        $response = new Response($content);
        $response->headers->set(key: 'Content-Type', values: 'text/csv');
        $response->headers->set(key: 'Content-Disposition', values: 'attachment; filename="users.csv"');

        return $response;
    }

    /**
     * @throws Exception|\Exception
     */
    #[Route(path: 'admin/api/user/data/:userId')]
    public function userData(Request $request): JsonResponse
    {
        $this->userHasUserPermission();

        $user = $this->container->get(id: 'phpmyfaq.user.current_user');

        $user->getUserById((int) $request->attributes->get(key: 'userId'), allowBlockedUsers: true);

        $userData = [];

        $data = $user->userdata->get(field: '*');
        if (is_array($data)) {
            $userData = $data;
            $userData['userId'] = $user->getUserId();
            $userData['status'] = $user->getStatus();
            $userData['login'] = $user->getLogin();
            $userData['displayName'] = $userData['display_name'];
            $userData['isSuperadmin'] = $user->isSuperAdmin();
            $userData['authSource'] = $user->getUserAuthSource();
            $userData['isVisible'] = $userData['is_visible'];
            $userData['twoFactorEnabled'] = $userData['twofactor_enabled'];
            $userData['lastModified'] = $userData['last_modified'];
        }

        return $this->json($userData, Response::HTTP_OK);
    }

    /**
     * @throws Exception
     */
    #[Route(path: 'admin/api/user/permissions/:userId')]
    public function userPermissions(Request $request): JsonResponse
    {
        $this->userHasUserPermission();

        $currentUser = CurrentUser::getCurrentUser($this->configuration);

        $userId = $request->attributes->get(key: 'userId');
        $currentUser->getUserById((int) $userId, allowBlockedUsers: true);

        return $this->json($currentUser->perm->getUserRights((int) $userId), Response::HTTP_OK);
    }

    /**
     * @throws Exception
     * @throws \Exception
     */
    #[Route(path: 'admin/api/user/activate')]
    public function activate(Request $request): JsonResponse
    {
        $this->userHasUserPermission();

        $currentUser = CurrentUser::getCurrentUser($this->configuration);

        $data = json_decode($request->getContent());
        if (!Token::getInstance($this->session)->verifyToken(page: 'activate-user', requestToken: $data->csrfToken)) {
            return $this->json(['error' => Translation::get(key: 'msgNoPermission')], Response::HTTP_UNAUTHORIZED);
        }

        $userId = Filter::filterVar($data->userId, FILTER_VALIDATE_INT);

        $currentUser->getUserById((int) $userId, allowBlockedUsers: true);
        try {
            if ($currentUser->activateUser()) {
                $this->adminLog->log($this->currentUser, AdminLogType::USER_EDIT->value . ' (activated):' . $userId);
                return $this->json(['success' => $currentUser->getStatus()], Response::HTTP_OK);
            }

            return $this->json(['error' => $currentUser->getStatus()], Response::HTTP_BAD_REQUEST);
        } catch (TransportExceptionInterface|\Exception $exception) {
            return $this->json(['error' => $exception->getMessage()], Response::HTTP_BAD_REQUEST);
        }
    }

    /**
     * @throws Exception
     * @throws \Exception
     */
    #[Route(path: 'admin/api/user/overwrite-password')]
    public function overwritePassword(Request $request): JsonResponse
    {
        $this->userHasUserPermission();

        $currentUser = CurrentUser::getCurrentUser($this->configuration);

        $data = json_decode($request->getContent());

        $userId = Filter::filterVar($data->userId, FILTER_VALIDATE_INT);
        $csrfToken = Filter::filterVar($data->csrf, FILTER_SANITIZE_SPECIAL_CHARS);
        $newPassword = Filter::filterVar($data->newPassword, FILTER_SANITIZE_SPECIAL_CHARS);
        $retypedPassword = Filter::filterVar($data->passwordRepeat, FILTER_SANITIZE_SPECIAL_CHARS);

        if (!Token::getInstance($this->session)->verifyToken(page: 'overwrite-password', requestToken: $csrfToken)) {
            return $this->json(['error' => Translation::get(key: 'msgNoPermission')], Response::HTTP_UNAUTHORIZED);
        }

        if (strlen((string) $newPassword) <= 7 || strlen((string) $retypedPassword) <= 7) {
            return $this->json(['error' => Translation::get(key: 'msgPasswordTooShort')], Response::HTTP_BAD_REQUEST);
        }

        $currentUser->getUserById((int) $userId, allowBlockedUsers: true);

        $auth = new Auth($this->configuration);
        $authSource = $auth->selectAuth($currentUser->getAuthSource(key: 'name'));
        $authSource->getEncryptionContainer($currentUser->getAuthData(key: 'encType'));

        if (hash_equals($newPassword, $retypedPassword)) {
            if (!$currentUser->changePassword($newPassword)) {
                return $this->json(['error' => Translation::get(key: 'ad_passwd_fail')], Response::HTTP_BAD_REQUEST);
            }

            $this->adminLog->log($this->currentUser, AdminLogType::USER_CHANGE_PASSWORD->value . ':' . $userId);

            return $this->json(['success' => Translation::get(key: 'ad_passwdsuc')], Response::HTTP_OK);
        }

        return $this->json(['error' => Translation::get(key: 'msgPasswordsMustBeEqual')], Response::HTTP_BAD_REQUEST);
    }

    /**
     * @throws Exception
     * *@throws \Exception
     *
     */
    #[Route(path: 'admin/api/user/delete')]
    public function deleteUser(Request $request): JsonResponse
    {
        $this->userHasPermission(PermissionType::USER_DELETE);

        $currentUser = CurrentUser::getCurrentUser($this->configuration);

        $data = json_decode($request->getContent());

        if (!Token::getInstance($this->session)->verifyToken(page: 'delete-user', requestToken: $data->csrfToken)) {
            return $this->json(['error' => Translation::get(key: 'msgNoPermission')], Response::HTTP_UNAUTHORIZED);
        }

        $userId = Filter::filterVar($data->userId, FILTER_VALIDATE_INT);

        $currentUser->getUserById($userId, allowBlockedUsers: true);
        if ($currentUser->getStatus() === 'protected' || $userId === 1) {
            return $this->json(['error' => Translation::get(
                key: 'ad_user_error_protectedAccount',
            )], Response::HTTP_BAD_REQUEST);
        }

        if (!$currentUser->deleteUser()) {
            return $this->json(['error' => Translation::get(key: 'ad_user_error_delete')], Response::HTTP_BAD_REQUEST);
        }

        $category = new Category($this->configuration, [], withPermission: false);
        $category->moveOwnership((int) $userId, newOwner: 1);

        // Remove the user from groups
        if ('basic' !== $this->configuration->get(item: 'security.permLevel')) {
            $permissions = Permission::create(permLevel: 'medium', configuration: $this->configuration);
            $permissions->removeFromAllGroups($userId);
        }

        $this->adminLog->log($this->currentUser, AdminLogType::USER_DELETE->value . ':' . $userId);

        return $this->json(['success' => Translation::get(key: 'ad_user_deleted')], Response::HTTP_OK);
    }

    /**
     * @throws Exception
     * @throws \Exception
     */
    #[Route(path: 'admin/api/user/add')]
    public function addUser(Request $request): JsonResponse
    {
        $this->userHasUserPermission();

        $data = json_decode($request->getContent());

        if (!Token::getInstance($this->session)->verifyToken(page: 'add-user', requestToken: $data->csrf)) {
            return $this->json(['error' => Translation::get(key: 'msgNoPermission')], Response::HTTP_UNAUTHORIZED);
        }

        $errorMessage = [];

        $userName = Filter::filterVar($data->userName, FILTER_SANITIZE_SPECIAL_CHARS);
        $userRealName = trim(strip_tags((string) $data->realName));
        $userEmail = Filter::filterVar($data->email, FILTER_VALIDATE_EMAIL);
        $automaticPassword = Filter::filterVar($data->automaticPassword, FILTER_VALIDATE_BOOLEAN);
        $userPassword = Filter::filterVar($data->password, FILTER_SANITIZE_SPECIAL_CHARS);
        $userPasswordConfirm = Filter::filterVar($data->passwordConfirm, FILTER_SANITIZE_SPECIAL_CHARS);
        $userIsSuperAdmin = Filter::filterVar($data->isSuperAdmin, FILTER_VALIDATE_BOOLEAN);

        $newUser = new User($this->configuration);

        if (!$newUser->isValidLogin($userName)) {
            $errorMessage[] = Translation::get(key: 'ad_user_error_loginInvalid');
        }

        if ($newUser->getUserByLogin($userName, raiseError: false)) {
            $errorMessage[] = Translation::get(key: 'ad_adus_exerr');
        }

        if ($userRealName === '') {
            $errorMessage[] = Translation::get(key: 'ad_user_error_noRealName');
        }

        if (is_null($userEmail)) {
            $errorMessage[] = Translation::get(key: 'ad_user_error_noEmail');
        }

        if (
            !$automaticPassword
            && (strlen((string) $userPassword) <= 7 || strlen((string) $userPasswordConfirm) <= 7)
        ) {
            $errorMessage[] = Translation::get(key: 'ad_passwd_fail');
        }

        if ($automaticPassword) {
            $userPassword = $newUser->createPassword(minimumLength: 8, allowUnderscore: false);
        }

        if ($errorMessage === []) {
            if (!$newUser->createUser($userName, $userPassword)) {
                $errorMessage[] = $newUser->error();
                return $this->json($errorMessage, Response::HTTP_BAD_REQUEST);
            }

            $newUser->userdata->set(['display_name', 'email', 'is_visible'], [$userRealName, $userEmail, 0]);
            $newUser->setStatus(status: 'active');
            $newUser->setSuperAdmin((bool) $userIsSuperAdmin);

            $mailHelper = new MailHelper($this->configuration);
            try {
                $mailHelper->sendMailToNewUser($newUser, $userPassword);
            } catch (Exception|TransportExceptionInterface) {
                /* @mago-expect lint:no-empty-catch-clause */
            }

            $this->adminLog->log($this->currentUser, AdminLogType::USER_ADD->value . ':' . $newUser->getUserId());

            return $this->json(['success' => Translation::get(key: 'ad_adus_suc')], Response::HTTP_OK);
        }

        return $this->json($errorMessage, Response::HTTP_BAD_REQUEST);
    }

    /**
     * @throws Exception|\Exception|TransportExceptionInterface
     */
    #[Route(path: 'admin/api/user/edit')]
    public function editUser(Request $request): JsonResponse
    {
        $this->userHasPermission(PermissionType::USER_EDIT);

        $data = json_decode($request->getContent());

        if (!Token::getInstance($this->session)->verifyToken(
            page: 'update-user-data',
            requestToken: $data->csrfToken,
        )) {
            return $this->json(['error' => Translation::get(key: 'msgNoPermission')], Response::HTTP_UNAUTHORIZED);
        }

        $userId = Filter::filterVar($data->userId, FILTER_VALIDATE_INT, default: 0);
        if ($userId === 0) {
            return $this->json(['error' => Translation::get(key: 'ad_user_error_noId')], Response::HTTP_BAD_REQUEST);
        }

        $userData = [];
        $userData['display_name'] = trim(strip_tags((string) $data->display_name));
        $userData['email'] = Filter::filterVar($data->email, FILTER_VALIDATE_EMAIL);
        $userData['last_modified'] = Filter::filterVar($data->last_modified, FILTER_SANITIZE_SPECIAL_CHARS);
        $userStatus = Filter::filterVar($data->user_status, FILTER_SANITIZE_SPECIAL_CHARS, default: 'active');
        $isSuperAdmin = Filter::filterVar($data->is_superadmin, FILTER_SANITIZE_SPECIAL_CHARS);
        $deleteTwoFactor = Filter::filterVar($data->overwrite_twofactor, FILTER_SANITIZE_SPECIAL_CHARS);
        $deleteTwoFactor = $deleteTwoFactor === 'on';

        $user = new User($this->configuration);
        $user->getUserById($userId, allowBlockedUsers: true);

        $stats = $user->getStatus();
        $wasSuperAdmin = $user->isSuperAdmin();

        // reset two-factor authentication if required
        if ($deleteTwoFactor) {
            $user->setUserData(['secret' => '', 'twofactor_enabled' => 0]);
            $this->adminLog->log($this->currentUser, AdminLogType::AUTH_2FA_RESET->value . ':' . $userId);
        }

        // set a new password and sent email if a user is switched to active
        if ($stats === 'blocked' && $userStatus === 'active' && !$user->activateUser()) {
            $userStatus = 'invalid_status';
        }

        // Log status change
        if ($stats !== $userStatus) {
            $this->adminLog->log(
                $this->currentUser,
                AdminLogType::USER_STATUS_CHANGED->value . ':' . $userId . ' (' . $stats . ' -> ' . $userStatus . ')',
            );
        }

        // Set the super-admin flag and log changes
        $user->setSuperAdmin((bool) $isSuperAdmin);
        if (!$wasSuperAdmin && (bool) $isSuperAdmin) {
            $this->adminLog->log($this->currentUser, AdminLogType::USER_SUPERADMIN_GRANTED->value . ':' . $userId);
        } elseif ($wasSuperAdmin && !(bool) $isSuperAdmin) {
            $this->adminLog->log($this->currentUser, AdminLogType::USER_SUPERADMIN_REVOKED->value . ':' . $userId);
        }

        if (!$user->userdata->set(array_keys($userData), array_values($userData)) || !$user->setStatus($userStatus)) {
            return $this->json(['error' => 'ad_msg_mysqlerr'], Response::HTTP_BAD_REQUEST);
        }

        $this->adminLog->log($this->currentUser, AdminLogType::USER_EDIT->value . ':' . $userId);

        $success =
            Translation::get(key: 'ad_msg_savedsuc_1')
            . ' "'
            . Strings::htmlentities($user->getLogin(), ENT_QUOTES)
            . '" '
            . Translation::get(key: 'ad_msg_savedsuc_2');
        return $this->json(['success' => $success], Response::HTTP_OK);
    }

    /**
     * @throws Exception
     * @throws \Exception
     */
    #[Route(path: 'admin/api/user/update-rights')]
    public function updateUserRights(Request $request): JsonResponse
    {
        $this->userHasPermission(PermissionType::USER_EDIT);

        $data = json_decode($request->getContent());

        if (!Token::getInstance($this->session)->verifyToken(
            page: 'update-user-rights',
            requestToken: $data->csrfToken,
        )) {
            return $this->json(['error' => Translation::get(key: 'msgNoPermission')], Response::HTTP_UNAUTHORIZED);
        }

        $userId = Filter::filterVar($data->userId, FILTER_VALIDATE_INT, default: 0);

        if (0 === (int) $userId) {
            return $this->json(['error' => Translation::get(key: 'ad_user_error_noId')], Response::HTTP_BAD_REQUEST);
        }

        $user = new User($this->configuration);
        $user->getUserById($userId);

        $userRights = Filter::filterVar($data->userRights, FILTER_SANITIZE_SPECIAL_CHARS, []);
        if (!$user->perm->refuseAllUserRights($userId)) {
            return $this->json(['error' => Translation::get(key: 'ad_msg_mysqlerr')], Response::HTTP_BAD_REQUEST);
        }

        foreach ($userRights as $userRight) {
            $user->perm->grantUserRight($userId, (int) $userRight);
        }

        $this->adminLog->log($this->currentUser, AdminLogType::USER_CHANGE_PERMISSIONS->value . ':' . $userId);

        $user->terminateSessionId();
        $success =
            Translation::get(key: 'ad_msg_savedsuc_1')
            . ' "'
            . Strings::htmlentities($user->getLogin(), ENT_QUOTES)
            . '" '
            . Translation::get(key: 'ad_msg_savedsuc_2');

        return $this->json(['success' => $success], Response::HTTP_OK);
    }
}
