<?php

/**
 * Authentication Controller for Microsoft Entra ID
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at https://mozilla.org/MPL/2.0/.
 *
 * @package   phpMyFAQ
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2022-2026 phpMyFAQ Team
 * @license   https://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2026-02-22
 */

declare(strict_types=1);

namespace phpMyFAQ\Controller\Frontend;

use Exception;
use phpMyFAQ\Auth\AuthEntraId;
use phpMyFAQ\Auth\EntraId\EntraIdSession;
use phpMyFAQ\Auth\EntraId\OAuth;
use phpMyFAQ\Enums\AuthenticationSourceType;
use phpMyFAQ\Filter;
use phpMyFAQ\User\CurrentUser;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\Storage\PhpBridgeSessionStorage;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;

final class AzureAuthenticationController extends AbstractFrontController
{
    public function __construct(
        private readonly ?\Closure $authContextFactory = null,
        private readonly ?\Closure $currentUserFactory = null,
        private readonly ?\Closure $azureConfigLoader = null,
    ) {
        parent::__construct();
    }

    #[Route(path: '/auth/azure/authorize', name: 'public.azure.authorize', methods: ['GET'])]
    #[Route(path: '/services/azure/authorize', name: 'public.azure.authorize_legacy_services', methods: ['GET'])]
    public function authorize(): RedirectResponse
    {
        $this->loadAzureConfiguration();

        try {
            [$auth] = $this->buildAuthContext();
            return $auth->authorize();
        } catch (Exception $exception) {
            $this->configuration
                ->getLogger()
                ->info(sprintf(
                    'Entra ID Login failed: %s at line %d at %s',
                    $exception->getMessage(),
                    $exception->getLine(),
                    $exception->getFile(),
                ));

            return new RedirectResponse($this->configuration->getDefaultUrl());
        }
    }

    #[Route(path: '/auth/azure/logout', name: 'public.azure.logout', methods: ['GET'])]
    #[Route(path: '/services/azure/logout', name: 'public.azure.logout_legacy_services', methods: ['GET'])]
    public function logout(): RedirectResponse
    {
        $this->loadAzureConfiguration();

        [$auth] = $this->buildAuthContext();
        return $auth->logout();
    }

    /**
     * @throws Exception
     */
    #[Route(path: '/auth/azure/callback', name: 'public.azure.callback', methods: ['GET'])]
    #[Route(path: '/services/azure/callback', name: 'public.azure.callback_legacy_services', methods: ['GET'])]
    #[Route(path: '/services/azure/callback.php', name: 'public.azure.callback_legacy_php', methods: ['GET'])]
    public function callback(Request $request): Response
    {
        $this->loadAzureConfiguration();

        [$auth, $oAuth, $entraIdSession] = $this->buildAuthContext();

        $code = Filter::filterVar($request->query->get('code'), FILTER_SANITIZE_SPECIAL_CHARS, '');
        $errorParam = Filter::filterVar($request->query->get('error'), FILTER_SANITIZE_SPECIAL_CHARS, '');
        $error = Filter::filterVar($request->query->get('error_description'), FILTER_SANITIZE_SPECIAL_CHARS, '');

        if ($errorParam !== '' || $error !== '') {
            $this->configuration
                ->getLogger()
                ->warning(sprintf(
                    'Azure callback error: %s',
                    trim($errorParam . ($error !== '' ? ': ' . $error : '')),
                ));
            return new RedirectResponse($this->configuration->getDefaultUrl());
        }

        $redirect = new RedirectResponse($this->configuration->getDefaultUrl());

        if (!$entraIdSession->getCurrentSessionKey()) {
            return $redirect;
        }

        try {
            $token = $oAuth->getOAuthToken($code);
            $oAuth->setToken($token)->setAccessToken($token->access_token)->setRefreshToken($token->refresh_token);

            if (!$auth->isValidLogin($oAuth->getMail())) {
                return new Response('Login not valid.');
            }

            if (!$auth->checkCredentials($oAuth->getMail(), '')) {
                return new Response('Credentials not valid.');
            }

            $user = $this->getCurrentUserService();
            $user->getUserByLogin($oAuth->getMail());
            $user->setLoggedIn(true);
            $user->setAuthSource(AuthenticationSourceType::AUTH_AZURE->value);
            $user->updateSessionId(true);
            $user->saveToSession();
            $user->setTokenData([
                'refresh_token' => $oAuth->getRefreshToken(),
                'access_token' => $oAuth->getAccessToken(),
                'code_verifier' => $entraIdSession->get(EntraIdSession::ENTRA_ID_OAUTH_VERIFIER),
                'jwt' => $oAuth->getToken(),
            ]);
            $user->setSuccess(true);

            return $redirect;
        } catch (TransportExceptionInterface|Exception $exception) {
            return new Response(sprintf(
                'Entra ID Login failed: %s at line %d at %s',
                $exception->getMessage(),
                $exception->getLine(),
                $exception->getFile(),
            ));
        }
    }

    /**
     * @return array{0: AuthEntraId, 1: OAuth, 2: EntraIdSession}
     */
    protected function buildAuthContext(): array
    {
        if ($this->authContextFactory instanceof \Closure) {
            return ($this->authContextFactory)();
        }

        // Use a bridge session per request to preserve legacy Azure flow behavior.
        $session = new Session(new PhpBridgeSessionStorage());
        if (!$session->isStarted()) {
            $session->start();
        }

        $entraIdSession = new EntraIdSession($this->configuration, $session);
        $oAuth = new OAuth($this->configuration, $entraIdSession);
        $auth = new AuthEntraId($this->configuration, $oAuth);

        return [$auth, $oAuth, $entraIdSession];
    }

    protected function getCurrentUserService(): CurrentUser
    {
        if ($this->currentUserFactory instanceof \Closure) {
            return ($this->currentUserFactory)();
        }

        return $this->currentUser;
    }

    protected function loadAzureConfiguration(): void
    {
        if ($this->azureConfigLoader instanceof \Closure) {
            ($this->azureConfigLoader)();
            return;
        }

        if (defined('AAD_OAUTH_CLIENTID')) {
            return;
        }

        require PMF_CONFIG_DIR . '/azure.php';
    }
}
