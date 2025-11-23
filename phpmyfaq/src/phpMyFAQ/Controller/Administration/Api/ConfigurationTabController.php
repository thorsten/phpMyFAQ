<?php

declare(strict_types=1);

/**
 * The Admin Configuration Tab Controller
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at https://mozilla.org/MPL/2.0/.
 *
 * @package   phpMyFAQ
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2023-2025 phpMyFAQ Team
 * @license   https://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2023-10-30
 */

namespace phpMyFAQ\Controller\Administration\Api;

use phpMyFAQ\Administration\Helper;
use phpMyFAQ\Controller\AbstractController;
use phpMyFAQ\Core\Exception;
use phpMyFAQ\Enums\PermissionType;
use phpMyFAQ\Filter;
use phpMyFAQ\Helper\LanguageHelper;
use phpMyFAQ\Helper\PermissionHelper;
use phpMyFAQ\Session\Token;
use phpMyFAQ\Strings;
use phpMyFAQ\Translation;
use phpMyFAQ\Twig\TemplateException;
use Symfony\Component\HttpFoundation\Exception\BadRequestException;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Twig\Error\LoaderError;

final class ConfigurationTabController extends AbstractController
{
    /**
     * @throws TemplateException
     * @throws Exception
     * @throws LoaderError*@throws \Exception
     *
     */
    #[Route(path: 'admin/api/configuration/list/{mode}')]
    public function list(Request $request): Response
    {
        $this->userHasPermission(PermissionType::CONFIGURATION_EDIT);

        $language = $this->container->get(id: 'phpmyfaq.language');
        $currentLanguage = $language->setLanguageByAcceptLanguage();

        try {
            Translation::create()
                ->setTranslationsDir(PMF_LANGUAGE_DIR)
                ->setDefaultLanguage(defaultLanguage: 'en')
                ->setCurrentLanguage($currentLanguage)
                ->setMultiByteLanguage();
        } catch (Exception $exception) {
            throw new BadRequestException($exception->getMessage());
        }

        $mode = $request->get(key: 'mode');
        $configurationList = Translation::getConfigurationItems($mode);

        return $this->render(
            file: '@admin/configuration/tab-list.twig',
            context: [
                'mode' => $mode,
                'configurationList' => $configurationList,
                'configurationData' => $this->configuration->getAll(),
                'specialCases' => [
                    'ldapSupport' => extension_loaded(extension: 'ldap'),
                    'useSslForLogins' => Request::createFromGlobals()->isSecure(),
                    'useSslOnly' => Request::createFromGlobals()->isSecure(),
                    'ssoSupport' => Request::createFromGlobals()->server->get(key: 'REMOTE_USER'),
                    'buttonTes',
                ],
            ],
        );
    }

    /**
     * @throws Exception|\Exception
     */
    #[Route(path: 'admin/api/configuration')]
    public function save(Request $request): JsonResponse
    {
        $this->userHasPermission(PermissionType::CONFIGURATION_EDIT);

        $csrfToken = $request->get(key: 'pmf-csrf-token');
        $configurationData = $request->get(key: 'edit');
        $oldConfigurationData = $this->configuration->getAll();

        if (!Token::getInstance($this->container->get(id: 'session'))->verifyToken(
            page: 'configuration',
            requestToken: $csrfToken,
        )) {
            return $this->json(['error' => Translation::get(key: 'msgNoPermission')], Response::HTTP_UNAUTHORIZED);
        }

        // Set the new values
        $newConfigValues = [];
        $escapeValues = [
            'main.contactInformation',
            'main.customPdfHeader',
            'main.customPdfFooter',
            'main.titleFAQ',
        ];

        // Special checks
        if (isset($configurationData['main.enableMarkdownEditor'])) {
            $configurationData['main.enableWysiwygEditor'] = false; // Disable WYSIWYG editor if Markdown is enabled
        }

        if (isset($configurationData['main.currentVersion'])) {
            unset($configurationData['main.currentVersion']); // don't update the version number
        }

        if (isset($configurationData['records.attachmentsPath'])) {
            $realPath = realpath($configurationData['records.attachmentsPath']);

            if (false === $realPath) {
                unset($configurationData['records.attachmentsPath']);
            }

            if (false !== $realPath) {
                $configurationData['records.attachmentsPath'] = str_replace(
                    search: Request::createFromGlobals()->server->get(key: 'DOCUMENT_ROOT') . DIRECTORY_SEPARATOR,
                    replace: '',
                    subject: $realPath,
                );
            }
        }

        if (
            isset($configurationData['main.referenceURL'])
            && is_null(Filter::filterVar($configurationData['main.referenceURL'], FILTER_VALIDATE_URL))
        ) {
            unset($configurationData['main.referenceURL']);
        }

        foreach ($configurationData as $key => $value) {
            $newConfigValues[$key] = (string) $value;
            // Escape some values
            if (isset($escapeValues[$key])) {
                $newConfigValues[$key] = Strings::htmlspecialchars($value, ENT_QUOTES);
            }
        }

        foreach ($oldConfigurationData as $key => $value) {
            $newValueExists = array_key_exists($key, $newConfigValues);
            if (!$newValueExists) {
                $newConfigValues[$key] = $value === 'true' ? 'false' : $value;
            }
        }

        // Replace main.referenceUrl in FAQs
        if ($oldConfigurationData['main.referenceURL'] !== $newConfigValues['main.referenceURL']) {
            $this->configuration->replaceMainReferenceUrl(
                $oldConfigurationData['main.referenceURL'],
                $newConfigValues['main.referenceURL'],
            );
        }

        $this->configuration->update($newConfigValues);

        return $this->json(['success' => Translation::get(key: 'ad_config_saved')], Response::HTTP_OK);
    }

