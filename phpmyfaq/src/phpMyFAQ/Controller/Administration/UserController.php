<?php

/**
 * The User Administration Controller
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at https://mozilla.org/MPL/2.0/.
 *
 * @package   phpMyFAQ
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2024-2026 phpMyFAQ Team
 * @license   https://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2024-11-23
 */

declare(strict_types=1);

namespace phpMyFAQ\Controller\Administration;

use phpMyFAQ\Core\Exception;
use phpMyFAQ\Enums\PermissionType;
use phpMyFAQ\Filter;
use phpMyFAQ\Pagination;
use phpMyFAQ\Pagination\UrlConfig;
use phpMyFAQ\Session\Token;
use phpMyFAQ\Twig\Extensions\PermissionTranslationTwigExtension;
use phpMyFAQ\User;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Twig\Error\LoaderError;
use Twig\Extension\AttributeExtension;

final class UserController extends AbstractAdministrationController
{
    public function __construct(
        private readonly User $user,
    ) {
        parent::__construct();
    }

    /**
     * @throws Exception
     * @throws LoaderError
     * @throws \Exception
     */
    #[Route(path: '/user', name: 'admin.user', methods: ['GET'])]
    public function index(Request $request): Response
    {
        $this->userHasPermission(PermissionType::USER_ADD);
        $this->userHasPermission(PermissionType::USER_DELETE);
        $this->userHasPermission(PermissionType::USER_EDIT);

        $this->addExtension(new AttributeExtension(PermissionTranslationTwigExtension::class));
        return $this->render('@admin/user/user.twig', [
            ...$this->getHeader($request),
            ...$this->getFooter(),
            ...$this->getBaseTemplateVars(),
        ]);
    }

    /**
     * @throws Exception
     * @throws LoaderError
     * @throws \Exception
     */
    #[Route(path: '/user/edit/{userId}', name: 'admin.user.edit', methods: ['GET'])]
    public function edit(Request $request): Response
    {
        $this->userHasPermission(PermissionType::USER_ADD);
        $this->userHasPermission(PermissionType::USER_DELETE);
        $this->userHasPermission(PermissionType::USER_EDIT);

        $userId = (int) Filter::filterVar($request->attributes->get('userId'), FILTER_VALIDATE_INT);

        $this->addExtension(new AttributeExtension(PermissionTranslationTwigExtension::class));
        return $this->render('@admin/user/user.twig', [
            ...$this->getHeader($request),
            ...$this->getFooter(),
            ...$this->getBaseTemplateVars(),
            'userId' => $userId,
        ]);
    }

    /**
     * @throws Exception
     * @throws LoaderError
     * @throws \Exception
     */
    #[Route(path: '/user/list', name: 'admin.user.list', methods: ['GET'])]
    public function list(Request $request): Response
    {
        $this->userHasPermission(PermissionType::USER_ADD);
        $this->userHasPermission(PermissionType::USER_DELETE);
        $this->userHasPermission(PermissionType::USER_EDIT);

        $allUsers = $this->user->getAllUsers(false);
        $numUsers = is_countable($allUsers) ? count($allUsers) : 0;

        $page = Filter::filterVar($request->query->get('page'), FILTER_VALIDATE_INT, 0);
        $perPage = 10;
        $lastPage = $page * $perPage;
        $firstPage = $lastPage - $perPage;

        $pagination = new Pagination(
            baseUrl: sprintf('%sadmin/user/list?page=%d', $this->configuration->getDefaultUrl(), $page),
            total: $numUsers,
            perPage: $perPage,
            urlConfig: new UrlConfig(pageParamName: 'page'),
        );

        $counter = 0;
        $displayedCounter = 0;
        $users = [];
        foreach ($allUsers as $allUser) {
            $this->user->getUserById($allUser, true);

            if ($displayedCounter >= $perPage) {
                continue;
            }

            ++$counter;
            if ($counter <= $firstPage) {
                continue;
            }

            ++$displayedCounter;

            $tempUser = [
                'display_name' => $this->user->getUserData('display_name'),
                'id' => $this->user->getUserId(),
                'email' => $this->user->getUserData('email'),
                'status' => $this->user->getStatus(),
                'isSuperAdmin' => $this->user->isSuperAdmin(),
                'isVisible' => $this->user->getUserData('is_visible'),
                'login' => $this->user->getLogin(),
            ];

            $users[] = $tempUser;
        }

        return $this->render('@admin/user/user-list.twig', [
            ...$this->getHeader($request),
            ...$this->getFooter(),
            ...$this->getBaseTemplateVars(),
            'perPage' => $perPage,
            'numUsers' => $numUsers,
            'pagination' => $pagination->render(),
            'users' => $users,
            'userIsSuperAdmin' => $this->currentUser->isSuperAdmin(),
        ]);
    }

    /**
     * @throws \Exception
     * @return array<string, mixed>
     */
    private function getBaseTemplateVars(): array
    {
        $currentUserId = $this->currentUser->getUserId();

        return [
            'permissionAddUser' => $this->currentUser?->perm->hasPermission(
                $currentUserId,
                PermissionType::USER_ADD->value,
            ),
            'permissionDeleteUser' => $this->currentUser->perm->hasPermission(
                $currentUserId,
                PermissionType::USER_DELETE->value,
            ),
            'permissionEditUser' => $this->currentUser->perm->hasPermission(
                $currentUserId,
                PermissionType::USER_EDIT->value,
            ),
            'csrfToken_updateUserData' => Token::getInstance($this->session)->getTokenString('update-user-data'),
            'csrfToken_updateUserRights' => Token::getInstance($this->session)->getTokenString('update-user-rights'),
            'csrfToken_activateUser' => Token::getInstance($this->session)->getTokenString('activate-user'),
            'csrfToken_deleteUser' => Token::getInstance($this->session)->getTokenString('delete-user'),
            'csrfToken_addUser' => Token::getInstance($this->session)->getTokenString('add-user'),
            'csrfToken_overwritePassword' => Token::getInstance($this->session)->getTokenString('overwrite-password'),
            'userRights' => $this->user->perm->getAllRightsData(),
            'userIsSuperAdmin' => $this->currentUser->isSuperAdmin(),
        ];
    }
}
