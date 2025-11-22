<?php

declare(strict_types=1);

/**
 * The WebAuthn Controller
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at https://mozilla.org/MPL/2.0/.
 *
 * @package   phpMyFAQ
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2024-2025 phpMyFAQ Team
 * @license   https://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2024-09-11
 */

namespace phpMyFAQ\Controller;

use phpMyFAQ\Core\Exception;
use phpMyFAQ\Environment;
use phpMyFAQ\Helper\LanguageHelper;
use phpMyFAQ\System;
use phpMyFAQ\Translation;
use phpMyFAQ\Twig\TwigWrapper;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Twig\Error\LoaderError;

final class WebAuthnController extends AbstractController
{
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * @throws Exception|LoaderError
     */
    #[Route(path: '/', name: 'public.webauthn.index')]
    public function index(Request $request): Response
    {
        $system = new System();

        $topNavigation = [
            [
                'name' => Translation::get(languageKey: 'msgShowAllCategories'),
                'link' => './show-categories.html',
                'active' => '',
            ],
            [
                'name' => Translation::get(languageKey: 'msgAddContent'),
                'link' => './add-faq.html',
                'active' => '',
            ],
            [
                'name' => Translation::get(languageKey: 'msgQuestion'),
                'link' => './add-question.html',
                'active' => '',
            ],
            [
                'name' => Translation::get(languageKey: 'msgOpenQuestions'),
                'link' => './open-questions.html',
                'active' => '',
            ],
        ];

        $footerNavigation = [
            [
                'name' => Translation::get(languageKey: 'faqOverview'),
                'link' => './overview.html',
                'active' => '',
            ],
            [
                'name' => Translation::get(languageKey: 'msgSitemap'),
                'link' => './sitemap/A/' . $this->configuration->getDefaultLanguage() . '.html',
                'active' => '',
            ],
            [
                'name' => Translation::get(languageKey: 'ad_menu_glossary'),
                'link' => './glossary.html',
                'active' => '',
            ],
            [
                'name' => Translation::get(languageKey: 'msgContact'),
                'link' => './contact.html',
                'active' => '',
            ],
        ];

        return $this->render('/webauthn.twig', [
            'isMaintenanceMode' => $this->configuration->get(item: 'main.maintenanceMode'),
            'isCompletelySecured' => $this->configuration->get(item: 'security.enableLoginOnly'),
            'isDebugEnabled' => Environment::isDebugMode(),
            'richSnippetsEnabled' => $this->configuration->get(item: 'seo.enableRichSnippets'),
            'tplSetName' => TwigWrapper::getTemplateSetName(),
            'msgLoginUser' => Translation::get(languageKey: 'msgLoginUser'),
            'isUserLoggedIn' => $this->currentUser->isLoggedIn(),
            'title' => Translation::get(languageKey: 'msgLoginUser'),
            'baseHref' => $system->getSystemUri($this->configuration),
            'customCss' => $this->configuration->getCustomCss(),
            'version' => $this->configuration->getVersion(),
            'header' => str_replace('"', '', $this->configuration->getTitle()),
            'metaPublisher' => $this->configuration->get(item: 'main.metaPublisher'),
            'metaLanguage' => Translation::get(languageKey: 'metaLanguage'),
            'phpmyfaqVersion' => $this->configuration->getVersion(),
            'stylesheet' => Translation::get(languageKey: 'direction') == 'rtl' ? 'style.rtl' : 'style',
            'currentPageUrl' => $request->getSchemeAndHttpHost() . $request->getRequestUri(),
            'dir' => Translation::get(languageKey: 'direction'),
            'searchBox' => Translation::get(languageKey: 'msgSearch'),
            'faqHome' => $this->configuration->getDefaultUrl(),
            'topNavigation' => $topNavigation,
            'footerNavigation' => $footerNavigation,
            'languageBox' => Translation::get(languageKey: 'msgLanguageSubmit'),
            'switchLanguages' => LanguageHelper::renderSelectLanguage($this->configuration->getDefaultLanguage(), true),
            'copyright' => System::getPoweredByString(true),
            'isUserRegistrationEnabled' => $this->configuration->get(item: 'security.enableRegistration'),
            'msgRegisterUser' => Translation::get(languageKey: 'msgRegisterUser'),
            'isPrivacyLinkEnabled' => $this->configuration->get(item: 'layout.enablePrivacyLink'),
            'urlPrivacyLink' => $this->configuration->get(item: 'main.privacyURL'),
            'msgPrivacyNote' => Translation::get(languageKey: 'msgPrivacyNote'),
            'isCookieConsentEnabled' => $this->configuration->get(item: 'layout.enableCookieConsent'),
            'cookiePreferences' => Translation::get(languageKey: 'cookiePreferences'),
        ]);
    }
}
