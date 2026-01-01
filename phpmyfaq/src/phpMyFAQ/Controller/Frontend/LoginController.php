<?php

/**
 * Login Controller
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
use phpMyFAQ\Translation;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Twig\Error\LoaderError;

final class LoginController extends AbstractFrontController
{
    /**
     * @throws Exception
     * @throws LoaderError
     * @throws \Exception
     */ #[Route(path: '/login', name: 'public.login')]
    public function index(Request $request): Response
    {
        $faqSession = $this->container->get('phpmyfaq.user.session');
        $faqSession->setCurrentUser($this->currentUser);
        $faqSession->userTracking('login', 0);

        $session = $this->container->get('session');
        $errorMessages = $session->getFlashBag()->get('error');
        $errorMessage = !empty($errorMessages) ? $errorMessages[0] : null;

        return $this->render('login.twig', [
            ...$this->getHeader($request),
            'title' => sprintf('%s - %s', Translation::get(key: 'msgLoginUser'), $this->configuration->getTitle()),
            'loginHeader' => Translation::get(key: 'msgLoginUser'),
            'sendPassword' => Translation::get(key: 'lostPassword'),
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
    #[Route(path: '/forgot-password', name: 'public.forgot-password')]
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
}
