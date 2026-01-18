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
use phpMyFAQ\User\TwoFactor;
use RobThree\Auth\TwoFactorAuthException;
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
    #[Route(path: '/user/request-removal', name: 'public.user.request-removal', methods: ['GET'])]
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
    #[Route(path: '/user/bookmarks', name: 'public.user.bookmarks', methods: ['GET'])]
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
    #[Route(path: '/user/register', name: 'public.user.register', methods: ['GET'])]
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

    /**
     * Displays the User Control Panel.
     *
     * @throws Exception
     * @throws \Exception
     */
    #[Route(path: '/user/ucp', name: 'public.user.ucp', methods: ['GET'])]
    public function ucp(Request $request): Response
    {
        if (!$this->currentUser->isLoggedIn()) {
            return new RedirectResponse($this->configuration->getDefaultUrl());
        }

        $faqSession = $this->container->get('phpmyfaq.user.session');
        $faqSession->setCurrentUser($this->currentUser);
        $faqSession->userTracking('user_control_panel', $this->currentUser->getUserId());

        if ($this->configuration->get('main.enableGravatarSupport')) {
            $gravatar = $this->container->get('phpmyfaq.services.gravatar');
            $gravatarImg = sprintf('<a target="_blank" href="https://www.gravatar.com">%s</a>', $gravatar->getImage(
                $this->currentUser->getUserData('email'),
                ['class' => 'img-responsive rounded-circle', 'size' => 125],
            ));
        } else {
            $gravatarImg = '';
        }

        $qrCode = '';
        $secret = '';
        try {
            $twoFactor = new TwoFactor($this->configuration, $this->currentUser);
            $secret = $twoFactor->getSecret($this->currentUser);
            if ('' === $secret || is_null($secret)) {
                try {
                    $secret = $twoFactor->generateSecret();
                } catch (TwoFactorAuthException $exception) {
                    $this->configuration->getLogger()->error('Cannot generate 2FA secret: ' . $exception->getMessage());
                }

                $twoFactor->saveSecret($secret);
            }

            $qrCode = $twoFactor->getQrCode($secret);
        } catch (TwoFactorAuthException|\Exception $exception) {
            $this->configuration->getLogger()->error('2FA error: ' . $exception->getMessage());
        }

        $session = $this->container->get('session');

        return $this->render('ucp.twig', [
            ...$this->getHeader($request),
            'headerUserControlPanel' => Translation::get(key: 'headerUserControlPanel'),
            'ucpGravatarImage' => $gravatarImg,
            'msgHeaderUserData' => Translation::get(key: 'headerUserControlPanel'),
            'userid' => $this->currentUser->getUserId(),
            'csrf' => Token::getInstance($session)->getTokenInput('ucp'),
            'lang' => $this->configuration->getLanguage()->getLanguage(),
            'readonly' => $this->currentUser->isLocalUser() ? '' : 'readonly disabled',
            'msgRealName' => Translation::get(key: 'ad_user_name'),
            'realname' => $this->currentUser->getUserData('display_name'),
            'msgEmail' => Translation::get(key: 'msgNewContentMail'),
            'email' => $this->currentUser->getUserData('email'),
            'msgIsVisible' => Translation::get(key: 'msgUserDataVisible'),
            'checked' => (int) $this->currentUser->getUserData('is_visible') === 1 ? 'checked' : '',
            'msgPassword' => Translation::get(key: 'ad_auth_passwd'),
            'msgConfirm' => Translation::get(key: 'ad_user_confirm'),
            'msgSave' => Translation::get(key: 'msgSave'),
            'msgCancel' => Translation::get(key: 'msgCancel'),
            'twofactor_enabled' => (bool) $this->currentUser->getUserData('twofactor_enabled'),
            'msgTwofactorEnabled' => Translation::get(key: 'msgTwofactorEnabled'),
            'msgTwofactorConfig' => Translation::get(key: 'msgTwofactorConfig'),
            'msgTwofactorConfigModelTitle' => Translation::get(key: 'msgTwofactorConfigModelTitle'),
            'twofactor_secret' => $secret,
            'qr_code_secret' => $qrCode,
            'qr_code_secret_alt' => Translation::get(key: 'qr_code_secret_alt'),
            'msgTwofactorNewSecret' => Translation::get(key: 'msgTwofactorNewSecret'),
            'msgWarning' => Translation::get(key: 'msgWarning'),
            'ad_gen_yes' => Translation::get(key: 'ad_gen_yes'),
            'ad_gen_no' => Translation::get(key: 'ad_gen_no'),
            'msgConfirmTwofactorConfig' => Translation::get(key: 'msgConfirmTwofactorConfig'),
            'csrfTokenRemoveTwofactor' => Token::getInstance($session)->getTokenString('remove-twofactor'),
            'msgGravatarNotConnected' => Translation::get(key: 'msgGravatarNotConnected'),
            'webauthnSupportEnabled' => $this->configuration->get('security.enableWebAuthnSupport'),
            'csrfExportUserData' => Token::getInstance($session)->getTokenInput('export-userdata'),
            'exportUserDataUrl' => 'api/user/data/export',
            'msgDownloadYourData' => Translation::get(key: 'msgDownloadYourData'),
            'msgDataExportDescription' => Translation::get(key: 'msgDataExportDescription'),
            'msgDownload' => Translation::get(key: 'msgDownload'),
        ]);
    }
}
