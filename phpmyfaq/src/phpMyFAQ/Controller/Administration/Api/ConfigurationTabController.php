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

use phpMyFAQ\Administration\AdminMenuBuilder;
use phpMyFAQ\Core\Exception;
use phpMyFAQ\Enums\AdminLogType;
use phpMyFAQ\Enums\PermissionType;
use phpMyFAQ\Filter;
use phpMyFAQ\Helper\LanguageHelper;
use phpMyFAQ\Helper\PermissionHelper;
use phpMyFAQ\Language;
use phpMyFAQ\Session\Token;
use phpMyFAQ\Strings;
use phpMyFAQ\System;
use phpMyFAQ\Template\ThemeManager;
use phpMyFAQ\Translation;
use phpMyFAQ\Twig\TemplateException;
use Symfony\Component\HttpFoundation\Exception\BadRequestException;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Twig\Error\LoaderError;

final class ConfigurationTabController extends AbstractAdministrationApiController
{
    public function __construct(
        private readonly Language $language,
        private readonly System $faqSystem,
        private readonly ThemeManager $themeManager,
    ) {
        parent::__construct();
    }

    /**
     * @throws TemplateException
     * @throws Exception
     * @throws LoaderError
     * @throws \Exception
     */
    #[Route(path: 'configuration/list/{mode}', name: 'admin.api.configuration.list', methods: ['GET'])]
    public function list(Request $request): Response
    {
        $this->userHasPermission(PermissionType::CONFIGURATION_EDIT);

        $currentLanguage = $this->language->setLanguageByAcceptLanguage();

        try {
            Translation::create()
                ->setTranslationsDir(PMF_LANGUAGE_DIR)
                ->setDefaultLanguage(defaultLanguage: 'en')
                ->setCurrentLanguage($currentLanguage)
                ->setMultiByteLanguage();
        } catch (Exception $exception) {
            throw new BadRequestException($exception->getMessage());
        }

        $mode = (string) $request->attributes->get(key: 'mode');
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
            'themeCsrfToken' => Token::getInstance($this->session)->getTokenString('theme-manager'),
            'activeTheme' => (string) $this->configuration->get('layout.templateSet'),
        ]);
    }

    /**
     * @throws \Exception
     */
    #[Route(path: 'configuration/themes/upload', name: 'admin.api.configuration.themes.upload', methods: ['POST'])]
    public function uploadTheme(Request $request): JsonResponse
    {
        $this->userHasPermission(PermissionType::CONFIGURATION_EDIT);

        if (!$this->hasValidThemeCsrfToken($request)) {
            return $this->json(['error' => Translation::get(key: 'msgNoPermission')], Response::HTTP_UNAUTHORIZED);
        }

        $file = $request->files->get('themeArchive');
        if (!$file instanceof UploadedFile || !$file->isValid()) {
            return $this->json(['error' => 'No valid ZIP file uploaded.'], Response::HTTP_BAD_REQUEST);
        }

        $themeName = trim((string) $request->request->get('themeName', ''));
        if ($themeName === '') {
            $themeName = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
        }

        try {
            $uploadedFiles = $this->themeManager->uploadTheme($themeName, $file->getPathname());

            return $this->json([
                'success' => sprintf('Theme "%s" uploaded (%d files).', $themeName, $uploadedFiles),
            ], Response::HTTP_OK);
        } catch (\RuntimeException $runtimeException) {
            return $this->json(['error' => $runtimeException->getMessage()], Response::HTTP_BAD_REQUEST);
        }
    }

    /**
     * @throws \Exception
     */
    #[Route(path: 'configuration', name: 'admin.api.configuration.save', methods: ['POST'])]
    public function save(Request $request): JsonResponse
    {
        $this->userHasPermission(PermissionType::CONFIGURATION_EDIT);

        $csrfToken = (string) $request->request->get(key: 'pmf-csrf-token');
        $configurationData = $request->getPayload()->all(key: 'edit');
        $availableFieldsJson = (string) $request->request->get(key: 'availableFields');

        $oldConfigurationData = $this->configuration->getAll();

        if (!Token::getInstance($this->session)->verifyToken(page: 'configuration', requestToken: $csrfToken)) {
            return $this->json(['error' => Translation::get(key: 'msgNoPermission')], Response::HTTP_UNAUTHORIZED);
        }

        // Parse the list of available fields from the form
        $availableFields = [];
        if ($availableFieldsJson !== '') {
            $decodedFields = json_decode($availableFieldsJson, associative: true);
            if (is_array($decodedFields)) {
                $availableFields = array_map(
                    static fn(mixed $fieldName): string => (string) $fieldName,
                    $decodedFields,
                );
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
        if (array_key_exists('main.enableMarkdownEditor', $configurationData)) {
            $configurationData['main.enableWysiwygEditor'] = false; // Disable WYSIWYG editor if Markdown is enabled
        }

        if (array_key_exists('main.currentVersion', $configurationData)) {
            unset($configurationData['main.currentVersion']); // don't update the version number
        }

        if (array_key_exists('records.attachmentsPath', $configurationData)) {
            $realPath = realpath((string) $configurationData['records.attachmentsPath']);

            if (false === $realPath) {
                unset($configurationData['records.attachmentsPath']);
            }

            if (false !== $realPath) {
                $configurationData['records.attachmentsPath'] = str_replace(
                    search: (string) Request::createFromGlobals()->server->get(key: 'DOCUMENT_ROOT')
                    . DIRECTORY_SEPARATOR,
                    replace: '',
                    subject: $realPath,
                );
            }
        }

        if (
            array_key_exists('main.referenceURL', $configurationData)
            && is_null(Filter::filterVar($configurationData['main.referenceURL'], FILTER_VALIDATE_URL))
        ) {
            unset($configurationData['main.referenceURL']);
        }

        foreach ($configurationData as $key => $value) {
            $stringValue = is_scalar($value) || $value === null ? (string) $value : '';
            $newConfigValues[(string) $key] = $stringValue;
            // Escape some values
            if (in_array($key, $escapeValues, strict: true)) {
                $newConfigValues[(string) $key] = Strings::htmlspecialchars($stringValue, ENT_QUOTES);
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
                    array_key_exists($availableField, $oldConfigurationData)
                    && $oldConfigurationData[$availableField] === 'true'
                ) {
                    $newConfigValues[$availableField] = 'false';
                }
            }
        }

        // Keep all values that were not in the available fields (from other tabs);
        // runtime objects under core.* keys are never part of the form payload.
        foreach ($oldConfigurationData as $key => $value) {
            if (array_key_exists($key, $newConfigValues)) {
                continue;
            }

            if (is_scalar($value) || $value === null) {
                $newConfigValues[(string) $key] = $value === null ? null : (string) $value;
            }
        }

        // Replace "main.referenceUrl" in FAQs
        $oldReferenceUrl = (string) ($oldConfigurationData['main.referenceURL'] ?? '');
        $newReferenceUrl = (string) ($newConfigValues['main.referenceURL'] ?? '');
        if ($oldReferenceUrl !== $newReferenceUrl) {
            $this->configuration->replaceMainReferenceUrl($oldReferenceUrl, $newReferenceUrl);
        }

        $this->configuration->update($newConfigValues);

        // Filter out non-scalar values from old config before comparison
        $oldConfigComparable = array_filter(
            $oldConfigurationData,
            static fn($value) => is_scalar($value) || $value === null,
        );

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
    /**
     * @param list<string> $changedKeys
     * @param array<array-key, mixed> $oldConfig
     * @param array<array-key, mixed> $newConfig
     */
    private function logSecurityConfigChanges(array $changedKeys, array $oldConfig, array $newConfig): void
    {
        // Maintenance mode changes
        if (in_array('main.maintenanceMode', $changedKeys, strict: true)) {
            if ($newConfig['main.maintenanceMode'] === 'false' && $oldConfig['main.maintenanceMode'] === 'true') {
                $this->adminLog->log($this->currentUser, AdminLogType::SYSTEM_MAINTENANCE_MODE_DISABLED->value);
            }

            if ($newConfig['main.maintenanceMode'] === 'true' && $oldConfig['main.maintenanceMode'] === 'false') {
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
                $details[] = (string) $key . ':' . $oldValue . '->' . $newValue;
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
            'keycloak.enable',
            'keycloak.baseUrl',
            'keycloak.realm',
            'keycloak.clientId',
            'keycloak.clientSecret',
            'keycloak.redirectUri',
            'keycloak.scopes',
            'keycloak.autoProvision',
            'keycloak.groupAutoAssign',
            'keycloak.groupSyncOnLogin',
            'keycloak.groupMapping',
            'keycloak.logoutRedirectUrl',
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
    #[Route(path: 'configuration/translations', name: 'admin.api.configuration.translations', methods: ['GET'])]
    public function translations(): Response
    {
        $this->userHasPermission(PermissionType::CONFIGURATION_EDIT);

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
    #[Route(path: 'configuration/templates', name: 'admin.api.configuration.templates', methods: ['GET'])]
    public function templates(): Response
    {
        $this->userHasPermission(PermissionType::CONFIGURATION_EDIT);

        $response = new Response();
        $templates = $this->faqSystem->getAvailableTemplates();
        $htmlString = '';

        foreach ($templates as $template => $selected) {
            $selectedAttribute = $selected === true ? ' selected' : '';
            $htmlString .= sprintf('<option%s>%s</option>', $selectedAttribute, $template);
        }

        return $response->setContent($htmlString);
    }

    #[Route(
        path: 'configuration/faqs-sorting-key/{current}',
        name: 'admin.api.configuration.faqs-sorting-key',
        methods: ['GET'],
    )]
    public function faqsSortingKey(Request $request): Response
    {
        $this->userHasPermission(PermissionType::CONFIGURATION_EDIT);

        return new Response(AdminMenuBuilder::sortingKeyOptions((string) $request->attributes->get(key: 'current')));
    }

    #[Route(
        path: 'configuration/faqs-sorting-order/{current}',
        name: 'admin.api.configuration.faqs-sorting-order',
        methods: ['GET'],
    )]
    public function faqsSortingOrder(Request $request): Response
    {
        $this->userHasPermission(PermissionType::CONFIGURATION_EDIT);

        return new Response(AdminMenuBuilder::sortingOrderOptions((string) $request->attributes->get(key: 'current')));
    }

    #[Route(
        path: 'configuration/faqs-sorting-popular/{current}',
        name: 'admin.api.configuration.faqs-sorting-popular',
        methods: ['GET'],
    )]
    public function faqsSortingPopular(Request $request): Response
    {
        $this->userHasPermission(PermissionType::CONFIGURATION_EDIT);

        return new Response(AdminMenuBuilder::sortingPopularFaqsOptions((string) $request->attributes->get(
            key: 'current',
        )));
    }

    #[Route(path: 'configuration/perm-level/{current}', name: 'admin.api.configuration.permLevel', methods: ['GET'])]
    public function permLevel(Request $request): Response
    {
        $this->userHasPermission(PermissionType::CONFIGURATION_EDIT);

        return new Response(PermissionHelper::permOptions((string) $request->attributes->get(key: 'current')));
    }

    #[Route(
        path: 'configuration/release-environment/{current}',
        name: 'admin.api.configuration.release-environment',
        methods: ['GET'],
    )]
    public function releaseEnvironment(Request $request): Response
    {
        $this->userHasPermission(PermissionType::CONFIGURATION_EDIT);

        return new Response(AdminMenuBuilder::renderReleaseTypeOptions((string) $request->attributes->get(
            key: 'current',
        )));
    }

    #[Route(
        path: 'configuration/search-relevance/{current}',
        name: 'admin.api.configuration.search-relevance',
        methods: ['GET'],
    )]
    public function searchRelevance(Request $request): Response
    {
        $this->userHasPermission(PermissionType::CONFIGURATION_EDIT);

        return new Response(AdminMenuBuilder::searchRelevanceOptions((string) $request->attributes->get(
            key: 'current',
        )));
    }

    #[Route(
        path: 'configuration/seo-metatags/{current}',
        name: 'admin.api.configuration.seo-metatags',
        methods: ['GET'],
    )]
    public function seoMetaTags(Request $request): Response
    {
        $this->userHasPermission(PermissionType::CONFIGURATION_EDIT);

        return new Response(AdminMenuBuilder::renderMetaRobotsDropdown((string) $request->attributes->get(
            key: 'current',
        )));
    }

    #[Route(
        path: 'configuration/translation-provider/{current}',
        name: 'admin.api.configuration.translation-provider',
        methods: ['GET'],
    )]
    public function translationProvider(Request $request): Response
    {
        $this->userHasPermission(PermissionType::CONFIGURATION_EDIT);

        return new Response(AdminMenuBuilder::renderTranslationProviderOptions((string) $request->attributes->get(
            key: 'current',
        )));
    }

    #[Route(
        path: 'configuration/mail-provider/{current}',
        name: 'admin.api.configuration.mail-provider',
        methods: ['GET'],
    )]
    public function mailProvider(Request $request): Response
    {
        $this->userHasPermission(PermissionType::CONFIGURATION_EDIT);

        return new Response(AdminMenuBuilder::renderMailProviderOptions((string) $request->attributes->get(
            key: 'current',
        )));
    }

    #[Route(
        path: 'configuration/cache-adapter/{current}',
        name: 'admin.api.configuration.cache-adapter',
        methods: ['GET'],
    )]
    public function cacheAdapter(Request $request): Response
    {
        $this->userHasPermission(PermissionType::CONFIGURATION_EDIT);

        return new Response(AdminMenuBuilder::renderCacheAdapterOptions((string) $request->attributes->get(
            key: 'current',
        )));
    }

    #[Route(path: 'configuration/layout-mode/{current}', name: 'admin.api.configuration.layout-mode', methods: ['GET'])]
    public function layoutMode(Request $request): Response
    {
        $this->userHasPermission(PermissionType::CONFIGURATION_EDIT);

        return new Response(AdminMenuBuilder::renderLayoutModeOptions((string) $request->attributes->get(
            key: 'current',
        )));
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

    private function hasValidThemeCsrfToken(Request $request): bool
    {
        $csrfToken = (string) $request->request->get('theme-csrf-token', '');
        return Token::getInstance($this->session)->verifyToken('theme-manager', $csrfToken);
    }
}
