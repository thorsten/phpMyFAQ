<?php

/**
 * Public metadata service for the REST API bootstrap endpoint.
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at https://mozilla.org/MPL/2.0/.
 *
 * @package   phpMyFAQ
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2026 phpMyFAQ Team
 * @license   https://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2026-04-11
 */

declare(strict_types=1);

namespace phpMyFAQ\Api;

use phpMyFAQ\Configuration;
use phpMyFAQ\Helper\LanguageHelper;

final readonly class MetaService
{
    public function __construct(
        private Configuration $configuration,
        private OAuthDiscoveryService $oAuthDiscoveryService,
    ) {
    }

    /**
     * @return array{
     *     version: string,
     *     title: string,
     *     language: string,
     *     availableLanguages: array<string, string>,
     *     enabledFeatures: array<string, bool>,
     *     publicLogoUrl: string,
     *     themeColors: array<string, array<string, string>>,
     *     oauthDiscovery: array<string, bool|string|string[]>
     * }
     */
    public function getPublicMetadata(): array
    {
        return [
            'version' => $this->configuration->getVersion(),
            'title' => $this->configuration->getTitle(),
            'language' => $this->configuration->getLanguage()->getLanguage(),
            'availableLanguages' => LanguageHelper::getAvailableLanguages(),
            'enabledFeatures' => $this->buildEnabledFeatures(),
            'publicLogoUrl' => $this->buildPublicLogoUrl(),
            'themeColors' => $this->buildThemeColors(),
            'oauthDiscovery' => $this->oAuthDiscoveryService->getMetaDiscovery(),
        ];
    }

    /**
     * @return array<string, bool>
     */
    private function buildEnabledFeatures(): array
    {
        return [
            'api' => true,
            'oauth2' => $this->toBool($this->configuration->get('oauth2.enable')),
            'captcha' => $this->toBool($this->configuration->get('spam.enableCaptchaCode')),
            'ldap' => $this->configuration->isLdapActive(),
            'elasticsearch' => $this->toBool($this->configuration->get('search.enableElasticsearch')),
            'opensearch' => $this->toBool($this->configuration->get('search.enableOpenSearch')),
            'sso' => $this->toBool($this->configuration->get('security.ssoSupport')),
            'signInWithMicrosoft' => $this->configuration->isSignInWithMicrosoftActive(),
        ];
    }

    private function buildPublicLogoUrl(): string
    {
        return rtrim($this->configuration->getDefaultUrl(), characters: '/') . '/assets/images/logo-transparent.svg';
    }

    /**
     * @return array<string, array<string, string>>
     */
    private function buildThemeColors(): array
    {
        $themeCssPath = PMF_ROOT_DIR . '/assets/templates/default/theme.css';
        if (!is_readable($themeCssPath)) {
            return [
                'light' => [],
                'dark' => [],
                'highContrast' => [],
            ];
        }

        $themeCss = file_get_contents($themeCssPath);
        if ($themeCss === false) {
            return [
                'light' => [],
                'dark' => [],
                'highContrast' => [],
            ];
        }

        return [
            'light' => $this->extractThemeVariables($themeCss, ":root,\n[data-bs-theme='light']"),
            'dark' => $this->extractThemeVariables($themeCss, "[data-bs-theme='dark']"),
            'highContrast' => $this->extractThemeVariables($themeCss, "[data-bs-theme='high-contrast']"),
        ];
    }

    /**
     * @return array<string, string>
     */
    private function extractThemeVariables(string $themeCss, string $selector): array
    {
        $pattern = sprintf('/%s\s*\{(?P<body>.*?)^\}/ms', preg_quote($selector, delimiter: '/'));
        if (preg_match($pattern, $themeCss, $matches) !== 1) {
            return [];
        }

        preg_match_all(
            '/(?P<name>--[A-Za-z0-9\-]+)\s*:\s*(?P<value>[^;]+);/',
            $matches['body'],
            $variableMatches,
            PREG_SET_ORDER,
        );

        $variables = [];
        foreach ($variableMatches as $variableMatch) {
            $variables[$variableMatch['name']] = trim($variableMatch['value']);
        }

        return $variables;
    }

    private function toBool(mixed $value): bool
    {
        return $value === true || $value === 1 || $value === '1' || $value === 'true';
    }
}
