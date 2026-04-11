<?php

/**
 * OAuth discovery metadata service.
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

final readonly class OAuthDiscoveryService
{
    public function __construct(
        private Configuration $configuration,
    ) {
    }

    /**
     * @return array<string, bool|string|string[]>
     */
    public function getMetaDiscovery(): array
    {
        $document = $this->getDiscoveryDocument();

        return [
            'enabled' => $this->isEnabled(),
            'issuer' => (string) $document['issuer'],
            'authorizationEndpoint' => (string) $document['authorization_endpoint'],
            'tokenEndpoint' => (string) $document['token_endpoint'],
            'grantTypesSupported' => $document['grant_types_supported'],
            'responseTypesSupported' => $document['response_types_supported'],
            'tokenEndpointAuthMethodsSupported' => $document['token_endpoint_auth_methods_supported'],
        ];
    }

    /**
     * @return array<string, string|string[]>
     */
    public function getDiscoveryDocument(): array
    {
        $apiBaseUrl = rtrim($this->configuration->getDefaultUrl(), characters: '/') . '/api';

        return [
            'issuer' => $apiBaseUrl,
            'authorization_endpoint' => $apiBaseUrl . '/oauth/authorize',
            'token_endpoint' => $apiBaseUrl . '/oauth/token',
            'grant_types_supported' => ['authorization_code', 'client_credentials', 'refresh_token'],
            'response_types_supported' => ['code'],
            'token_endpoint_auth_methods_supported' => ['client_secret_basic', 'client_secret_post', 'none'],
        ];
    }

    public function isEnabled(): bool
    {
        $value = $this->configuration->get('oauth2.enable');

        return $value === true || $value === 1 || $value === '1' || $value === 'true';
    }
}
