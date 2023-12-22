<?php

/**
 * The Admin Configuration Tab Controller
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at https://mozilla.org/MPL/2.0/.
 *
 * @package   phpMyFAQ
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2023 phpMyFAQ Team
 * @license   https://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2023-10-30
 */

namespace phpMyFAQ\Controller\Administration;

use phpMyFAQ\Configuration;
use phpMyFAQ\Controller\AbstractController;
use phpMyFAQ\Helper\AdministrationHelper;
use phpMyFAQ\Helper\LanguageHelper;
use phpMyFAQ\Helper\PermissionHelper;
use phpMyFAQ\System;
use phpMyFAQ\Template\TemplateException;
use phpMyFAQ\Translation;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class ConfigurationTabController extends AbstractController
{
    /**
     * @throws TemplateException
     */
    #[Route('admin/api/configuration/list')]
    public function list(Request $request): Response
    {
        $configuration = Configuration::getConfigurationInstance();

        $mode = $request->get('mode');

        $configurationList = Translation::getConfigurationItems($mode);

        return $this->render(
            './admin/configuration/tab-list.twig',
            [
                'mode' => $mode,
                'configurationList' => $configurationList,
                'configurationData' => $configuration->getAll(),
                'specialCases' => [
                    'ldapSupport' => extension_loaded('ldap'),
                    'useSslForLogins' => Request::createFromGlobals()->isSecure(),
                    'useSslOnly' => Request::createFromGlobals()->isSecure(),
                    'ssoSupport' => Request::createFromGlobals()->server->get('REMOTE_USER')
                ]
            ]
        );
    }

    #[Route('admin/api/configuration/translations')]
    public function translations(): Response
    {
        $configuration = Configuration::getConfigurationInstance();
        $response = new Response();

        $languages = LanguageHelper::getAvailableLanguages();
        if (count($languages) > 0) {
            return $response->setContent(LanguageHelper::renderLanguageOptions(
                str_replace(
                    [ 'language_', '.php', ],
                    '',
                    $configuration->get('main.language')
                ),
                false,
                true
            ));
        } else {
            return $response->setContent('<option value="language_en.php">English</option>');
        }
    }

    #[Route('admin/api/configuration/templates')]
    public function templates(): Response
    {
        $response = new Response();
        $faqSystem = new System();
        $templates = $faqSystem->getAvailableTemplates();
        $htmlString = '';

        foreach ($templates as $template => $selected) {
            $htmlString .= sprintf(
                '<option%s>%s</option>',
                ($selected === true ? ' selected' : ''),
                $template
            );
        }

        return $response->setContent($htmlString);
    }

    #[Route('admin/api/configuration/faqs-sorting-key')]
    public function faqsSortingKey(Request $request): Response
    {
        return new Response(
            AdministrationHelper::sortingKeyOptions($request->get('current'))
        );
    }

    #[Route('admin/api/configuration/faqs-sorting-order')]
    public function faqsSortingOrder(Request $request): Response
    {
        return new Response(
            AdministrationHelper::sortingOrderOptions($request->get('current'))
        );
    }

    #[Route('admin/api/configuration/faqs-sorting-popular')]
    public function faqsSortingPopular(Request $request): Response
    {
        return new Response(
            AdministrationHelper::sortingPopularFaqsOptions($request->get('current'))
        );
    }
    #[Route('admin/api/configuration/perm-level')]
    public function permLevel(Request $request): Response
    {
        return new Response(
            PermissionHelper::permOptions($request->get('current'))
        );
    }

    #Route('admin/api/configuration/search-relevance')
    public function searchRelevance(Request $request): Response
    {
        return new Response(
            AdministrationHelper::searchRelevanceOptions($request->get('current'))
        );
    }
}
