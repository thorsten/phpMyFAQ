<?php

/**
 * The abstract Front Controller
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
 * @since     2024-06-16
 */

declare(strict_types=1);

namespace phpMyFAQ\Controller\Frontend;

use phpMyFAQ\Controller\AbstractController;
use phpMyFAQ\Core\Exception;
use phpMyFAQ\Enums\PermissionType;
use phpMyFAQ\Environment;
use phpMyFAQ\Helper\LanguageHelper;
use phpMyFAQ\System;
use phpMyFAQ\Translation;
use phpMyFAQ\Twig\TwigWrapper;
use Symfony\Component\HttpFoundation\Request;

abstract class AbstractFrontController extends AbstractController
{
    /**
     * @return string[]
     * @throws Exception
     * @throws \Exception
     */
    protected function getHeader(Request $request): array
    {
        $faqSystem = $this->container->get(id: 'phpmyfaq.system');
        $seo = $this->container->get(id: 'phpmyfaq.seo');
        $action = $request->query->get(key: 'action', default: 'index');

        $isUserHasAdminRights = $this->currentUser->perm->hasPermission(
            $this->currentUser->getUserId(),
            PermissionType::VIEW_ADMIN_LINK->value,
        );

        return [
            'isMaintenanceMode' => $this->configuration->get('main.maintenanceMode'),
            'isCompletelySecured' => $this->configuration->get('security.enableLoginOnly'),
            'isDebugEnabled' => Environment::isDebugMode(),
            'richSnippetsEnabled' => $this->configuration->get('seo.enableRichSnippets'),
            'tplSetName' => TwigWrapper::getTemplateSetName(),
            'msgLoginUser' => $this->currentUser->isLoggedIn()
                ? $this->currentUser->getUserData('display_name')
                : Translation::get(key: 'msgLoginUser'),
            'isUserLoggedIn' => $this->currentUser->isLoggedIn(),
            'isUserHasAdminRights' => $isUserHasAdminRights || $this->currentUser->isSuperAdmin(),
            'baseHref' => $faqSystem->getSystemUri($this->configuration),
            'customCss' => $this->configuration->getCustomCss(),
            'version' => $this->configuration->getVersion(),
            'header' => str_replace('"', '', $this->configuration->getTitle()),
            'metaDescription' => $metaDescription ?? $this->configuration->get('seo.description'),
            'metaPublisher' => $this->configuration->get('main.metaPublisher'),
            'metaLanguage' => Translation::get(key: 'metaLanguage'),
            'metaRobots' => $seo->getMetaRobots($action),
            'phpmyfaqVersion' => $this->configuration->getVersion(),
            'stylesheet' => Translation::get(key: 'direction') == 'rtl' ? 'style.rtl' : 'style',
            'currentPageUrl' => $request->getSchemeAndHttpHost() . $request->getRequestUri(),
            'action' => $action,
            'dir' => Translation::get(key: 'direction'),
            'formActionUrl' => './search',
            'searchBox' => Translation::get(key: 'msgSearch'),
            'languageBox' => Translation::get(key: 'msgLanguageSubmit'),
            'switchLanguages' => LanguageHelper::renderSelectLanguage(
                $this->configuration->getLanguage()->getLanguage(),
                true,
            ),
            'copyright' => System::getPoweredByString(),
            'isUserRegistrationEnabled' => $this->configuration->get('security.enableRegistration'),
            'pluginStylesheets' => $this->configuration->getPluginManager()->getAllPluginStylesheets(),
            'pluginScripts' => $this->configuration->getPluginManager()->getAllPluginScripts(),
            'msgRegisterUser' => Translation::get(key: 'msgRegisterUser'),
            'sendPassword' =>
                '<a href="'
                . $faqSystem->getSystemUri($this->configuration)
                . 'forgot-password">'
                . Translation::get(key: 'lostPassword')
                . '</a>',
            'msgFullName' => Translation::get(key: 'ad_user_loggedin') . $this->currentUser->getLogin(),
            'msgLoginName' => $this->currentUser->getUserData('display_name'),
            'loginHeader' => Translation::get(key: 'msgLoginUser'),
            'msgAdvancedSearch' => Translation::get(key: 'msgAdvancedSearch'),
            'currentYear' => date(format: 'Y', timestamp: time()),
            'cookieConsentEnabled' => $this->configuration->get('layout.enableCookieConsent'),
            'faqHome' => $this->configuration->getDefaultUrl(),
            'topNavigation' => $this->getTopNavigation($request),
            'isAskQuestionsEnabled' => $this->configuration->get('main.enableAskQuestions'),
            'isOpenQuestionsEnabled' => $this->configuration->get('main.enableAskQuestions'),
            'footerNavigation' => $this->getFooterNavigation($request),
            'isPrivacyLinkEnabled' => $this->configuration->get('layout.enablePrivacyLink'),
            'msgPrivacyNote' => Translation::get(key: 'msgPrivacyNote'),
            'isCookieConsentEnabled' => $this->configuration->get('layout.enableCookieConsent'),
            'cookiePreferences' => Translation::get(key: 'cookiePreferences'),
        ];
    }

    private function getTopNavigation(Request $request): array
    {
        $action = $request->query->get(key: 'action', default: 'index');

        return [
            [
                'name' => Translation::get(key: 'msgShowAllCategories'),
                'link' => './show-categories.html',
                'active' => 'show' === $action ? 'active' : '',
            ],
            [
                'name' => Translation::get(key: 'msgAddContent'),
                'link' => './add-faq.html',
                'active' => 'add' === $action ? 'active' : '',
            ],
            [
                'name' => Translation::get(key: 'msgQuestion'),
                'link' => './add-question.html',
                'active' => 'ask' == $action ? 'active' : '',
            ],
            [
                'name' => Translation::get(key: 'msgOpenQuestions'),
                'link' => './open-questions.html',
                'active' => 'open-questions' == $action ? 'active' : '',
            ],
        ];
    }

    private function getFooterNavigation(Request $request): array
    {
        $action = $request->query->get(key: 'action', default: 'index');

        return [
            [
                'name' => Translation::get(key: 'faqOverview'),
                'link' => './overview.html',
                'active' => 'faq-overview' == $action ? 'active' : '',
            ],
            [
                'name' => Translation::get(key: 'msgSitemap'),
                'link' => './sitemap/A/' . $this->configuration->getLanguage()->getLanguage() . '.html',
                'active' => 'sitemap' == $action ? 'active' : '',
            ],
            [
                'name' => Translation::get(key: 'ad_menu_glossary'),
                'link' => './glossary.html',
                'active' => 'glossary' == $action ? 'active' : '',
            ],
            [
                'name' => Translation::get(key: 'msgContact'),
                'link' => './contact.html',
                'active' => 'contact' == $action ? 'active' : '',
            ],
        ];
    }
}
