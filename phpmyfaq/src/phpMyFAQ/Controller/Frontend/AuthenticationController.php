<?php

/**
 * Authentication Controller to handle login, logout, and password reset
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at https://mozilla.org/MPL/2.0/.
 *
 * @package   phpMyFAQ
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2012-2026 phpMyFAQ Team
 * @license   https://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2012-02-12
 */

namespace phpMyFAQ\Controller\Frontend;

use phpMyFAQ\Core\Exception;
use phpMyFAQ\Filter;
use phpMyFAQ\Session\Token;
use phpMyFAQ\Translation;
use phpMyFAQ\User\UserAuthentication;
use phpMyFAQ\User\UserException;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Twig\Error\LoaderError;

final class AuthenticationController extends AbstractFrontController
{
    /**
     * @throws Exception
     * @throws LoaderError
     * @throws \Exception
     */ #[Route(path: '/login', name: 'public.auth.login', methods: ['GET'])]
    public function login(Request $request): Response
    {
        $faqSession = $this->container->get('phpmyfaq.user.session');
        $faqSession->setCurrentUser($this->currentUser);
        $faqSession->userTracking('login', 0);

        // Redirect to authenticate if SSO is enabled and the user is already authenticated
        if (
            $this->configuration->get(item: 'security.ssoSupport')
            && $request->server->get(key: 'REMOTE_USER') !== null
        ) {
            return new RedirectResponse(url: './authenticate');
        }

        $session = $this->container->get('session');
        $errorMessages = $session->getFlashBag()->get('error');
        $errorMessage = empty($errorMessages) ? null : $errorMessages[0];

        return $this->render('login.twig', [
            ...$this->getHeader($request),
            'title' => sprintf('%s - %s', Translation::get(key: 'msgLoginUser'), $this->configuration->getTitle()),
            'loginHeader' => Translation::get(key: 'msgLoginUser'),
            'errorMessage' => $errorMessage,
            'writeLoginPath' => $this->configuration->getDefaultUrl(),
            'login' => Translation::get(key: 'ad_auth_ok'),
            'username' => Translation::get(key: 'ad_auth_user'),
            'password' => Translation::get(key: 'ad_auth_passwd'),
            'rememberMe' => Translation::get(key: 'rememberMe'),
            'msgTwofactorEnabled' => Translation::get(key: 'msgTwofactorEnabled'),
            'msgTwofactorTokenModelTitle' => Translation::get(key: 'msgTwofactorTokenModelTitle'),
            'msgEnterTwofactorToken' => Translation::get(key: 'msgEnterTwofactorToken'),
            'msgTwofactorCheck' => Translation::get(key: 'msgTwofactorCheck'),
            'userid' => $this->currentUser->getUserId(),
            'enableRegistration' => $this->configuration->get('security.enableRegistration'),
            'registerUser' => Translation::get(key: 'msgRegistration'),
            'useSignInWithMicrosoft' => $this->configuration->isSignInWithMicrosoftActive(),
            'isWebAuthnEnabled' => $this->configuration->get('security.enableWebAuthnSupport'),
        ]);
    }

    /**
     * @throws Exception
     * @throws LoaderError
     * @throws \Exception
     */
    #[Route(path: '/forgot-password', name: 'public.forgot-password', methods: ['GET', 'POST'])]
    public function forgotPassword(Request $request): Response
    {
        $faqSession = $this->container->get('phpmyfaq.user.session');
        $faqSession->setCurrentUser($this->currentUser);
        $faqSession->userTracking('forgot_password', 0);

        return $this->render('password.twig', [
            ...$this->getHeader($request),
            'lang' => $this->configuration->getLanguage()->getLanguage(),
            'username' => Translation::get(key: 'ad_auth_user'),
            'password' => Translation::get(key: 'ad_auth_passwd'),
        ]);
    }

    /**
     * @throws \Exception
     */
    #[Route(path: '/logout', name: 'public.auth.logout', methods: ['GET'])]
    public function logout(Request $request): RedirectResponse
    {
        $session = $this->container->get('session');
        $csrfToken = Filter::filterVar($request->query->get('csrf'), FILTER_SANITIZE_SPECIAL_CHARS);

        $redirectResponse = new RedirectResponse(url: $this->configuration->getDefaultUrl());

        if (!Token::getInstance($this->container->get('session'))->verifyToken('logout', $csrfToken)) {
            $session->getFlashBag()->add('error', 'CSRF Problem detected: ' . $csrfToken);
            return $redirectResponse;
        }

        if (!$this->currentUser->isLoggedIn()) {
            return $redirectResponse;
        }

        $this->currentUser->deleteFromSession(true);

        // Add a success message
        $session->getFlashBag()->add('success', Translation::get('ad_logout'));

        // SSO Logout
        $ssoLogout = $this->configuration->get('security.ssoLogoutRedirect');
        if ($this->configuration->get('security.ssoSupport') && (string) $ssoLogout !== '') {
            $redirectResponse->isRedirect($ssoLogout);
            return $redirectResponse;
        }

        // Microsoft Azure Logout
        if (
            $this->configuration->isSignInWithMicrosoftActive()
            && $this->currentUser->getUserAuthSource() === 'azure'
        ) {
            return new RedirectResponse($this->configuration->getDefaultUrl() . 'services/azure/logout.php');
        }

        return $redirectResponse;
    }

