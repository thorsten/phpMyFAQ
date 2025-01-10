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
 * @copyright 2024 phpMyFAQ Team
 * @license   https://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2024-09-11
 */

namespace phpMyFAQ\Controller;

use phpMyFAQ\Core\Exception;
use phpMyFAQ\Helper\LanguageHelper;
use phpMyFAQ\System;
use phpMyFAQ\Template\TwigWrapper;
use phpMyFAQ\Translation;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class WebAuthnController extends AbstractController
{
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * @throws Exception
     */
    #[Route('/', name: 'public.webauthn.index')]
    public function index(Request $request): Response
    {
        $system = new System();

        $topNavigation = [
            [
                'name' => Translation::get('msgShowAllCategories'),
                'link' => './show-categories.html',
                'active' => '',
            ],
            [
                'name' => Translation::get('msgAddContent'),
                'link' => './add-faq.html',
                'active' => '',
            ],
            [
                'name' => Translation::get('msgQuestion'),
                'link' => './add-question.html',
                'active' => '',
            ],
            [
                'name' => Translation::get('msgOpenQuestions'),
                'link' => './open-questions.html',
                'active' => '',
            ],
        ];

        $footerNavigation = [
            [
                'name' => Translation::get('faqOverview'),
                'link' => './overview.html',
                'active' => '',
            ],
            [
                'name' => Translation::get('msgSitemap'),
                'link' => './sitemap/A/' . $this->configuration->getDefaultLanguage() . '.html',
                'active' => '',
            ],
            [
                'name' => Translation::get('ad_menu_glossary'),
                'link' => './glossary.html',
                'active' => '',
            ],
            [
                'name' => Translation::get('msgContact'),
                'link' => './contact.html',
                'active' => '',
            ],
        ];

        return $this->render(
            '/webauthn.twig',
            [
                'isMaintenanceMode' => $this->configuration->get('main.maintenanceMode'),
                'isCompletelySecured' => $this->configuration->get('security.enableLoginOnly'),
                'isDebugEnabled' => DEBUG,
                'richSnippetsEnabled' => $this->configuration->get('seo.enableRichSnippets'),
                'tplSetName' => TwigWrapper::getTemplateSetName(),
                'msgLoginUser' => Translation::get('msgLoginUser'),
                'isUserLoggedIn' => $this->currentUser->isLoggedIn(),
                'title' => Translation::get('msgLoginUser'),
                'baseHref' => $system->getSystemUri($this->configuration),
                'customCss' => $this->configuration->getCustomCss(),
                'version' => $this->configuration->getVersion(),
                'header' => str_replace('"', '', $this->configuration->getTitle()),
                'metaPublisher' => $this->configuration->get('main.metaPublisher'),
                'metaLanguage' => Translation::get('metaLanguage'),
                'phpmyfaqVersion' => $this->configuration->getVersion(),
                'stylesheet' => Translation::get('direction') == 'rtl' ? 'style.rtl' : 'style',
                'currentPageUrl' => $request->getSchemeAndHttpHost() . $request->getRequestUri(),
                'dir' => Translation::get('direction'),
                'searchBox' => Translation::get('msgSearch'),
                'faqHome' => $this->configuration->getDefaultUrl(),
                'topNavigation' => $topNavigation,
                'footerNavigation' => $footerNavigation,
                'languageBox' => Translation::get('msgLanguageSubmit'),
                'switchLanguages' =>
                    LanguageHelper::renderSelectLanguage($this->configuration->getDefaultLanguage(), true),
                'copyright' => System::getPoweredByString(true),
                'isUserRegistrationEnabled' => $this->configuration->get('security.enableRegistration'),
                'msgRegisterUser' => Translation::get('msgRegisterUser'),
                'isPrivacyLinkEnabled' => $this->configuration->get('layout.enablePrivacyLink'),
                'urlPrivacyLink' => $this->configuration->get('main.privacyURL'),
                'msgPrivacyNote' => Translation::get('msgPrivacyNote'),
                'isCookieConsentEnabled' => $this->configuration->get('layout.enableCookieConsent'),
                'cookiePreferences' => Translation::get('cookiePreferences')
            ]
        );
    }
}
