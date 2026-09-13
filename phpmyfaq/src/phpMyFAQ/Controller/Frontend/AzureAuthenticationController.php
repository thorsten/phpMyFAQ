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
use phpMyFAQ\Auth\EntraId\JwksProvider;
use phpMyFAQ\Auth\EntraId\OAuth;
use phpMyFAQ\Enums\AuthenticationSourceType;
use phpMyFAQ\Filter;
use phpMyFAQ\Translation;
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
        $state = Filter::filterVar($request->query->get('state'), FILTER_SANITIZE_SPECIAL_CHARS, '');
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

        // The state ties this callback to the authorization request this browser started;
        // without it an attacker could complete a login with a code of their own account.
        if (!$auth->isValidState($state)) {
            $this->configuration->getLogger()->warning('Entra ID callback rejected: invalid OAuth state.');
            return $this->loginFailed();
        }

        try {
            $token = $oAuth->getOAuthToken($code);
            $accessToken = $token->access_token ?? null;
            $refreshToken = $token->refresh_token ?? null;
            $oAuth
                ->setToken($token)
                ->setAccessToken($accessToken === null ? null : (string) $accessToken)
                ->setRefreshToken($refreshToken === null ? null : (string) $refreshToken);

            if (!$auth->isValidLogin($oAuth->getMail())) {
                return $this->loginFailed();
            }

            if (!$auth->checkCredentials($oAuth->getMail(), '')) {
                return $this->loginFailed();
            }

            // Only the account the driver resolved for this identity may be logged in, and
            // getUserById() refuses blocked accounts and the anonymous user.
            $user = $this->getCurrentUserService();
            $userId = $auth->getAuthenticatedUserId();
            if ($userId <= 0 || !$user->getUserById($userId) || $user->getUserId() <= 0) {
                $this->configuration
                    ->getLogger()
                    ->warning(sprintf('Entra ID login rejected: local account #%d is not available.', $userId));
                return $this->loginFailed();
            }

            $user->setLoggedIn(true);
            $user->setAuthSource(AuthenticationSourceType::AUTH_AZURE->value);
            $user->updateSessionId(true);
            $user->saveToSession();
            $user->setTokenData([
                'refresh_token' => $oAuth->getRefreshToken() ?? '',
                'access_token' => $oAuth->getAccessToken() ?? '',
                'code_verifier' => (string) $entraIdSession->get(EntraIdSession::ENTRA_ID_OAUTH_VERIFIER),
                'jwt' => $oAuth->getToken(),
            ]);
            $user->setSuccess(true);

            return $redirect;
        } catch (TransportExceptionInterface|Exception $exception) {
            $this->configuration
                ->getLogger()
                ->error(sprintf(
                    'Entra ID Login failed: %s at line %d at %s',
                    $exception->getMessage(),
                    $exception->getLine(),
                    $exception->getFile(),
                ));

            return $this->loginFailed(Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Returns the generic, translated failure response; the reason is only logged.
     */
    private function loginFailed(int $status = Response::HTTP_FORBIDDEN): Response
    {
        $message = Translation::get('msgEntraIdLoginFailed');

        return new Response(is_string($message) ? $message : '', $status);
    }

    /**
     * @return array{0: AuthEntraId, 1: OAuth, 2: EntraIdSession}
     */
    protected function buildAuthContext(): array
    {
        if ($this->authContextFactory instanceof \Closure) {
            $authContext = ($this->authContextFactory)();
            $entraAuth = is_array($authContext) ? $authContext[0] ?? null : null;
            $entraOAuth = is_array($authContext) ? $authContext[1] ?? null : null;
            $entraSession = is_array($authContext) ? $authContext[2] ?? null : null;
            if (
                $entraAuth instanceof AuthEntraId
                && $entraOAuth instanceof OAuth
                && $entraSession instanceof EntraIdSession
            ) {
                return [$entraAuth, $entraOAuth, $entraSession];
            }
        }

        // Use a bridge session per request to preserve legacy Azure flow behavior.
        $session = new Session(new PhpBridgeSessionStorage());
        if (!$session->isStarted()) {
            $session->start();
        }

        $entraIdSession = new EntraIdSession($this->configuration, $session);
        $oAuth = new OAuth($this->configuration, $entraIdSession, new JwksProvider());
        $auth = new AuthEntraId($this->configuration, $oAuth);

        return [$auth, $oAuth, $entraIdSession];
    }

    protected function getCurrentUserService(): CurrentUser
    {
        if ($this->currentUserFactory instanceof \Closure) {
            $currentUser = ($this->currentUserFactory)();
            if ($currentUser instanceof CurrentUser) {
                return $currentUser;
            }
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

        require (string) PMF_CONFIG_DIR . '/azure.php';
    }
}
