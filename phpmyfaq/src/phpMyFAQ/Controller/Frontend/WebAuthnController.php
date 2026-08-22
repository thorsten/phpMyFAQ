<?php

/**
 * The WebAuthn Controller
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
 * @since     2024-09-11
 */

declare(strict_types=1);

namespace phpMyFAQ\Controller\Frontend;

use phpMyFAQ\Auth\AuthWebAuthn;
use phpMyFAQ\Auth\WebAuthn\WebAuthnUser;
use phpMyFAQ\Controller\AbstractController;
use phpMyFAQ\Controller\Administration\SkipsAuthenticationCheck;
use phpMyFAQ\Core\Exception;
use phpMyFAQ\Enums\AuthenticationSourceType;
use phpMyFAQ\Filter;
use phpMyFAQ\Session\Token;
use phpMyFAQ\Translation;
use phpMyFAQ\User;
use phpMyFAQ\User\CurrentUser;
use Random\RandomException;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

final class WebAuthnController extends AbstractController implements SkipsAuthenticationCheck
{
    private readonly AuthWebAuthn $authWebAuthn;

    private readonly User $user;

    public function __construct()
    {
        parent::__construct();

        $this->authWebAuthn = new AuthWebAuthn($this->configuration);
        $this->user = new User($this->configuration);
    }

    /**
     * @throws RandomException|\JsonException
     * @throws \Exception
     */
    #[Route(path: 'api/webauthn/prepare', name: 'api.private.webauthn.prepare', methods: ['POST'])]
    public function prepare(Request $request): JsonResponse
    {
        if (!$this->configuration->get(item: 'security.enableWebAuthnSupport')) {
            return $this->json(['error' => 'WebAuthn support is disabled.'], Response::HTTP_FORBIDDEN);
        }

        if (!$this->configuration->get(item: 'security.enableRegistration')) {
            return $this->json(['error' => 'User registration is disabled.'], Response::HTTP_FORBIDDEN);
        }

        $data = json_decode($request->getContent(), associative: false, depth: 512, flags: JSON_THROW_ON_ERROR);

        $csrfToken = Filter::filterVar($data->csrfToken ?? '', FILTER_SANITIZE_SPECIAL_CHARS);
        if (!Token::getInstance($this->container->get(id: 'session'))->verifyToken('webauthn-prepare', $csrfToken)) {
            return $this->json(['error' => Translation::get(key: 'err_NotAuth')], Response::HTTP_UNAUTHORIZED);
        }

        $username = Filter::filterVar($data->username, FILTER_SANITIZE_SPECIAL_CHARS);

        $userExists = $this->user->getUserByLogin($username, raiseError: false);

        // Adding a passkey to an existing account is only allowed for the authenticated owner of
        // that account; otherwise an anonymous caller could attach a credential to any account
        // (e.g. admin) and take it over. A non-existing username is a passwordless sign-up and is
        // created below.
        if (
            $userExists
            && (
                !$this->currentUser instanceof CurrentUser
                || !$this->currentUser->isLoggedIn()
                || $this->currentUser->getUserId() !== $this->user->getUserId()
            )
        ) {
            return $this->json(['error' => Translation::get(key: 'err_NotAuth')], Response::HTTP_UNAUTHORIZED);
        }

        if (!$userExists) {
            try {
                $this->user->createUser($username);
                $this->user->setStatus(status: 'active');
                $this->user->setAuthSource(AuthenticationSourceType::AUTH_WEB_AUTHN->value);
                $this->user->setUserData([
                    'display_name' => $username,
                    'email' => $username,
                ]);
            } catch (\Exception $e) {
                return $this->json(['error' => $e->getMessage()], Response::HTTP_BAD_REQUEST);
            }
        }

        $challenge = $this->authWebAuthn->prepareChallengeForRegistration($username, (string) $this->user->getUserId());

        $webAuthnUser = new WebAuthnUser();
        $webAuthnUser
            ->setName($username)
            ->setId((string) $this->user->getUserId())
            ->setWebAuthnKeys(webAuthnKeys: '')
            ->setChallenge($challenge['b64challenge']);

        $this->authWebAuthn->storeUserInSession($webAuthnUser);

        return $this->json([
            'challenge' => $challenge,
            'csrfToken' => Token::getInstance($this->container->get(id: 'session'))->getTokenString(
                'webauthn-register',
            ),
        ], Response::HTTP_OK);
    }

    /**
     * @throws Exception
     * @throws \JsonException
     */
    #[Route(path: 'api/webauthn/register', name: 'api.private.webauthn.register', methods: ['POST'])]
    public function register(Request $request): JsonResponse
    {
        if (!$this->configuration->get(item: 'security.enableWebAuthnSupport')) {
            return $this->json(['error' => 'WebAuthn support is disabled.'], Response::HTTP_FORBIDDEN);
        }

        $data = json_decode($request->getContent(), associative: false, depth: 512, flags: JSON_THROW_ON_ERROR);

        $csrfToken = Filter::filterVar($data->csrfToken ?? '', FILTER_SANITIZE_SPECIAL_CHARS);
        if (!Token::getInstance($this->container->get(id: 'session'))->verifyToken('webauthn-register', $csrfToken)) {
            return $this->json(['error' => Translation::get(key: 'err_NotAuth')], Response::HTTP_UNAUTHORIZED);
        }

        $register = Filter::filterVar($data->register, FILTER_SANITIZE_SPECIAL_CHARS);

        $webAuthnUser = $this->authWebAuthn->getUserFromSession();
        if (!$webAuthnUser instanceof WebAuthnUser) {
            return $this->json(['error' => Translation::get(key: 'err_NotAuth')], Response::HTTP_UNAUTHORIZED);
        }

        try {
            $webAuthnKeys = $this->authWebAuthn->register(
                $register,
                $webAuthnUser->getWebAuthnKeys(),
                $webAuthnUser->getChallenge(),
            );
        } catch (Exception $exception) {
            return $this->json(['error' => $exception->getMessage()], Response::HTTP_BAD_REQUEST);
        }

        $webAuthnUser->setWebAuthnKeys($webAuthnKeys);

        // Burn the registration challenge so the ceremony cannot be replayed against this session.
        $webAuthnUser->setChallenge('');
        $this->authWebAuthn->storeUserInSession($webAuthnUser);

        try {
            $this->user->getUserByLogin($webAuthnUser->getName());
        } catch (Exception) {
            return $this->json(['error' => Translation::get(key: 'ad_auth_fail')], Response::HTTP_BAD_REQUEST);
        }

        if ($this->user->setWebAuthnKeys($webAuthnUser->getWebAuthnKeys())) {
            return $this->json([
                'success' => 'ok',
                'message' => Translation::get(key: 'msgPasskeyRegistrationSuccess'),
            ], Response::HTTP_OK);
        }

        return $this->json(['error' => 'Cannot set WebAuthn keys'], Response::HTTP_BAD_REQUEST);
    }

