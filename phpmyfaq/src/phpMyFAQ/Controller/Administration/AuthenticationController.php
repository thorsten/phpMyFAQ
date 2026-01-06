<?php

/**
 * The Administration Authentication Controller
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
 * @since     2024-12-28
 */

declare(strict_types=1);

namespace phpMyFAQ\Controller\Administration;

use phpMyFAQ\Core\Exception;
use phpMyFAQ\Enums\AdminLogType;
use phpMyFAQ\Filter;
use phpMyFAQ\Session\Token;
use phpMyFAQ\Translation;
use phpMyFAQ\User\UserAuthentication;
use phpMyFAQ\User\UserException;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class AuthenticationController extends AbstractAdministrationController
{
    #[Route(path: '/authenticate', name: 'admin.auth.authenticate', methods: ['POST'])]
    public function authenticate(Request $request): RedirectResponse
    {
        if ($this->currentUser->isLoggedIn()) {
            return new RedirectResponse(url: './');
        }

        $username = Filter::filterVar($request->request->get(key: 'faqusername'), FILTER_SANITIZE_SPECIAL_CHARS);
        $password = Filter::filterVar(
            $request->request->get(key: 'faqpassword'),
            FILTER_SANITIZE_SPECIAL_CHARS,
            FILTER_FLAG_NO_ENCODE_QUOTES,
        );
        $rememberMe = Filter::filterVar($request->request->get(key: 'faqrememberme'), FILTER_VALIDATE_BOOLEAN);

        // Set username via SSO
        if (
            $this->configuration->get(item: 'security.ssoSupport')
            && $request->server->get(key: 'REMOTE_USER') !== null
        ) {
            $username = trim((string) $request->server->get(key: 'REMOTE_USER'));
            $password = '';
        }

        // Login via local DB or LDAP or SSO
        if ($username !== '' && ($password !== '' || $this->configuration->get(item: 'security.ssoSupport'))) {
            $userAuthentication = new UserAuthentication($this->configuration, $this->currentUser);
            $userAuthentication->setRememberMe($rememberMe ?? false);
            try {
                $this->currentUser = $userAuthentication->authenticate($username, $password);
                if ($userAuthentication->hasTwoFactorAuthentication()) {
                    $this->adminLog->log(
                        $this->currentUser,
                        AdminLogType::AUTH_LOGIN_SUCCESS->value . ' (2FA required):' . $username,
                    );
                    return new RedirectResponse(url: './token?user-id=' . $this->currentUser->getUserId());
                }

                $this->adminLog->log($this->currentUser, AdminLogType::AUTH_LOGIN_SUCCESS->value . ':' . $username);
            } catch (Exception) {
                $this->adminLog->log(
                    $this->currentUser,
                    AdminLogType::AUTH_LOGIN_FAILED->value . ':' . $username . ' - '
                        . implode(separator: ', ', array: $this->currentUser->errors),
                );
                return new RedirectResponse(url: './login');
            }
        }

        return new RedirectResponse(url: './');
    }

    /**
     * @throws UserException
     * @throws Exception
     * @throws \Exception
     */
    #[Route(path: '/login', name: 'admin.auth.logout', methods: ['GET'])]
    public function login(Request $request): Response
    {
        // Redirect to authenticate if SSO is enabled and the user is already authenticated
        if (
            $this->configuration->get(item: 'security.ssoSupport')
            && $request->server->get(key: 'REMOTE_USER') !== null
        ) {
            return new RedirectResponse(url: './authenticate');
        }

        return $this->render(file: '@admin/login.twig', context: [
            ...$this->getHeader($request),
            ...$this->getFooter(),
            'isSecure' => $request->isSecure() || !$this->configuration->get(item: 'security.useSslForLogins'),
            'isError' => isset($error) && (string) $error !== '',
            'errorMessage' => 'to be implemented',
            'loginMessage' => Translation::get(key: 'ad_auth_insert'),
            'isLogout' => $request->query->get(key: 'action') === 'logout',
            'logoutMessage' => Translation::get(key: 'ad_logout'),
            'loginUrl' => $this->configuration->getDefaultUrl() . 'admin/authenticate',
            'redirectAction' => $request->query->get(key: 'action') ?? '',
            'msgUsername' => Translation::get(key: 'ad_auth_user'),
            'msgPassword' => Translation::get(key: 'ad_auth_passwd'),
            'msgRememberMe' => Translation::get(key: 'rememberMe'),
            'msgLostPassword' => Translation::get(key: 'lostPassword'),
            'msgLoginUser' => Translation::get(key: 'msgLoginUser'),
            'hasRegistrationEnabled' => $this->configuration->get(item: 'security.enableRegistration'),
            'msgRegistration' => Translation::get(key: 'msgRegistration'),
            'hasSignInWithMicrosoftActive' => $this->configuration->isSignInWithMicrosoftActive(),
            'msgSignInWithMicrosoft' => Translation::get(key: 'msgSignInWithMicrosoft'),
            'secureUrl' => preg_replace(pattern: '/^http:/', replacement: 'https:', subject: $request->getUri()),
            'msgNotSecure' => Translation::get(key: 'msgSecureSwitch'),
            'isWebAuthnEnabled' => $this->configuration->get(item: 'security.enableWebAuthnSupport'),
        ]);
    }

    /**
     * @throws \Exception
     */
    #[Route(path: '/logout', name: 'admin.auth.logout', methods: ['GET'])]
    public function logout(Request $request): RedirectResponse
    {
        $this->userIsAuthenticated();

        $redirectResponse = new RedirectResponse(url: $this->configuration->getDefaultUrl() . 'admin/login');

        $csrfToken = Filter::filterVar($request->query->get(key: 'csrf'), FILTER_SANITIZE_SPECIAL_CHARS);

        if (!Token::getInstance($this->session)->verifyToken(page: 'admin-logout', requestToken: $csrfToken)) {
            // @todo add an error message
            return $redirectResponse->send();
        }

        $this->adminLog->log(
            $this->currentUser,
            AdminLogType::AUTH_LOGOUT->value . ':' . $this->currentUser->getLogin(),
        );

        $this->currentUser->deleteFromSession(deleteCookie: true);
        $ssoLogout = $this->configuration->get(item: 'security.ssoLogoutRedirect');
        if ($this->configuration->get(item: 'security.ssoSupport') && (string) $ssoLogout !== '') {
            $redirectResponse->isRedirect($ssoLogout);
            $redirectResponse->send();
        }

        return $redirectResponse->send();
    }

    /**
     * @throws \Exception
     */
    #[Route(path: '/token', name: 'admin.auth.token', methods: ['GET'])]
    public function token(Request $request): Response
    {
        if ($this->currentUser->isLoggedIn()) {
            return new RedirectResponse(url: './');
        }

        $userId = (int) Filter::filterVar($request->query->get(key: 'user-id'), FILTER_VALIDATE_INT);

        return $this->render(file: '@admin/user/twofactor.twig', context: [
            ...$this->getHeader($request),
            ...$this->getFooter(),
            'msgTwofactorEnabled' => Translation::get(key: 'msgTwofactorEnabled'),
            'msgTwofactorCheck' => Translation::get(key: 'msgTwofactorCheck'),
            'msgEnterTwofactorToken' => Translation::get(key: 'msgEnterTwofactorToken'),
            'requestIsSecure' => $request->isSecure(),
            'security.useSslForLogins' => $this->configuration->get(item: 'security.useSslForLogins'),
            'requestHost' => $request->getHost(),
            'requestUri' => $request->getRequestUri(),
            'userId' => $userId,
            'msgSecureSwitch' => Translation::get(key: 'msgSecureSwitch'),
            'systemUri' => $this->configuration->getDefaultUrl(),
        ]);
    }

    /**
     * @throws \Exception
     */
    #[Route(path: '/check', name: 'admin.auth.check', methods: ['POST'])]
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
                $this->adminLog->log($user, AdminLogType::AUTH_2FA_SUCCESS->value . ':' . $user->getLogin());
                return new RedirectResponse(url: './');
            }

            $this->adminLog->log($user, AdminLogType::AUTH_2FA_FAILED->value . ':' . $user->getLogin());
        }

        return new RedirectResponse('./token?user-id=' . $userId);
    }
}
