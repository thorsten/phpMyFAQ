<?php

declare(strict_types=1);

namespace phpMyFAQ\Controller\Administration;

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

class AuthenticationController extends AbstractAdministrationController
{
    #[Route('/authenticate', name: 'admin.auth.authenticate', methods: ['POST'])]
    public function authenticate(Request $request): Response
    {
        if ($this->currentUser->isLoggedIn()) {
            return new RedirectResponse('./');
        }

        $logging = $this->container->get('phpmyfaq.admin.admin-log');

        $username = Filter::filterVar($request->get('faqusername'), FILTER_SANITIZE_SPECIAL_CHARS);
        $password = Filter::filterVar(
            $request->get('faqpassword'),
            FILTER_SANITIZE_SPECIAL_CHARS,
            FILTER_FLAG_NO_ENCODE_QUOTES
        );
        $rememberMe = Filter::filterVar($request->get('faqrememberme'), FILTER_VALIDATE_BOOLEAN);

        // Set username via SSO
        if ($this->configuration->get('security.ssoSupport') && $request->server->get('REMOTE_USER') !== null) {
            $username = trim((string) $request->server->get('REMOTE_USER'));
            $password = '';
        }

        // Login via local DB or LDAP or SSO
        if ($username !== '' && ($password !== '' || $this->configuration->get('security.ssoSupport'))) {
            $userAuth = new UserAuthentication($this->configuration, $this->currentUser);
            $userAuth->setRememberMe($rememberMe ?? false);
            try {
                $this->currentUser = $userAuth->authenticate($username, $password);
                if ($userAuth->hasTwoFactorAuthentication()) {
                    return new RedirectResponse('./2fa');
                }
            } catch (Exception $e) {
                $logging->log(
                    $this->currentUser,
                    'Login-error\nLogin: ' . $username . '\nErrors: ' . implode(', ', $this->configuration->errors)
                );
                //$error = $e->getMessage();
                return new RedirectResponse('./login');
            }
        }

        return new RedirectResponse('./');
    }

    /**
     * @throws UserException
     * @throws Exception
     * @throws \Exception
     */
    #[Route('/login', name: 'admin.auth.logout', methods: ['GET'])]
    public function login(Request $request): Response
    {
        // Redirect to authenticate if SSO is enabled and the user is already authenticated
        if ($this->configuration->get('security.ssoSupport') && $request->server->get('REMOTE_USER') !== null) {
            return new RedirectResponse('./authenticate');
        }

        return $this->render(
            '@admin/login.twig',
            [
                ... $this->getHeader($request),
                ... $this->getFooter(),
                'isSecure' => $request->isSecure() || !$this->configuration->get('security.useSslForLogins'),
                'isError' => isset($error) && 0 < strlen((string) $error),
                'errorMessage' => 'to be implemented',
                'loginMessage' => Translation::get('ad_auth_insert'),
                'isLogout' => $request->query->get('action') === 'logout',
                'logoutMessage' => Translation::get('ad_logout'),
                'loginUrl' => $this->configuration->getDefaultUrl() . 'admin/authenticate',
                'redirectAction' => $request->query->get('action') ?? '' ,
                'msgUsername' => Translation::get('ad_auth_user'),
                'msgPassword' => Translation::get('ad_auth_passwd'),
                'msgRememberMe' => Translation::get('rememberMe'),
                'msgLostPassword' => Translation::get('lostPassword'),
                'msgLoginUser' => Translation::get('msgLoginUser'),
                'hasRegistrationEnabled' => $this->configuration->get('security.enableRegistration'),
                'msgRegistration' => Translation::get('msgRegistration'),
                'hasSignInWithMicrosoftActive' => $this->configuration->isSignInWithMicrosoftActive(),
                'msgSignInWithMicrosoft' => Translation::get('msgSignInWithMicrosoft'),
                'secureUrl' => sprintf('https://%s%s', $request->getHost(), $request->getRequestUri()),
                'msgNotSecure' => Translation::get('msgSecureSwitch'),
                'isWebAuthnEnabled' => $this->configuration->get('security.enableWebAuthnSupport'),
            ]
        );
    }

    /**
     * @throws \Exception
     */
    #[Route('/logout', name: 'admin.auth.logout', methods: ['GET'])]
    public function logout(Request $request): Response
    {
        $this->userIsAuthenticated();

        $redirect = new RedirectResponse('./');

        $csrfToken = Filter::filterVar($request->get('csrf'), FILTER_SANITIZE_SPECIAL_CHARS);
        if (!Token::getInstance($this->container->get('session'))->verifyToken('admin-logout', $csrfToken)) {
            return $redirect->send();
        }

        $this->currentUser->deleteFromSession(true);
        $ssoLogout = $this->configuration->get('security.ssoLogoutRedirect');
        if ($this->configuration->get('security.ssoSupport') && !empty($ssoLogout)) {
            $redirect->isRedirect($ssoLogout);
            $redirect->send();
        }

        return $redirect->send();
    }
}
