<?php

/**
 * The OAuth 2.0 Administration Controller
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

use phpMyFAQ\Auth\OAuth2\Repository\ClientRepository;
use phpMyFAQ\Auth\OAuth2\Repository\ScopeRepository;
use phpMyFAQ\Core\Exception;
use phpMyFAQ\Enums\ApiKeyScope;
use phpMyFAQ\Enums\OAuth2GrantType;
use phpMyFAQ\Enums\PermissionType;
use phpMyFAQ\Session\Token;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Twig\Error\LoaderError;

final class OAuth2Controller extends AbstractAdministrationController
{
    /**
     * The clients and scopes of the OAuth 2.0 authorization server.
     *
     * @throws LoaderError
     * @throws Exception
     * @throws \Exception
     */
    #[Route(path: '/oauth2', name: 'admin.oauth2', methods: ['GET'])]
    public function index(Request $request): Response
    {
        $this->userHasPermission(PermissionType::CONFIGURATION_EDIT);

        $token = Token::getInstance($this->session);

        return $this->render('@admin/configuration/oauth2.twig', [
            ...$this->getHeader($request),
            ...$this->getFooter(),
            'isOAuth2Enabled' => (bool) $this->configuration->get(item: 'oauth2.enable'),
            'clients' => $this->clients(),
            'scopes' => $this->scopes(),
            'grantTypes' => array_map(
                static fn(OAuth2GrantType $grant): string => $grant->value,
                OAuth2GrantType::cases(),
            ),
            'csrfTokenSaveClient' => $token->getTokenString('oauth2-client-save'),
            'csrfTokenDeleteClient' => $token->getTokenString('oauth2-client-delete'),
            'csrfTokenSaveScopes' => $token->getTokenString('oauth2-scopes-save'),
        ]);
    }

    /**
     * @return list<array{id: string, name: string, redirectUri: string, grants: list<string>, confidential: bool}>
     */
    private function clients(): array
    {
        $clients = [];
        foreach (new ClientRepository($this->configuration)->all() as $client) {
            $redirectUri = $client->getRedirectUri();

            $clients[] = [
                'id' => $client->getIdentifier(),
                'name' => $client->getName(),
                'redirectUri' => is_array($redirectUri) ? $redirectUri[0] ?? '' : $redirectUri,
                'grants' => array_values($client->allowedGrants),
                'confidential' => $client->isConfidential(),
            ];
        }

        return $clients;
    }

    /**
     * The scopes the REST API checks, followed by stored scopes it does not know (created by
     * hand before this page existed), each with whether clients may request it.
     *
     * @return list<array{id: string, description: string, enabled: bool, known: bool}>
     */
    private function scopes(): array
    {
        $stored = new ScopeRepository($this->configuration)->all();
        $known = array_map(static fn(ApiKeyScope $scope): string => $scope->value, ApiKeyScope::cases());

        $scopes = [];
        foreach ($known as $scope) {
            $scopes[] = [
                'id' => $scope,
                'description' => $stored[$scope] ?? '',
                'enabled' => array_key_exists($scope, $stored),
                'known' => true,
            ];
        }

        foreach ($stored as $scope => $description) {
            if (in_array($scope, $known, strict: true)) {
                continue;
            }

            $scopes[] = ['id' => $scope, 'description' => $description, 'enabled' => true, 'known' => false];
        }

        return $scopes;
    }
}