    /**
     * @throws \Exception
     */
    #[Route(
        path: 'admin/api/configuration/translations',
        name: 'admin.api.configuration.translations',
        methods: ['GET'],
    )]
    public function translations(): Response
    {
        $this->userIsAuthenticated();

        $response = new Response();

        $languages = LanguageHelper::getAvailableLanguages();
        if ($languages !== []) {
            return $response->setContent(LanguageHelper::renderLanguageOptions(
                str_replace(
                    ['language_', '.php'],
                    replace: '',
                    subject: (string) $this->configuration->get(item: 'main.language'),
                ),
                onlyThisLang: false,
                fileLanguageValue: true,
            ));
        }

        return $response->setContent(content: '<option value="language_en.php">English</option>');
    }

    /**
     * @throws \Exception
     */
    #[Route(path: 'admin/api/configuration/templates', name: 'admin.api.configuration.templates', methods: ['GET'])]
    public function templates(): Response
    {
        $this->userIsAuthenticated();

        $response = new Response();
        $faqSystem = $this->container->get(id: 'phpmyfaq.system');
        $templates = $faqSystem->getAvailableTemplates();
        $htmlString = '';

        foreach ($templates as $template => $selected) {
            $selectedAttribute = $selected === true ? ' selected' : '';
            $htmlString .= "<option{$selectedAttribute}>{$template}</option>";
        }

        return $response->setContent($htmlString);
    }

    #[Route(
        path: 'admin/api/configuration/faqs-sorting-key',
        name: 'admin.api.configuration.faqs-sorting-key',
        methods: ['GET'],
    )]
    public function faqsSortingKey(Request $request): Response
    {
        $this->userIsAuthenticated();

        return new Response(Helper::sortingKeyOptions($request->get(key: 'current')));
    }

    #[Route(
        path: 'admin/api/configuration/faqs-sorting-order',
        name: 'admin.api.configuration.faqs-sorting-order',
        methods: ['GET'],
    )]
    public function faqsSortingOrder(Request $request): Response
    {
        $this->userIsAuthenticated();

        return new Response(Helper::sortingOrderOptions($request->get(key: 'current')));
    }

    #[Route(
        path: 'admin/api/configuration/faqs-sorting-popular',
        name: 'admin.api.configuration.faqs-sorting-popular',
        methods: ['GET'],
    )]
    public function faqsSortingPopular(Request $request): Response
    {
        $this->userIsAuthenticated();

        return new Response(Helper::sortingPopularFaqsOptions($request->get(key: 'current')));
    }

    #[Route(path: 'admin/api/configuration/perm-level', name: 'admin.api.configuration.perm-level', methods: ['GET'])]
    public function permLevel(Request $request): Response
    {
        $this->userIsAuthenticated();

        return new Response(PermissionHelper::permOptions($request->get(key: 'current')));
    }

    #[Route(
        path: 'admin/api/configuration/release-environment',
        name: 'admin.api.configuration.release-environment',
        methods: ['GET'],
    )]
    public function releaseEnvironment(Request $request): Response
    {
        $this->userIsAuthenticated();

        return new Response(Helper::renderReleaseTypeOptions($request->get(key: 'current')));
    }

    #[Route(
        path: 'admin/api/configuration/search-relevance',
        name: 'admin.api.configuration.search-relevance',
        methods: ['GET'],
    )]
    public function searchRelevance(Request $request): Response
    {
        $this->userIsAuthenticated();

        return new Response(Helper::searchRelevanceOptions($request->get(key: 'current')));
    }

    #[Route(
        path: 'admin/api/configuration/seo-metatags',
        name: 'admin.api.configuration.seo-metatags',
        methods: ['GET'],
    )]
    public function seoMetaTags(Request $request): Response
    {
        $this->userIsAuthenticated();

        return new Response(Helper::renderMetaRobotsDropdown($request->get(key: 'current')));
    }
}
