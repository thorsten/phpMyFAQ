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

namespace phpMyFAQ\Controller\Frontend\Api;

use phpMyFAQ\Auth\AuthWebAuthn;
use phpMyFAQ\Auth\WebAuthn\WebAuthnUser;
use phpMyFAQ\Controller\AbstractController;
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
use Symfony\Component\Routing\Attribute\Route;

final class WebAuthnController extends AbstractController
{
    private readonly AuthWebAuthn $authWebAuthn;

    private readonly User $user;

    private readonly ?CurrentUser $loginCurrentUser;

    public function __construct(
        ?AuthWebAuthn $authWebAuthn = null,
        ?User $user = null,
        ?CurrentUser $loginCurrentUser = null,
    ) {
        parent::__construct();

        $this->authWebAuthn = $authWebAuthn ?? new AuthWebAuthn($this->configuration);
        $this->user = $user ?? new User($this->configuration);
        $this->loginCurrentUser = $loginCurrentUser;
    }

    /**
     * @throws RandomException|\JsonException
     * @throws \Exception
     */
    #[Route(path: 'webauthn/prepare', name: 'api.private.webauthn.prepare', methods: ['POST'])]
    public function prepare(Request $request): JsonResponse
    {
        if (!$this->configuration->get('security.enableWebAuthnSupport')) {
            return $this->json(['error' => Translation::get(key: 'msgNoPermission')], Response::HTTP_FORBIDDEN);
        }

        $data = json_decode($request->getContent(), associative: false, depth: 512, flags: JSON_THROW_ON_ERROR);

        if (!is_object($data) || !property_exists($data, 'username')) {
            throw new Exception('Missing username');
        }

        $username = Filter::filterVar($data->username, FILTER_SANITIZE_SPECIAL_CHARS, '');

        $userExists = (bool) $this->user->getUserByLogin($username, raiseError: false);

        if (!$userExists && !$this->configuration->get('security.enableRegistration')) {
            return $this->json(['error' => Translation::get(key: 'msgNoPermission')], Response::HTTP_FORBIDDEN);
        }

        // Verify the CSRF token on every path, regardless of whether the user exists.
        $csrfToken = Filter::filterVar($data->{'pmf-csrf-token'} ?? '', FILTER_SANITIZE_SPECIAL_CHARS);

        if (!Token::getInstance($this->session)->verifyToken('webauthn', $csrfToken)) {
            return $this->json(['error' => Translation::get(key: 'msgSessionExpired')], Response::HTTP_UNAUTHORIZED);
        }

        // The account already exists: only its authenticated owner may (re-)register a passkey.
        // This prevents an unauthenticated attacker from overwriting an existing user's passkeys.
        if ($userExists && !$this->currentUser->isLoggedIn()) {
            return $this->json(['error' => Translation::get(key: 'ad_msg_noauth')], Response::HTTP_UNAUTHORIZED);
        }

        if ($userExists && $this->currentUser->getUserId() !== $this->user->getUserId()) {
            return $this->json(['error' => Translation::get(key: 'msgNoPermission')], Response::HTTP_UNAUTHORIZED);
        }

        if (!$userExists) {
            if (!$this->captchaCodeIsValid($request)) {
                return $this->json(['error' => Translation::get(key: 'msgCaptcha')], Response::HTTP_BAD_REQUEST);
            }

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

        $challengeData = $this->authWebAuthn->prepareChallengeForRegistration(
            $username,
            (string) $this->user->getUserId(),
        );
        $b64Challenge = $challengeData['b64challenge'] ?? '';

        $webAuthnUser = new WebAuthnUser();
        $webAuthnUser
            ->setName($username)
            ->setId((string) $this->user->getUserId())
            ->setWebAuthnKeys(webAuthnKeys: '')
            ->setChallenge(is_string($b64Challenge) ? $b64Challenge : '');

        $this->authWebAuthn->storeUserInSession($webAuthnUser);

        return $this->json([
            'challenge' => $challengeData,
        ], Response::HTTP_OK);
    }

    /**
     * @throws Exception
     * @throws \JsonException
     */
    #[Route(path: 'webauthn/register', name: 'api.private.webauthn.register', methods: ['POST'])]
    public function register(Request $request): JsonResponse
    {
        if (!$this->configuration->get('security.enableWebAuthnSupport')) {
            return $this->json(['error' => Translation::get(key: 'msgNoPermission')], Response::HTTP_FORBIDDEN);
        }

        $data = json_decode($request->getContent(), associative: false, depth: 512, flags: JSON_THROW_ON_ERROR);

        if (!is_object($data) || !property_exists($data, 'register')) {
            throw new Exception('Missing register data');
        }

        $csrfToken = Filter::filterVar($data->{'pmf-csrf-token'} ?? '', FILTER_SANITIZE_SPECIAL_CHARS);
        if (!Token::getInstance($this->session)->verifyToken('webauthn', $csrfToken)) {
            return $this->json(['error' => Translation::get(key: 'msgSessionExpired')], Response::HTTP_UNAUTHORIZED);
        }

        $register = Filter::filterVar($data->register, FILTER_SANITIZE_SPECIAL_CHARS, '');

        $webAuthnUser = $this->authWebAuthn->getUserFromSession();

        if (!$webAuthnUser) {
            throw new Exception('User not found in session');
        }

        $webAuthnUser->setWebAuthnKeys($this->authWebAuthn->register(
            $register,
            $webAuthnUser->getWebAuthnKeys(),
            $webAuthnUser->getChallenge(),
        ));

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
    #[Route(path: 'webauthn/prepare-login', name: 'api.private.webauthn.prepare-login', methods: ['POST'])]
    public function prepareLogin(Request $request): JsonResponse
    {
        if (!$this->configuration->get('security.enableWebAuthnSupport')) {
            return $this->json(['error' => Translation::get(key: 'msgNoPermission')], Response::HTTP_FORBIDDEN);
        }

        $data = json_decode($request->getContent(), associative: false, depth: 512, flags: JSON_THROW_ON_ERROR);

        if (!is_object($data) || !property_exists($data, 'username')) {
            throw new Exception('Missing username');
        }

        $login = Filter::filterVar($data->username, FILTER_SANITIZE_SPECIAL_CHARS, '');

        try {
            $this->user->getUserByLogin($login);
        } catch (Exception) {
            return $this->json(['error' => Translation::get(key: 'ad_auth_fail')], Response::HTTP_BAD_REQUEST);
        }

        $webAuthnKeys = $this->user->getWebAuthnKeys();
        $publicKey = $this->authWebAuthn->prepareForLogin($webAuthnKeys);

        // prepareForLogin() stamps the pending challenge onto the keys; it has to be stored so the
        // login can check the assertion against it and reject replays.
        $this->user->setWebAuthnKeys($webAuthnKeys);

        return $this->json($publicKey, Response::HTTP_OK);
    }

    /**
     * @throws Exception
     * @throws \JsonException
     * @throws \Exception
     */
    #[Route(path: 'webauthn/login', name: 'api.private.webauthn.login', methods: ['POST'])]
    public function login(Request $request): JsonResponse
    {
        if (!$this->configuration->get('security.enableWebAuthnSupport')) {
            return $this->json(['error' => Translation::get(key: 'msgNoPermission')], Response::HTTP_FORBIDDEN);
        }

        $data = json_decode($request->getContent(), associative: false, depth: 512, flags: JSON_THROW_ON_ERROR);

        if (!is_object($data) || !property_exists($data, 'username')) {
            throw new Exception('Missing username');
        }

        if (!property_exists($data, 'login')) {
            throw new Exception('Missing login data');
        }

        $login = Filter::filterVar($data->username, FILTER_SANITIZE_SPECIAL_CHARS, '');
        $loginData = $data->login;
        if (!$loginData instanceof \stdClass) {
            throw new Exception('Missing login data');
        }

        $this->user->getUserByLogin($login);

        $webAuthnKeys = $this->user->getWebAuthnKeys();
        $isAuthenticated = $this->authWebAuthn->authenticate($loginData, $webAuthnKeys);

        // authenticate() blanks the challenge it just consumed. Store that, so the same assertion
        // cannot be presented a second time.
        $this->user->setWebAuthnKeys($webAuthnKeys);

        if ($isAuthenticated) {
            $currentUser = $this->loginCurrentUser ?? new CurrentUser($this->configuration);
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

                $this->session->set('2fa_pending_user_id', $currentUser->getUserId());
                // The WebAuthn login form has no remember-me option; the token step decides
                // cookie issuance, so carry an explicit "false" through it.
                $this->session->set('2fa_pending_remember_me', false);

                return $this->json([
                    'success' => 'ok',
                    'redirect' => $this->configuration->getDefaultUrl() . 'token?user-id=' . $currentUser->getUserId(),
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
