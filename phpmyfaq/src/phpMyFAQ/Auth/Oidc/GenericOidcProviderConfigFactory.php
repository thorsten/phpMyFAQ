<?php

/**
 * Builds the OidcProviderConfig for the generic OpenID Connect provider.
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
 * @since     2026-10-13
 */

declare(strict_types=1);

namespace phpMyFAQ\Auth\Oidc;

use InvalidArgumentException;
use phpMyFAQ\Configuration;

final readonly class GenericOidcProviderConfigFactory
{
    public const string PROVIDER = 'oidc';

    public function __construct(
        private Configuration $configuration,
    ) {
    }

    public function create(): OidcProviderConfig
    {
        $discoveryUrl = trim((string) $this->configuration->get('oidc.discoveryUrl'));
        $clientId = trim((string) $this->configuration->get('oidc.clientId'));
        $redirectUri = trim((string) $this->configuration->get('oidc.redirectUri'));
        $scopes = preg_split('/\s+/', trim((string) $this->configuration->get('oidc.scopes')));
        if ($scopes === false) {
            $scopes = [];
        }

        $enabled = $this->toBool($this->configuration->get('oidc.enable'));

        if ($enabled && ($discoveryUrl === '' || $clientId === '')) {
            $missing = array_filter([
                $discoveryUrl === '' ? 'discoveryUrl' : null,
                $clientId === '' ? 'clientId' : null,
            ]);
            throw new InvalidArgumentException(sprintf('OpenID Connect enabled but missing: %s', implode(
                ' and ',
                $missing,
            )));
        }

        if ($redirectUri === '') {
            $redirectUri = rtrim($this->configuration->getDefaultUrl(), characters: '/') . '/auth/oidc/callback';
        }

        return new OidcProviderConfig(
            provider: self::PROVIDER,
            enabled: $enabled,
            discoveryUrl: $discoveryUrl,
            client: new OidcClientConfig(
                clientId: $clientId,
                clientSecret: (string) $this->configuration->get('oidc.clientSecret'),
                redirectUri: $redirectUri,
                scopes: array_values(array_filter($scopes, static fn(string $scope): bool => $scope !== '')),
            ),
            autoProvision: $this->toBool($this->configuration->get('oidc.autoProvision')),
            logoutRedirectUrl: trim((string) $this->configuration->get('oidc.logoutRedirectUrl')),
        );
    }

    private function toBool(mixed $value): bool
    {
        return $value === true || $value === 1 || $value === '1' || $value === 'true';
    }
}
