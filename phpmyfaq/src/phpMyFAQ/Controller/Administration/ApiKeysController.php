<?php

/**
 * The API Keys Administration Controller
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at https://mozilla.org/MPL/2.0/.
 *
 * @package   phpMyFAQ
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2026 phpMyFAQ Team
 * @license   https://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2026-11-22
 */

declare(strict_types=1);

namespace phpMyFAQ\Controller\Administration;

use phpMyFAQ\Core\Exception;
use phpMyFAQ\Enums\ApiKeyScope;
use phpMyFAQ\Enums\PermissionType;
use phpMyFAQ\Session\Token;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Twig\Error\LoaderError;

final class ApiKeysController extends AbstractAdministrationController
{
    /**
     * The API keys of the logged-in user. The list itself is loaded from the administration API.
     *
     * @throws LoaderError
     * @throws Exception
     * @throws \Exception
     */
    #[Route(path: '/user/api-keys', name: 'admin.user.api-keys', methods: ['GET'])]
    public function index(Request $request): Response
    {
        $this->userHasPermission(PermissionType::USER_EDIT);

        $token = Token::getInstance($this->session);

        return $this->render('@admin/user/api-keys.twig', [
            ...$this->getHeader($request),
            ...$this->getFooter(),
            'grantableScopes' => $this->grantableScopes(),
            'csrfTokenCreate' => $token->getTokenString('api-key-create'),
            'csrfTokenUpdate' => $token->getTokenString('api-key-update'),
            'csrfTokenDelete' => $token->getTokenString('api-key-delete'),
        ]);
    }

    /**
     * A key acts on behalf of its owner: only the scopes whose permission the owner holds can be
     * granted, so no other scope is offered.
     *
     * @return list<string>
     */
    private function grantableScopes(): array
    {
        $userId = $this->currentUser->getUserId();
        $isSuperAdmin = $this->currentUser->isSuperAdmin();

        $scopes = [];
        foreach (ApiKeyScope::cases() as $scope) {
            $mayGrant =
                $isSuperAdmin || $this->currentUser->perm->hasPermission($userId, $scope->requiredPermission()->value);

            if (!$mayGrant) {
                continue;
            }

            $scopes[] = $scope->value;
        }

        return $scopes;
    }
}