    /**
     * Handles user authentication (login form submission)
     *
     * @throws \Exception
     */
    #[Route(path: '/authenticate', name: 'public.auth.authenticate', methods: ['POST'])]
    public function authenticate(Request $request): RedirectResponse
    {
        if ($this->currentUser->isLoggedIn()) {
            return new RedirectResponse(url: './');
        }

        $username = Filter::filterVar($request->request->get('faqusername'), FILTER_SANITIZE_SPECIAL_CHARS);
        $password = Filter::filterVar(
            $request->request->get('faqpassword'),
            FILTER_SANITIZE_SPECIAL_CHARS,
            FILTER_FLAG_NO_ENCODE_QUOTES,
        );
        $rememberMe = Filter::filterVar($request->request->get('faqrememberme'), FILTER_VALIDATE_BOOLEAN);

        // Set username via SSO
        if (
            $this->configuration->get(item: 'security.ssoSupport')
            && $request->server->get(key: 'REMOTE_USER') !== null
        ) {
            $username = trim((string) $request->server->get(key: 'REMOTE_USER'));
            $password = '';
        }

        // Login via local DB or LDAP or SSO
        if ($username !== '' && ($password !== '' || $this->configuration->get('security.ssoSupport'))) {
            $userAuthentication = new UserAuthentication($this->configuration, $this->currentUser);
            $userAuthentication->setRememberMe($rememberMe ?? false);
            try {
                $this->currentUser = $userAuthentication->authenticate($username, $password);

                // Check if two-factor authentication is enabled
                if ($userAuthentication->hasTwoFactorAuthentication()) {
                    return new RedirectResponse(url: './token?user-id=' . $this->currentUser->getUserId());
                }

                return new RedirectResponse('./');
            } catch (UserException $e) {
                $this->configuration->getLogger()->error('Login-error: ' . $e->getMessage());
                $this->container->get('session')->getFlashBag()->add('error', $e->getMessage());
                return new RedirectResponse('./login');
            }
        }

        $this->container->get('session')->getFlashBag()->add('error', Translation::get('ad_auth_fail'));
        return new RedirectResponse($this->configuration->getDefaultUrl() . 'login');
    }

    /**
     * Displays the two-factor authentication page
     *
     * @throws Exception
     * @throws LoaderError
     * @throws \Exception
     */
    #[Route(path: '/token', name: 'public.auth.token', methods: ['GET'])]
    public function token(Request $request): Response
    {
        if ($this->currentUser->isLoggedIn()) {
            return new RedirectResponse(url: './');
        }

        $faqSession = $this->container->get('phpmyfaq.user.session');
        $faqSession->setCurrentUser($this->currentUser);
        $faqSession->userTracking('twofactor', 0);

        $userId = (int) Filter::filterVar($request->query->get(key: 'user-id'), FILTER_VALIDATE_INT);

        return $this->render('twofactor.twig', [
            ...$this->getHeader($request),
            'title' => sprintf(
                '%s - %s',
                Translation::get(key: 'msgTwofactorEnabled'),
                $this->configuration->getTitle(),
            ),
            'msgTwofactorEnabled' => Translation::get(key: 'msgTwofactorEnabled'),
            'msgEnterTwofactorToken' => Translation::get(key: 'msgEnterTwofactorToken'),
            'msgTwofactorCheck' => Translation::get(key: 'msgTwofactorCheck'),
            'userId' => $userId,
        ]);
    }

    /**
     * Validates the two-factor authentication token
     *
     * @throws \Exception
     */
    #[Route(path: '/check', name: 'public.auth.check', methods: ['POST'])]
    public function check(Request $request): RedirectResponse
    {
        if ($this->currentUser->isLoggedIn()) {
            return new RedirectResponse(url: './');
        }

        $token = Filter::filterVar($request->request->get(key: 'token'), FILTER_SANITIZE_SPECIAL_CHARS);
        $userId = (int) Filter::filterVar($request->request->get(key: 'user-id'), FILTER_VALIDATE_INT);

        $user = $this->container->get(id: 'phpmyfaq.user.current_user');
        $user->getUserById($userId);

        if (strlen((string) $token) === 6) {
            $tfa = $this->container->get(id: 'phpmyfaq.user.two-factor');
            $result = $tfa->validateToken($token, $userId);

            if ($result) {
                $user->twoFactorSuccess();
                return new RedirectResponse(url: './');
            }
        }

        $this->session->getFlashBag()->add('error', Translation::get('msgTwofactorErrorToken'));
        return new RedirectResponse('./token?user-id=' . $userId);
    }
}
