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

    private function toBool(mixed $value): bool
    {
        return $value === true || $value === 1 || $value === '1' || $value === 'true';
    }
}
