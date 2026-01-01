<?php

/**
 * Contact Controller
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at https://mozilla.org/MPL/2.0/.
 *
 * @package   phpMyFAQ
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2002-2025 phpMyFAQ Team
 * @license   https://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2002-09-16
 */

declare(strict_types=1);

namespace phpMyFAQ\Controller\Frontend;

use Exception;
use phpMyFAQ\Translation;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class ContactController extends AbstractFrontController
{
    /**
     * Handles both GET and POST requests for the contact form
     * @throws Exception
     */
    #[Route(path: '/contact.html', name: 'public.contact')]
    public function index(Request $request): Response
    {
        $faqSession = $this->container->get('phpmyfaq.user.session');
        $faqSession->setCurrentUser($this->currentUser);
        $faqSession->userTracking('contact', 0);

        $captcha = $this->container->get('phpmyfaq.captcha');
        $captchaHelper = $this->container->get('phpmyfaq.captcha.helper.captcha_helper');

        if ($this->configuration->get('layout.contactInformationHTML')) {
            $contactText = html_entity_decode((string) $this->configuration->get('main.contactInformation'));
        } else {
            $contactText = nl2br((string) $this->configuration->get('main.contactInformation'));
        }

        return $this->render('contact.twig', [
            ...$this->getHeader($request),
            'title' => sprintf('%s - %s', Translation::get(key: 'msgContact'), $this->configuration->getTitle()),
            'msgContactOwnText' => $contactText,
            'privacyURL' => $this->configuration->get('main.privacyURL'),
            'lang' => $this->configuration->getLanguage()->getLanguage(),
            'defaultContentMail' => $this->currentUser->getUserId() > 0 ? $this->currentUser->getUserData('email') : '',
            'defaultContentName' => $this->currentUser->getUserId() > 0
                ? $this->currentUser->getUserData('display_name')
                : '',
            'version' => $this->configuration->getVersion(),
            'captchaFieldset' => $captchaHelper->renderCaptcha(
                $captcha,
                'contact',
                Translation::get(key: 'msgCaptcha'),
                $this->currentUser->isLoggedIn(),
            ),
        ]);
    }
}
