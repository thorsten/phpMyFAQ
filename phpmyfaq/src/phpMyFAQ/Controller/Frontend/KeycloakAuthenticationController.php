<?php

/**
 * Authentication Controller for Keycloak.
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
 * @since     2026-04-18
 */

declare(strict_types=1);

namespace phpMyFAQ\Controller\Frontend;

use Closure;
use Exception;
use phpMyFAQ\Auth\AuthKeycloak;
use phpMyFAQ\Auth\Keycloak\KeycloakProviderConfigFactory;
use phpMyFAQ\Auth\Oidc\OidcClient;
use phpMyFAQ\Auth\Oidc\OidcDiscoveryService;
use phpMyFAQ\Auth\Oidc\OidcIdTokenValidator;
use phpMyFAQ\Auth\Oidc\OidcPkceGenerator;
use phpMyFAQ\Auth\Oidc\OidcSession;
use phpMyFAQ\Enums\AuthenticationSourceType;
use phpMyFAQ\Filter;
use phpMyFAQ\User;
use phpMyFAQ\User\CurrentUser;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class KeycloakAuthenticationController extends AbstractFrontController
{
    private ?Closure $currentUserFactory = null;
    private ?Closure $userFactory = null;

    public function __construct(
        private readonly KeycloakProviderConfigFactory $providerConfigFactory,
        private readonly OidcDiscoveryService $discoveryService,
        private readonly OidcPkceGenerator $pkceGenerator,
        private readonly OidcSession $oidcSession,
        private readonly OidcClient $oidcClient,
        private readonly OidcIdTokenValidator $idTokenValidator,
    ) {
        parent::__construct();
    }

    public function setCurrentUserFactory(?Closure $currentUserFactory): self
    {
        $this->currentUserFactory = $currentUserFactory;
        return $this;
    }

    public function setUserFactory(?Closure $userFactory): self
    {
        $this->userFactory = $userFactory;
        return $this;
    }

    #[Route(path: '/auth/keycloak/authorize', name: 'public.keycloak.authorize', methods: ['GET'])]
    public function authorize(): RedirectResponse
    {
        try {
            $providerConfig = $this->providerConfigFactory->create();
            if (!$providerConfig->enabled || $providerConfig->discoveryUrl === '') {
                return new RedirectResponse($this->configuration->getDefaultUrl());
            }

            $discoveryDocument = $this->discoveryService->discover($providerConfig);
            $state = bin2hex(random_bytes(16));
            $nonce = bin2hex(random_bytes(16));
            $verifier = $this->pkceGenerator->generateVerifier();
            $challenge = $this->pkceGenerator->generateChallenge($verifier);

            $this->oidcSession->setAuthorizationState($state, $nonce, $verifier);

            return new RedirectResponse($this->oidcClient->buildAuthorizationUrl(
                $providerConfig,
                $discoveryDocument,
                $state,
                $nonce,
                $challenge,
            ));
        } catch (Exception $exception) {
            $this->configuration
                ->getLogger()
                ->info(sprintf(
                    'Keycloak login failed: %s at line %d at %s',
                    $exception->getMessage(),
                    $exception->getLine(),
                    $exception->getFile(),
                ));

            return new RedirectResponse($this->configuration->getDefaultUrl());
        }
    }

    #[Route(path: '/auth/keycloak/logout', name: 'public.keycloak.logout', methods: ['GET'])]
    public function logout(): RedirectResponse
    {
        try {
            $providerConfig = $this->providerConfigFactory->create();
            if (!$providerConfig->enabled || $providerConfig->discoveryUrl === '') {
                return new RedirectResponse($this->configuration->getDefaultUrl());
            }

            $idToken = $this->oidcSession->getIdToken();

            $this->currentUser->deleteFromSession();
            $this->oidcSession->clearIdToken();

            $discoveryDocument = $this->discoveryService->discover($providerConfig);
            $logoutUrl = $this->oidcClient->buildLogoutUrl($providerConfig, $discoveryDocument, $idToken);

            return new RedirectResponse($logoutUrl ?? $this->configuration->getDefaultUrl());
        } catch (Exception) {
            $this->currentUser->deleteFromSession();
            return new RedirectResponse($this->configuration->getDefaultUrl());
        }
    }

    /**
     * @throws Exception
     */
    #[Route(path: '/auth/keycloak/callback', name: 'public.keycloak.callback', methods: ['GET'])]
    public function callback(Request $request): Response
    {
        $providerConfig = $this->providerConfigFactory->create();
        if (!$providerConfig->enabled || $providerConfig->discoveryUrl === '') {
            return new RedirectResponse($this->configuration->getDefaultUrl());
        }

        $code = Filter::filterVar($request->query->get('code'), FILTER_SANITIZE_SPECIAL_CHARS, '');
        $state = Filter::filterVar($request->query->get('state'), FILTER_SANITIZE_SPECIAL_CHARS, '');
        $error = Filter::filterVar($request->query->get('error_description'), FILTER_SANITIZE_SPECIAL_CHARS, '');

        if ($error !== '') {
            $this->configuration->getLogger()->warning(sprintf('Keycloak callback error: %s', $error));
            return new RedirectResponse($this->configuration->getDefaultUrl());
        }

        $redirect = new RedirectResponse($this->configuration->getDefaultUrl());
        $authorizationState = $this->oidcSession->getAuthorizationState();

        if (
            $code === ''
            || $state === ''
            || $authorizationState['state'] === ''
            || !hash_equals($authorizationState['state'], $state)
        ) {
            $this->oidcSession->clearAuthorizationState();
            return $redirect;
        }

        try {
            $discoveryDocument = $this->discoveryService->discover($providerConfig);
            $token = $this->oidcClient->exchangeAuthorizationCode(
                $providerConfig,
                $discoveryDocument,
                $code,
                $authorizationState['verifier'],
            );
            $this->idTokenValidator->validate(
                (string) ($token['id_token'] ?? ''),
                $discoveryDocument,
                $providerConfig->client->clientId,
                $authorizationState['nonce'],
            );
            $claims = $this->oidcClient->fetchUserInfo($discoveryDocument, (string) $token['access_token']);
            $login = $this->resolveLocalLogin($claims);
            $auth = new AuthKeycloak($this->configuration, $providerConfig, $claims, $login, $this->userFactory);

            if (!$auth->isValidLogin($login)) {
                $this->configuration->getLogger()->warning(sprintf('Keycloak login not valid for user: %s', $login));
                $this->oidcSession->clearAuthorizationState();
                return $redirect;
            }

            if (!$auth->checkCredentials($login, '')) {
                $this->configuration
                    ->getLogger()
                    ->warning(sprintf('Keycloak credentials not valid for user: %s', $login));
                $this->oidcSession->clearAuthorizationState();
                return $redirect;
            }

            $user = $this->getCurrentUserService();
            if (!$user->getUserByLogin($login)) {
                $this->configuration
                    ->getLogger()
                    ->warning(sprintf('Keycloak user lookup failed for login: %s', $login));
                $this->oidcSession->clearAuthorizationState();
                return $redirect;
            }

            if (!$this->synchronizeKeycloakSubject($user, $claims)) {
                $this->configuration->getLogger()->warning(sprintf('Keycloak subject mismatch for user: %s', $login));
                $this->oidcSession->clearAuthorizationState();
                return $redirect;
            }

            $user->setLoggedIn(true);
            $user->setAuthSource(AuthenticationSourceType::AUTH_KEYCLOAK->value);
            $user->updateSessionId(true);
            $user->saveToSession();
            $user->setTokenData([
                'refresh_token' => (string) ($token['refresh_token'] ?? ''),
                'access_token' => (string) $token['access_token'],
                'code_verifier' => $authorizationState['verifier'],
                'jwt' => [
                    'id_token' => (string) ($token['id_token'] ?? ''),
                    'userinfo' => $claims,
                ],
            ]);
            $user->setSuccess(true);
            $this->oidcSession->clearAuthorizationState();
            $this->oidcSession->setIdToken((string) ($token['id_token'] ?? ''));

            return $redirect;
        } catch (Exception $exception) {
            $this->configuration->getLogger()->error(sprintf('Keycloak login failed: %s', $exception->getMessage()), [
                'exception' => $exception,
            ]);
            $this->oidcSession->clearAuthorizationState();

            return new RedirectResponse($this->configuration->getDefaultUrl());
        }
    }

    /** @param array<string, mixed> $claims */
    private function resolveLocalLogin(array $claims): string
    {
        $preferredUsername = trim((string) ($claims['preferred_username'] ?? ''));
        $email = trim((string) ($claims['email'] ?? ''));
        $subject = trim((string) ($claims['sub'] ?? ''));

        if ($subject !== '') {
            $user = $this->createUser();
            $userId = $user->getUserIdByKeycloakSub($subject);
            if ($userId > 0 && $user->getUserById($userId)) {
                return $user->getLogin();
            }
        }

        if ($preferredUsername !== '' && $this->createUser()->getUserByLogin($preferredUsername, false)) {
            return $preferredUsername;
        }

        if ($email !== '') {
            $user = $this->createUser();
            $userId = $user->getUserIdByEmail($email);
            if ($userId > 0 && $user->getUserById($userId)) {
                return $user->getLogin();
            }
        }

        if ($preferredUsername !== '') {
            return $preferredUsername;
        }

        if ($email !== '') {
            return $email;
        }

        return $subject;
    }

    /** @param array<string, mixed> $claims */
    private function synchronizeKeycloakSubject(CurrentUser $user, array $claims): bool
    {
        $subject = trim((string) ($claims['sub'] ?? ''));
        if ($subject === '') {
            return true;
        }

        $linkedSubject = trim((string) $user->getUserData('keycloak_sub'));
        if ($linkedSubject !== '' && !hash_equals($linkedSubject, $subject)) {
            return false;
        }

        if ($linkedSubject === '') {
            return $user->setUserData(['keycloak_sub' => $subject]);
        }

        return true;
    }

    private function getCurrentUserService(): CurrentUser
    {
        if ($this->currentUserFactory instanceof Closure) {
            return ($this->currentUserFactory)();
        }

        return $this->currentUser;
    }

    private function createUser(): User
    {
        if ($this->userFactory instanceof Closure) {
            return ($this->userFactory)();
        }

        return new User($this->configuration);
    }
}
