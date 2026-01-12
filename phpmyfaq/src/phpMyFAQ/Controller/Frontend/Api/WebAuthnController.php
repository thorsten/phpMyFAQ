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
        $data = json_decode($request->getContent(), associative: false, depth: 512, flags: JSON_THROW_ON_ERROR);

        if (!isset($data->username)) {
            throw new Exception('Missing username');
        }

        $username = Filter::filterVar($data->username, FILTER_SANITIZE_SPECIAL_CHARS);

        if (!$this->user->getUserByLogin($username, raiseError: false)) {
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

        $webAuthnUser = new WebAuthnUser();
        $webAuthnUser->setName($username)->setId((string) $this->user->getUserId())->setWebAuthnKeys(webAuthnKeys: '');

        $this->authWebAuthn->storeUserInSession($webAuthnUser);

        return $this->json([
            'challenge' => $this->authWebAuthn->prepareChallengeForRegistration(
                $username,
                (string) $this->user->getUserId(),
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
        $data = json_decode($request->getContent(), associative: false, depth: 512, flags: JSON_THROW_ON_ERROR);

        if (!isset($data->register)) {
            throw new Exception('Missing register data');
        }

        $register = Filter::filterVar($data->register, FILTER_SANITIZE_SPECIAL_CHARS);

        $webAuthnUser = $this->authWebAuthn->getUserFromSession();

        if (!$webAuthnUser) {
            throw new Exception('User not found in session');
        }

        $webAuthnUser->setWebAuthnKeys($this->authWebAuthn->register($register, $webAuthnUser->getWebAuthnKeys()));

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
        $data = json_decode($request->getContent(), associative: false, depth: 512, flags: JSON_THROW_ON_ERROR);

        if (!isset($data->username)) {
            throw new Exception('Missing username');
        }

        $login = Filter::filterVar($data->username, FILTER_SANITIZE_SPECIAL_CHARS);

        try {
            $this->user->getUserByLogin($login);
        } catch (Exception) {
            return $this->json(['error' => Translation::get(key: 'ad_auth_fail')], Response::HTTP_BAD_REQUEST);
        }

        $webAuthnKeys = $this->user->getWebAuthnKeys();

        return $this->json($this->authWebAuthn->prepareForLogin($webAuthnKeys), Response::HTTP_OK);
    }

    /**
     * @throws Exception
     * @throws \JsonException
     * @throws \Exception
     */
    #[Route(path: 'api/webauthn/login', name: 'api.private.webauthn.login', methods: ['POST'])]
    public function login(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), associative: false, depth: 512, flags: JSON_THROW_ON_ERROR);

        if (!isset($data->username)) {
            throw new Exception('Missing username');
        }

        if (!isset($data->login)) {
            throw new Exception('Missing login data');
        }

        $login = Filter::filterVar($data->username, FILTER_SANITIZE_SPECIAL_CHARS);
        $loginData = $data->login;

        $this->user->getUserByLogin($login);

        $webAuthnKeys = $this->user->getWebAuthnKeys();

        if ($this->authWebAuthn->authenticate($loginData, $webAuthnKeys)) {
            $currentUser = new CurrentUser($this->configuration);
            $currentUser->getUserByLogin($login);

            if ($currentUser->isBlocked()) {
                return $this->json(['error' => Translation::get(key: 'ad_auth_fail')], Response::HTTP_UNAUTHORIZED);
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