    /**
     * @throws \JsonException
     * @throws RandomException
     */
    #[Route(path: 'api/webauthn/prepare-login', name: 'api.private.webauthn.prepare-login', methods: ['POST'])]
    public function prepareLogin(Request $request): JsonResponse
    {
        if (!$this->configuration->get(item: 'security.enableWebAuthnSupport')) {
            return $this->json(['error' => 'WebAuthn support is disabled.'], Response::HTTP_FORBIDDEN);
        }

        $data = json_decode($request->getContent(), associative: false, depth: 512, flags: JSON_THROW_ON_ERROR);
        $login = Filter::filterVar($data->username, FILTER_SANITIZE_SPECIAL_CHARS);

        try {
            $this->user->getUserByLogin($login);
        } catch (Exception) {
            return $this->json(['error' => Translation::get(key: 'ad_auth_fail')], Response::HTTP_BAD_REQUEST);
        }

        $webAuthnKeys = $this->user->getWebAuthnKeys();
        $publicKey = $this->authWebAuthn->prepareForLogin($webAuthnKeys);

        // prepareForLogin() stamps a fresh single-use challenge onto the stored keys. It has to be
        // persisted, otherwise login() re-reads keys without a challenge and the replay check in
        // authenticate() has nothing to compare against.
        $this->user->setWebAuthnKeys($webAuthnKeys);

        return $this->json($publicKey, Response::HTTP_OK);
    }

    /**
     * @throws Exception
     * @throws \JsonException
     * @throws \Exception
     */
    #[Route(path: 'api/webauthn/login', name: 'api.private.webauthn.login', methods: ['POST'])]
    public function login(Request $request): JsonResponse
    {
        if (!$this->configuration->get(item: 'security.enableWebAuthnSupport')) {
            return $this->json(['error' => 'WebAuthn support is disabled.'], Response::HTTP_FORBIDDEN);
        }

        $data = json_decode($request->getContent(), associative: false, depth: 512, flags: JSON_THROW_ON_ERROR);
        $login = Filter::filterVar($data->username, FILTER_SANITIZE_SPECIAL_CHARS);
        $loginData = $data->login;

        $this->user->getUserByLogin($login);

        $webAuthnKeys = $this->user->getWebAuthnKeys();

        $isAuthenticated = $this->authWebAuthn->authenticate($loginData, $webAuthnKeys);

        // authenticate() clears the challenge on the stored keys. Persisting it here is what makes
        // the challenge single-use and the assertion non-replayable.
        $this->user->setWebAuthnKeys($webAuthnKeys);

        if ($isAuthenticated) {
            $currentUser = new CurrentUser($this->configuration);
            $currentUser->getUserByLogin($login);

            if ($currentUser->isBlocked()) {
                return $this->json(['error' => Translation::get(key: 'ad_auth_fail')], Response::HTTP_UNAUTHORIZED);
            }

            // A passkey is sufficient as the sole factor only for passwordless accounts. If the
            // account has TOTP two-factor enabled, the passkey counts as the first factor only:
            // defer to the token step instead of granting the session, mirroring the password
            // login flow, so a passkey cannot bypass two-factor authentication.
            if ((int) $currentUser->getUserData('twofactor_enabled') === 1) {
                if ($currentUser->isTwoFactorLockedOut()) {
                    return $this->json(['error' => Translation::get(key: 'ad_auth_fail')], Response::HTTP_UNAUTHORIZED);
                }

                $session = $this->container->get(id: 'session');
                $session->set('2fa_pending_user_id', $currentUser->getUserId());
                // The WebAuthn login form has no remember-me option; the token step decides
                // cookie issuance, so carry an explicit "false" through it.
                $session->set('2fa_pending_remember_me', false);

                return $this->json([
                    'success' => 'ok',
                    'redirect' =>
                        $this->configuration->getDefaultUrl() . 'admin/token?user-id=' . $currentUser->getUserId(),
                ], Response::HTTP_OK);
            }

            $currentUser->setLoggedIn(loggedIn: true);
            $currentUser->setSuccess(success: true);
            $currentUser->updateSessionId(updateLastLogin: true);
            $currentUser->saveToSession();
            return $this->json([
                'success' => 'ok',
                'redirect' => $this->configuration->getDefaultUrl(),
            ], Response::HTTP_OK);
        }

        return $this->json(['error' => Translation::get(key: 'ad_auth_fail')], Response::HTTP_UNAUTHORIZED);
    }
}
