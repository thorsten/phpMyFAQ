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
 * @copyright 2024-2025 phpMyFAQ Team
 * @license   https://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2024-09-11
 */

declare(strict_types=1);

namespace phpMyFAQ\Controller;

use phpMyFAQ\Core\Exception;
use phpMyFAQ\Environment;
use phpMyFAQ\Helper\LanguageHelper;
use phpMyFAQ\System;
use phpMyFAQ\Translation;
use phpMyFAQ\Twig\TwigWrapper;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Twig\Error\LoaderError;

final class WebAuthnController extends AbstractController
{
    /**
     * @throws Exception|LoaderError
     */
    #[Route(path: '/services/webauthn', name: 'public.webauthn.index')]
    public function index(Request $request): Response
    {
        $system = new System();

        $topNavigation = [
            [
                'name' => Translation::get(key: 'msgShowAllCategories'),
                'link' => './show-categories.html',
                'active' => '',
            ],
            [
                'name' => Translation::get(key: 'msgAddContent'),
                'link' => './add-faq.html',
                'active' => '',
            ],
            [
                'name' => Translation::get(key: 'msgQuestion'),
                'link' => './add-question.html',
                'active' => '',
            ],
            [
                'name' => Translation::get(key: 'msgOpenQuestions'),
                'link' => './open-questions.html',
                'active' => '',
            ],
        ];

        $footerNavigation = [
            [
                'name' => Translation::get(key: 'faqOverview'),
                'link' => './overview.html',
                'active' => '',
            ],
            [
                'name' => Translation::get(key: 'msgSitemap'),
                'link' => './sitemap/A/' . $this->configuration->getDefaultLanguage() . '.html',
                'active' => '',
            ],
            [
                'name' => Translation::get(key: 'ad_menu_glossary'),
                'link' => './glossary.html',
                'active' => '',
            ],
            [
                'name' => Translation::get(key: 'msgContact'),
                'link' => './contact.html',
                'active' => '',
            ],
        ];

        return $this->render(file: '/webauthn.twig', context: [
            'isMaintenanceMode' => $this->configuration->get(item: 'main.maintenanceMode'),
            'isCompletelySecured' => $this->configuration->get(item: 'security.enableLoginOnly'),
            'isDebugEnabled' => Environment::isDebugMode(),
            'richSnippetsEnabled' => $this->configuration->get(item: 'seo.enableRichSnippets'),
            'tplSetName' => TwigWrapper::getTemplateSetName(),
            'msgLoginUser' => Translation::get(key: 'msgLoginUser'),
            'isUserLoggedIn' => $this->currentUser->isLoggedIn(),
            'title' => Translation::get(key: 'msgLoginUser'),
            'baseHref' => $system->getSystemUri($this->configuration),
            'customCss' => $this->configuration->getCustomCss(),
            'version' => $this->configuration->getVersion(),
            'header' => str_replace(search: '"', replace: '', subject: $this->configuration->getTitle()),
            'metaPublisher' => $this->configuration->get(item: 'main.metaPublisher'),
            'metaLanguage' => Translation::get(key: 'metaLanguage'),
            'phpmyfaqVersion' => $this->configuration->getVersion(),
            'stylesheet' => Translation::get(key: 'direction') === 'rtl' ? 'style.rtl' : 'style',
            'currentPageUrl' => $request->getSchemeAndHttpHost() . $request->getRequestUri(),
            'dir' => Translation::get(key: 'direction'),
            'searchBox' => Translation::get(key: 'msgSearch'),
            'faqHome' => $this->configuration->getDefaultUrl(),
            'topNavigation' => $topNavigation,
            'footerNavigation' => $footerNavigation,
            'languageBox' => Translation::get(key: 'msgLanguageSubmit'),
            'switchLanguages' => LanguageHelper::renderSelectLanguage(
                $this->configuration->getDefaultLanguage(),
                submitOnChange: true,
            ),
            'copyright' => System::getPoweredByString(),
            'isUserRegistrationEnabled' => $this->configuration->get(item: 'security.enableRegistration'),
            'msgRegisterUser' => Translation::get(key: 'msgRegisterUser'),
            'isPrivacyLinkEnabled' => $this->configuration->get(item: 'layout.enablePrivacyLink'),
            'urlPrivacyLink' => $this->configuration->get(item: 'main.privacyURL'),
            'msgPrivacyNote' => Translation::get(key: 'msgPrivacyNote'),
            'isCookieConsentEnabled' => $this->configuration->get(item: 'layout.enableCookieConsent'),
            'cookiePreferences' => Translation::get(key: 'cookiePreferences'),
        ]);
    }
}
