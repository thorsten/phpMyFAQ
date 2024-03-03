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
 * @copyright 2023-2024 phpMyFAQ Team
 * @license   https://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2023-10-30
 */

namespace phpMyFAQ\Controller\Administration;

use phpMyFAQ\Configuration;
use phpMyFAQ\Controller\AbstractController;
use phpMyFAQ\Core\Exception;
use phpMyFAQ\Enums\PermissionType;
use phpMyFAQ\Filter;
use phpMyFAQ\Helper\AdministrationHelper;
use phpMyFAQ\Helper\LanguageHelper;
use phpMyFAQ\Helper\PermissionHelper;
use phpMyFAQ\Session\Token;
use phpMyFAQ\Strings;
use phpMyFAQ\System;
use phpMyFAQ\Template\TemplateException;
use phpMyFAQ\Translation;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class ConfigurationTabController extends AbstractController
{
    /**
     * @throws TemplateException
     * @throws Exception
     */
    #[Route('admin/api/configuration/list')]
    public function list(Request $request): Response
    {
        $this->userHasPermission(PermissionType::CONFIGURATION_EDIT);

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

    /**
     * @throws Exception
     */
    #[Route('admin/api/configuration')]
    public function save(Request $request): JsonResponse
    {
        $this->userHasPermission(PermissionType::CONFIGURATION_EDIT);

        $configuration = Configuration::getConfigurationInstance();
        $jsonResponse = new JsonResponse();

        $csrfToken = $request->get('pmf-csrf-token');
        $configurationData = $request->get('edit');
        $oldConfigurationData = $configuration->getAll();

        if (!Token::getInstance()->verifyToken('configuration', $csrfToken)) {
            $jsonResponse->setStatusCode(Response::HTTP_UNAUTHORIZED);
            $jsonResponse->setData(['error' => Translation::get('err_NotAuth')]);
        } else {
            // Set the new values
            $forbiddenValues = ['{', '}'];
            $newConfigValues = [];
            $escapeValues = [
                'main.contactInformation',
                'main.customPdfHeader',
                'main.customPdfFooter',
                'main.titleFAQ',
                'main.metaKeywords'
            ];

            // Special checks
            if (isset($configurationData['main.enableMarkdownEditor'])) {
                $configurationData['main.enableWysiwygEditor'] = false; // Disable WYSIWYG editor if Markdown is enabled
            }

            if (isset($configurationData['main.currentVersion'])) {
                unset($configurationData['main.currentVersion']); // don't update the version number
            }

            if (
                isset($configurationData['main.referenceURL']) &&
                is_null(Filter::filterVar($configurationData['main.referenceURL'], FILTER_VALIDATE_URL))
            ) {
                unset($configurationData['main.referenceURL']);
            }

            $newConfigClass = [];

            foreach ($configurationData as $key => $value) {
                // Remove forbidden characters
                $newConfigValues[$key] = str_replace($forbiddenValues, '', (string) $value);
                // Escape some values
                if (isset($escapeValues[$key])) {
                    $newConfigValues[$key] = Strings::htmlspecialchars($value, ENT_QUOTES);
                }

                $keyArray = array_values(explode('.', (string) $key));
                $newConfigClass = array_shift($keyArray);
            }

            foreach ($oldConfigurationData as $key => $value) {
                $keyArray = array_values(explode('.', (string) $key));
                $oldConfigClass = array_shift($keyArray);
                if (isset($newConfigValues[$key])) {
                    continue;
                }
                if ($oldConfigClass === $newConfigClass && $value === 'true') {
                    $newConfigValues[$key] = 'false';
                } else {
                    $newConfigValues[$key] = $value;
                }
            }

            $configuration->update($newConfigValues);

            $jsonResponse->setStatusCode(Response::HTTP_OK);
            $jsonResponse->setData(['success' => Translation::get('ad_config_saved')]);
        }

        return $jsonResponse;
    }

    /**
     * @throws Exception
     */
    #[Route('admin/api/configuration/translations')]
    public function translations(): Response
    {
        $this->userIsAuthenticated();

        $configuration = Configuration::getConfigurationInstance();
        $response = new Response();

        $languages = LanguageHelper::getAvailableLanguages();
        if ($languages !== []) {
            return $response->setContent(LanguageHelper::renderLanguageOptions(
                str_replace(
                    [ 'language_', '.php', ],
                    '',
                    (string) $configuration->get('main.language')
                ),
                false,
                true
            ));
        }
        return $response->setContent('<option value="language_en.php">English</option>');
    }

    /**
     * @throws Exception
     */
    #[Route('admin/api/configuration/templates')]
    public function templates(): Response
    {
        $this->userIsAuthenticated();

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

    /**
     * @throws Exception
     */
    #[Route('admin/api/configuration/faqs-sorting-key')]
    public function faqsSortingKey(Request $request): Response
    {
        $this->userIsAuthenticated();

        return new Response(
            AdministrationHelper::sortingKeyOptions($request->get('current'))
        );
    }

    /**
     * @throws Exception
     */
    #[Route('admin/api/configuration/faqs-sorting-order')]
    public function faqsSortingOrder(Request $request): Response
    {
        $this->userIsAuthenticated();

        return new Response(
            AdministrationHelper::sortingOrderOptions($request->get('current'))
        );
    }

    /**
     * @throws Exception
     */
    #[Route('admin/api/configuration/faqs-sorting-popular')]
    public function faqsSortingPopular(Request $request): Response
    {
        $this->userIsAuthenticated();

        return new Response(
            AdministrationHelper::sortingPopularFaqsOptions($request->get('current'))
        );
    }

    /**
     * @throws Exception
     */
    #[Route('admin/api/configuration/perm-level')]
    public function permLevel(Request $request): Response
    {
        $this->userIsAuthenticated();

        return new Response(
            PermissionHelper::permOptions($request->get('current'))
        );
    }

    /**
     * @throws Exception
     */
    #[Route('admin/api/configuration/release-environment')]
    public function releaseEnvironment(Request $request): Response
    {
        $this->userIsAuthenticated();

        return new Response(
            AdministrationHelper::renderReleaseTypeOptions($request->get('current'))
        );
    }

    /**
     * @throws Exception
     */
    #[Route('admin/api/configuration/search-relevance')]
    public function searchRelevance(Request $request): Response
    {
        $this->userIsAuthenticated();

        return new Response(
            AdministrationHelper::searchRelevanceOptions($request->get('current'))
        );
    }

    /**
     * @throws Exception
     */
    #[Route('admin/api/configuration/seo-metatags')]
    public function seoMetaTags(Request $request): Response
    {
        $this->userIsAuthenticated();

        return new Response(
            AdministrationHelper::renderMetaRobotsDropdown($request->get('current'))
        );
    }
}
