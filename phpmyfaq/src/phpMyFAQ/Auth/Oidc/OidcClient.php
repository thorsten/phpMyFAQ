<?php

/**
 * OIDC HTTP client helper.
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
use SensitiveParameter;
use Symfony\Contracts\HttpClient\Exception\ExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

final readonly class OidcClient
{
    public function __construct(
        private HttpClientInterface $httpClient,
    ) {
    }

    public function buildAuthorizationUrl(
        OidcProviderConfig $config,
        OidcDiscoveryDocument $discoveryDocument,
        string $state,
        string $nonce,
        string $codeChallenge,
    ): string {
        $query = http_build_query([
            'response_type' => 'code',
            'client_id' => $config->client->clientId,
            'redirect_uri' => $config->client->redirectUri,
            'scope' => $config->client->getScopesAsString(),
            'state' => $state,
            'nonce' => $nonce,
            'code_challenge' => $codeChallenge,
            'code_challenge_method' => 'S256',
        ]);

        return $discoveryDocument->authorizationEndpoint . '?' . $query;
    }

    /**
     * @return array<string, mixed>
     * @throws ExceptionInterface
     */
    public function exchangeAuthorizationCode(
        OidcProviderConfig $config,
        OidcDiscoveryDocument $discoveryDocument,
        string $code,
        #[SensitiveParameter]
        string $codeVerifier,
    ): array {
        $response = $this->httpClient->request('POST', $discoveryDocument->tokenEndpoint, [
            'body' => [
                'grant_type' => 'authorization_code',
                'code' => $code,
                'client_id' => $config->client->clientId,
                'client_secret' => $config->client->clientSecret,
                'redirect_uri' => $config->client->redirectUri,
                'code_verifier' => $codeVerifier,
            ],
        ]);

        $payload = $this->decodeJsonResponse($response->getContent(false), $response->getStatusCode(), 'token');
        if (
            !array_key_exists('access_token', $payload)
            || !is_string($payload['access_token'])
            || $payload['access_token'] === ''
        ) {
            throw new RuntimeException('OIDC token response did not contain a valid access_token');
        }

        return $payload;
    }

    /**
     * @return array<string, mixed>
     * @throws ExceptionInterface
     */
    public function fetchUserInfo(
        OidcDiscoveryDocument $discoveryDocument,
        #[SensitiveParameter]
        string $accessToken,
    ): array {
        $response = $this->httpClient->request('GET', $discoveryDocument->userInfoEndpoint, [
            'headers' => [
                'Authorization' => 'Bearer ' . $accessToken,
            ],
        ]);

        return $this->decodeJsonResponse($response->getContent(false), $response->getStatusCode(), 'userinfo');
    }

    public function buildLogoutUrl(
        OidcProviderConfig $config,
        OidcDiscoveryDocument $discoveryDocument,
        #[SensitiveParameter]
        string $idTokenHint = '',
    ): ?string {
        if ($discoveryDocument->endSessionEndpoint === null || $discoveryDocument->endSessionEndpoint === '') {
            return null;
        }

        $query = [
            'client_id' => $config->client->clientId,
        ];

        if ($config->logoutRedirectUrl !== '') {
            $query['post_logout_redirect_uri'] = $config->logoutRedirectUrl;
        }

        if ($idTokenHint !== '') {
            $query['id_token_hint'] = $idTokenHint;
        }

        return $discoveryDocument->endSessionEndpoint . '?' . http_build_query($query);
    }

    /**
     * @return array<string, mixed>
     */
    private function decodeJsonResponse(string $content, int $statusCode, string $context): array
    {
        if ($statusCode >= 400) {
            throw new RuntimeException(sprintf('OIDC %s request failed with status %d', $context, $statusCode));
        }

        try {
            $payload = json_decode($content, associative: true, depth: 512, flags: JSON_THROW_ON_ERROR);
        } catch (JsonException $exception) {
            throw new RuntimeException(sprintf('OIDC %s response is not valid JSON', $context), previous: $exception);
        }

        if (!is_array($payload)) {
            throw new RuntimeException(sprintf(
                'OIDC %s response is not a JSON object/array, got %s',
                $context,
                gettype($payload),
            ));
        }

        return $payload;
    }
}
