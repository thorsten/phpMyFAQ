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
 * @copyright 2023-2026 phpMyFAQ Team
 * @license   https://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2023-10-30
 */

declare(strict_types=1);

namespace phpMyFAQ\Controller\Administration\Api;

use phpMyFAQ\Administration\Helper;
use phpMyFAQ\Core\Exception;
use phpMyFAQ\Enums\AdminLogType;
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
use Symfony\Component\Routing\Attribute\Route;
use Twig\Error\LoaderError;

final class ConfigurationTabController extends AbstractAdministrationApiController
{
    /**
     * @throws TemplateException
     * @throws Exception
     * @throws LoaderError
     * @throws \Exception
     */
    #[Route(path: 'admin/api/configuration/list/{mode}', name: 'admin.api.configuration.list', methods: ['GET'])]
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

        $mode = $request->attributes->get(key: 'mode');
        $configurationList = Translation::getConfigurationItems($mode);

        return $this->render(file: '@admin/configuration/tab-list.twig', context: [
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
        ]);
    }

    /**
     * @throws \Exception
     */
    #[Route(path: 'admin/api/configuration', name: 'admin.api.configuration.save', methods: ['POST'])]
    public function save(Request $request): JsonResponse
    {
        $this->userHasPermission(PermissionType::CONFIGURATION_EDIT);

        $csrfToken = $request->request->get(key: 'pmf-csrf-token');
        $configurationData = $request->getPayload()->all(key: 'edit');
        $availableFieldsJson = $request->request->get(key: 'availableFields');

        $oldConfigurationData = $this->configuration->getAll();

        if (!Token::getInstance($this->session)->verifyToken(page: 'configuration', requestToken: $csrfToken)) {
            return $this->json(['error' => Translation::get(key: 'msgNoPermission')], Response::HTTP_UNAUTHORIZED);
        }

        // Parse the list of available fields from the form
        $availableFields = [];
        if ($availableFieldsJson) {
            $availableFields = json_decode($availableFieldsJson, associative: true);
            if (!is_array($availableFields)) {
                $availableFields = [];
            }
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

        // Only process fields that were available in the current form
        // For checkboxes: if the field is available but not in configurationData, set to false
        // For other fields: keep original value if not in configurationData
        if ($availableFields !== []) {
            foreach ($availableFields as $availableField) {
                if (array_key_exists($availableField, $newConfigValues)) {
                    continue;
                }

                if (
                    isset($oldConfigurationData[$availableField])
                    && $oldConfigurationData[$availableField] === 'true'
                ) {
                    $newConfigValues[$availableField] = 'false';
                }
            }
        }

        // Keep all values that were not in the available fields (from other tabs)
        foreach ($oldConfigurationData as $key => $value) {
            if (array_key_exists($key, $newConfigValues)) {
                continue;
            }

            $newConfigValues[$key] = $value;
        }

        // Replace "main.referenceUrl" in FAQs
        if ($oldConfigurationData['main.referenceURL'] !== $newConfigValues['main.referenceURL']) {
            $this->configuration->replaceMainReferenceUrl(
                $oldConfigurationData['main.referenceURL'],
                $newConfigValues['main.referenceURL'],
            );
        }

        $this->configuration->update($newConfigValues);

        // Filter out non-scalar values from old config before comparison
        $oldConfigComparable = array_filter($oldConfigurationData, fn($value) => is_scalar($value) || $value === null);

        $changedKeys = array_keys(array_diff_assoc($newConfigValues, $oldConfigComparable));

        // General configuration change log
        $this->adminLog->log($this->currentUser, AdminLogType::CONFIG_CHANGE->value . ':' . implode(',', $changedKeys));

        // Specific security-related configuration change logs
        $this->logSecurityConfigChanges($changedKeys, $oldConfigurationData, $newConfigValues);

        return $this->json(['success' => Translation::get(key: 'ad_config_saved')], Response::HTTP_OK);
    }

    /**
     * Log specific security-related configuration changes
     */
    private function logSecurityConfigChanges(array $changedKeys, array $oldConfig, array $newConfig): void
    {
        // Maintenance mode changes
        if (in_array('main.maintenanceMode', $changedKeys)) {
            if ($newConfig['main.maintenanceMode'] === 'false' && $oldConfig['main.maintenanceMode'] === 'true') {
                $this->adminLog->log($this->currentUser, AdminLogType::SYSTEM_MAINTENANCE_MODE_DISABLED->value);
            } elseif ($newConfig['main.maintenanceMode'] === 'true' && $oldConfig['main.maintenanceMode'] === 'false') {
                $this->adminLog->log($this->currentUser, AdminLogType::SYSTEM_MAINTENANCE_MODE_ENABLED->value);
            }
        }

        // Security configuration keys
        $securityKeys = [
            'security.permLevel',
            'security.enableLoginOnly',
            'security.enableRegistration',
            'security.useSslForLogins',
            'security.useSslOnly',
            'security.forcePasswordUpdate',
            'security.enableWebAuthnSupport',
            'security.bannedIPs',
            'security.loginWithEmailAddress',
            'security.enableSignInWithMicrosoft',
            'security.domainWhiteListForRegistrations',
        ];

        $securityChanges = array_intersect($changedKeys, $securityKeys);
        if ($securityChanges !== []) {
            $details = [];
            foreach ($securityChanges as $key) {
                $oldValue = $this->convertToString($oldConfig[$key] ?? null);
                $newValue = $this->convertToString($newConfig[$key] ?? null);
                $details[] = $key . ':' . $oldValue . '->' . $newValue;
            }
            $this->adminLog->log(
                $this->currentUser,
                AdminLogType::CONFIG_SECURITY_CHANGED->value . ':' . implode(';', $details),
            );
        }

        // LDAP configuration keys
        $ldapKeys = [
            'ldap.ldapSupport',
            'ldap.ldap_server',
            'ldap.ldap_port',
            'ldap.ldap_base',
            'ldap.ldap_groupSupport',
        ];

        $ldapChanges = array_intersect($changedKeys, $ldapKeys);
        if ($ldapChanges !== []) {
            $this->adminLog->log(
                $this->currentUser,
                AdminLogType::CONFIG_LDAP_CHANGED->value . ':' . implode(',', $ldapChanges),
            );
        }

        // SSO configuration keys
        $ssoKeys = [
            'security.ssoSupport',
            'security.ssoLogoutRedirect',
        ];

        $ssoChanges = array_intersect($changedKeys, $ssoKeys);
        if ($ssoChanges !== []) {
            $this->adminLog->log(
                $this->currentUser,
                AdminLogType::CONFIG_SSO_CHANGED->value . ':' . implode(',', $ssoChanges),
            );
        }

        // Encryption configuration keys
        $encryptionKeys = [
            'security.encryptionType',
        ];

        $encryptionChanges = array_intersect($changedKeys, $encryptionKeys);
        if ($encryptionChanges !== []) {
            $this->adminLog->log(
                $this->currentUser,
                AdminLogType::CONFIG_ENCRYPTION_CHANGED->value . ':' . implode(',', $encryptionChanges),
            );
        }
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
            $htmlString .= sprintf('<option%s>%s</option>', $selectedAttribute, $template);
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

        return new Response(Helper::sortingKeyOptions($request->attributes->get(key: 'current')));
    }

    #[Route(
        path: 'admin/api/configuration/faqs-sorting-order',
        name: 'admin.api.configuration.faqs-sorting-order',
        methods: ['GET'],
    )]
    public function faqsSortingOrder(Request $request): Response
    {
        $this->userIsAuthenticated();

        return new Response(Helper::sortingOrderOptions($request->attributes->get(key: 'current')));
    }

    #[Route(
        path: 'admin/api/configuration/faqs-sorting-popular',
        name: 'admin.api.configuration.faqs-sorting-popular',
        methods: ['GET'],
    )]
    public function faqsSortingPopular(Request $request): Response
    {
        $this->userIsAuthenticated();

        return new Response(Helper::sortingPopularFaqsOptions($request->attributes->get(key: 'current')));
    }

    #[Route(
        path: 'admin/api/configuration/perm-level/{current}',
        name: 'admin.api.configuration.permLevel',
        methods: ['GET'],
    )]
    public function permLevel(Request $request): Response
    {
        $this->userIsAuthenticated();

        return new Response(PermissionHelper::permOptions($request->attributes->get(key: 'current')));
    }

    #[Route(
        path: 'admin/api/configuration/release-environment',
        name: 'admin.api.configuration.release-environment',
        methods: ['GET'],
    )]
    public function releaseEnvironment(Request $request): Response
    {
        $this->userIsAuthenticated();

        return new Response(Helper::renderReleaseTypeOptions($request->attributes->get(key: 'current')));
    }

    #[Route(
        path: 'admin/api/configuration/search-relevance',
        name: 'admin.api.configuration.search-relevance',
        methods: ['GET'],
    )]
    public function searchRelevance(Request $request): Response
    {
        $this->userIsAuthenticated();

        return new Response(Helper::searchRelevanceOptions($request->attributes->get(key: 'current')));
    }

    #[Route(
        path: 'admin/api/configuration/seo-metatags',
        name: 'admin.api.configuration.seo-metatags',
        methods: ['GET'],
    )]
    public function seoMetaTags(Request $request): Response
    {
        $this->userIsAuthenticated();

        return new Response(Helper::renderMetaRobotsDropdown($request->attributes->get(key: 'current')));
    }

    #[Route(
        path: 'admin/api/configuration/translation-provider/{current}',
        name: 'admin.api.configuration.translation-provider',
        methods: ['GET'],
    )]
    public function translationProvider(Request $request): Response
    {
        $this->userIsAuthenticated();

        return new Response(Helper::renderTranslationProviderOptions($request->attributes->get(key: 'current')));
    }

    /**
     * Converts a value to string safely, handling objects and null values
     */
    private function convertToString(mixed $value): string
    {
        if ($value === null) {
            return 'null';
        }

        if (is_scalar($value)) {
            return (string) $value;
        }

        if (is_object($value)) {
            return get_class($value);
        }

        if (is_array($value)) {
            return 'array';
        }

        return 'unknown';
    }
}
