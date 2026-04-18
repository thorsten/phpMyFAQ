<?php

/**
 * OIDC discovery document loader.
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

use JsonException;
use RuntimeException;
use Symfony\Contracts\HttpClient\Exception\ExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

final readonly class OidcDiscoveryService
{
    public function __construct(
        private HttpClientInterface $httpClient,
    ) {
    }

    /**
     * @throws ExceptionInterface
     */
    public function discover(OidcProviderConfig $config): OidcDiscoveryDocument
    {
        $response = $this->httpClient->request('GET', $config->discoveryUrl);
        $content = $response->getContent(false);

        if ($response->getStatusCode() >= 400) {
            throw new RuntimeException(sprintf(
                'OIDC discovery request failed for %s with status %d',
                $config->provider,
                $response->getStatusCode(),
            ));
        }

        try {
            /** @var array<string, mixed> $payload */
            $payload = json_decode($content, associative: true, depth: 512, flags: JSON_THROW_ON_ERROR);
        } catch (JsonException $exception) {
            throw new RuntimeException('OIDC discovery response is not valid JSON', previous: $exception);
        }

        return OidcDiscoveryDocument::fromArray($payload);
    }
}
