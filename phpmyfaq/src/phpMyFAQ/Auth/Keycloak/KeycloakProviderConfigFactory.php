<?php

/**
 * Keycloak OIDC provider config factory.
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
 * @since     2026-04-18
 */

declare(strict_types=1);

namespace phpMyFAQ\Auth\Keycloak;

use InvalidArgumentException;
use phpMyFAQ\Auth\Oidc\OidcClientConfig;
use phpMyFAQ\Auth\Oidc\OidcProviderConfig;
use phpMyFAQ\Configuration;

final readonly class KeycloakProviderConfigFactory
{
    public function __construct(
        private Configuration $configuration,
    ) {
    }

    public function create(): OidcProviderConfig
    {
        $baseUrl = rtrim(trim((string) $this->configuration->get('keycloak.baseUrl')), characters: '/');
        $realm = trim((string) $this->configuration->get('keycloak.realm'));
        $redirectUri = trim((string) $this->configuration->get('keycloak.redirectUri'));
        $scopes = preg_split('/\s+/', trim((string) $this->configuration->get('keycloak.scopes')));
        if ($scopes === false) {
            $scopes = [];
        }

        $enabled = $this->toBool($this->configuration->get('keycloak.enable'));

        if ($enabled && ($baseUrl === '' || $realm === '')) {
            $missing = array_filter([
                $baseUrl === '' ? 'baseUrl' : null,
                $realm === '' ? 'realm' : null,
            ]);
            throw new InvalidArgumentException(sprintf('Keycloak enabled but missing: %s', implode(' and ', $missing)));
        }

        if ($redirectUri === '') {
            $redirectUri = rtrim($this->configuration->getDefaultUrl(), characters: '/') . '/auth/keycloak/callback';
        }

        return new OidcProviderConfig(
            provider: 'keycloak',
            enabled: $enabled,
            discoveryUrl: $this->buildDiscoveryUrl($baseUrl, $realm),
            client: new OidcClientConfig(
                clientId: trim((string) $this->configuration->get('keycloak.clientId')),
                clientSecret: (string) $this->configuration->get('keycloak.clientSecret'),
                redirectUri: $redirectUri,
                scopes: array_values(array_filter($scopes, static fn(string $scope): bool => $scope !== '')),
            ),
            autoProvision: $this->toBool($this->configuration->get('keycloak.autoProvision')),
            logoutRedirectUrl: trim((string) $this->configuration->get('keycloak.logoutRedirectUrl')),
        );
    }

    private function buildDiscoveryUrl(string $baseUrl, string $realm): string
    {
        if ($baseUrl === '' || $realm === '') {
            return '';
        }

        return $baseUrl . '/realms/' . rawurlencode($realm) . '/.well-known/openid-configuration';
    }

    private function toBool(mixed $value): bool
    {
        return $value === true || $value === 1 || $value === '1' || $value === 'true';
    }
}
