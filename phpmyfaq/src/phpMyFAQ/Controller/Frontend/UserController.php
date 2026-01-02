<?php

/**
 * User Controller
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at https://mozilla.org/MPL/2.0/.
 *
 * @package   phpMyFAQ
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @author    Jan Harms <model_railroader@gmx-topmail.de>
 * @copyright 2008-2026 phpMyFAQ Team
 * @license   https://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2008-01-25
 */

declare(strict_types=1);

namespace phpMyFAQ\Controller\Frontend;

use phpMyFAQ\Bookmark;
use phpMyFAQ\Core\Exception;
use phpMyFAQ\Session\Token;
use phpMyFAQ\Translation;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class UserController extends AbstractFrontController
{
    /**
     * Displays the request removal page.
     *
     * @throws Exception
     * @throws \Exception
     */
    #[Route(path: '/user/request-removal', name: 'public.user.request-removal')]
    public function requestRemoval(Request $request): Response
    {
        if (!$this->currentUser->isLoggedIn()) {
            return new RedirectResponse($this->configuration->getDefaultUrl());
        }

        $faqSession = $this->container->get('phpmyfaq.user.session');
        $faqSession->setCurrentUser($this->currentUser);
        $faqSession->userTracking('request_removal', 0);

        return $this->render('request-removal.twig', [
            ...$this->getHeader($request),
            'privacyURL' => $this->configuration->get('main.privacyURL'),
            'csrf' => Token::getInstance($this->container->get('session'))->getTokenInput('request-removal'),
            'lang' => $this->configuration->getLanguage()->getLanguage(),
            'userId' => $this->currentUser->getUserId(),
            'defaultContentMail' => $this->currentUser->getUserId() > 0 ? $this->currentUser->getUserData('email') : '',
            'defaultContentName' => $this->currentUser->getUserId() > 0
                ? $this->currentUser->getUserData('display_name')
                : '',
            'defaultLoginName' => $this->currentUser->getUserId() > 0 ? $this->currentUser->getLogin() : '',
        ]);
    }

    /**
     * Displays the user's bookmarks page.
     *
     * @throws Exception
     * @throws \Exception
     */
    #[Route(path: '/user/bookmarks', name: 'public.user.bookmarks')]
    public function bookmarks(Request $request): Response
    {
        if (!$this->currentUser->isLoggedIn()) {
            return new RedirectResponse($this->configuration->getDefaultUrl());
        }

        $faqSession = $this->container->get('phpmyfaq.user.session');
        $faqSession->setCurrentUser($this->currentUser);
        $faqSession->userTracking('bookmarks', 0);

        $bookmark = new Bookmark($this->configuration, $this->currentUser);
        $session = $this->container->get('session');

        return $this->render('bookmarks.twig', [
            ...$this->getHeader($request),
            'title' => sprintf('%s - %s', Translation::get(key: 'msgBookmarks'), $this->configuration->getTitle()),
            'bookmarksList' => $bookmark->getBookmarkList(),
            'csrfTokenDeleteBookmark' => Token::getInstance($session)->getTokenString('delete-bookmark'),
            'csrfTokenDeleteAllBookmarks' => Token::getInstance($session)->getTokenString('delete-all-bookmarks'),
        ]);
    }

    /**
     * Displays the user registration page.
     *
     * @throws Exception
     * @throws \Exception
     */
    #[Route(path: '/user/register', name: 'public.user.register')]
    public function register(Request $request): Response
    {
        if (!$this->configuration->get('security.enableRegistration')) {
            return new RedirectResponse($this->configuration->getDefaultUrl());
        }

        $faqSession = $this->container->get('phpmyfaq.user.session');
        $faqSession->setCurrentUser($this->currentUser);
        $faqSession->userTracking('registration', 0);

        $captcha = $this->container->get('phpmyfaq.captcha');
        $captchaHelper = $this->container->get('phpmyfaq.captcha.helper.captcha_helper');

        return $this->render('register.twig', [
            ...$this->getHeader($request),
            'title' => sprintf('%s - %s', Translation::get(key: 'msgRegistration'), $this->configuration->getTitle()),
            'lang' => $this->configuration->getLanguage()->getLanguage(),
            'isWebAuthnEnabled' => $this->configuration->get('security.enableWebAuthnSupport'),
            'captchaFieldset' => $captchaHelper->renderCaptcha(
                $captcha,
                'register',
                Translation::get(key: 'msgCaptcha'),
                $this->currentUser->isLoggedIn(),
            ),
        ]);
    }
}
