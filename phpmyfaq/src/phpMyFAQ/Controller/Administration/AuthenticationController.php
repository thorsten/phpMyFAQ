<?php

declare(strict_types=1);

namespace phpMyFAQ\Controller\Administration;

use phpMyFAQ\Filter;
use phpMyFAQ\Session\Token;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class AuthenticationController extends AbstractAdministrationController
{
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
