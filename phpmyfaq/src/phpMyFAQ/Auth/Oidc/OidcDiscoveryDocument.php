<?php

/**
 * Typed OIDC discovery document.
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

namespace phpMyFAQ\Auth\Oidc;

use InvalidArgumentException;

final readonly class OidcDiscoveryDocument
{
    public function __construct(
        public string $issuer,
        public string $authorizationEndpoint,
        public string $tokenEndpoint,
        public string $userInfoEndpoint,
        public string $jwksUri,
        public ?string $endSessionEndpoint = null,
    ) {
    }

    /**
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        foreach ([
            'issuer',
            'authorization_endpoint',
            'token_endpoint',
            'userinfo_endpoint',
            'jwks_uri',
        ] as $requiredKey) {
            if (
                !array_key_exists($requiredKey, $data)
                || !is_string($data[$requiredKey])
                || trim($data[$requiredKey]) === ''
            ) {
                throw new InvalidArgumentException(sprintf(
                    'Missing or invalid OIDC discovery field: %s',
                    $requiredKey,
                ));
            }
        }

        $endSessionEndpoint = null;
        if (array_key_exists('end_session_endpoint', $data) && is_string($data['end_session_endpoint'])) {
            $endSessionEndpoint = trim($data['end_session_endpoint']);
        }

        return new self(
            issuer: trim($data['issuer']),
            authorizationEndpoint: trim($data['authorization_endpoint']),
            tokenEndpoint: trim($data['token_endpoint']),
            userInfoEndpoint: trim($data['userinfo_endpoint']),
            jwksUri: trim($data['jwks_uri']),
            endSessionEndpoint: $endSessionEndpoint !== '' ? $endSessionEndpoint : null,
        );
    }
}
