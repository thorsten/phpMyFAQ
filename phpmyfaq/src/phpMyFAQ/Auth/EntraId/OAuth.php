<?php

/**
 * OAuth class for Entra ID.
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at https://mozilla.org/MPL/2.0/.
 *
 * @package   phpMyFAQ
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2022-2026 phpMyFAQ Team
 * @license   https://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2022-09-09
 */

declare(strict_types=1);

namespace phpMyFAQ\Auth\EntraId;

use Firebase\JWT\JWT;
use JsonException;
use phpMyFAQ\Configuration;
use stdClass;
use Symfony\Component\HttpClient\HttpClient;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Throwable;

/**
 * Class OAuth
 *
 * @package phpMyFAQ\Auth\Azure
 */
class OAuth
{
    private HttpClientInterface $httpClient;

    /** @var stdClass|null JWT */
    private ?stdClass $token = null;

    private ?string $refreshToken = null;

    private ?string $accessToken = null;

    private ?JwksProvider $jwksProvider;

    /**
     * Constructor.
     */
    public function __construct(
        private readonly Configuration $configuration,
        private readonly EntraIdSession $entraIdSession,
        ?JwksProvider $jwksProvider = null,
    ) {
        $this->httpClient = HttpClient::create();
        $this->jwksProvider = $jwksProvider;
    }

    public function setJwksProvider(?JwksProvider $jwksProvider): OAuth
    {
        $this->jwksProvider = $jwksProvider;
        return $this;
    }

    /**
     * Returns the error message.
     */
    public function errorMessage(string $message): string
    {
        return $message;
    }

    /**
     * Returns the Authorization Code from Entra ID.
     *
     * @throws JsonException
     * @throws TransportExceptionInterface
     */
    public function getOAuthToken(string $code): stdClass
    {
        $url = 'https://login.microsoftonline.com/' . AAD_OAUTH_TENANTID . '/oauth2/v2.0/token';

        $codeVerifier = $this->entraIdSession->getCookie(EntraIdSession::ENTRA_ID_OAUTH_VERIFIER);
        if ($this->entraIdSession->get(EntraIdSession::ENTRA_ID_OAUTH_VERIFIER) !== '') {
            $codeVerifier = $this->entraIdSession->get(EntraIdSession::ENTRA_ID_OAUTH_VERIFIER);
        }

        $response = $this->httpClient->request('POST', $url, [
            'body' => [
                'grant_type' => 'authorization_code',
                'client_id' => AAD_OAUTH_CLIENTID,
                'redirect_uri' => $this->configuration->getDefaultUrl() . 'services/azure/callback.php',
                'code' => $code,
                'code_verifier' => $codeVerifier,
                'client_secret' => AAD_OAUTH_SECRET,
            ],
        ]);

        $content = $response->getContent(false);
        $statusCode = $response->getStatusCode();

        if ($statusCode >= 400) {
            try {
                /** @var stdClass $errorPayload */
                $errorPayload = json_decode(json: $content, associative: null, depth: 512, flags: JSON_THROW_ON_ERROR);
                $error = $errorPayload->error ?? 'oauth_error';
                $description = $errorPayload->error_description ?? $content;
                throw new \RuntimeException(sprintf('OAuth token exchange failed (%s): %s', $error, $description));
            } catch (JsonException) {
                throw new \RuntimeException(sprintf('OAuth token exchange failed: %s', $content));
            }
        }

        return json_decode(json: $content, associative: null, depth: 512, flags: JSON_THROW_ON_ERROR);
    }

    /**
     * @throws JsonException
     * @throws TransportExceptionInterface
     */
    public function refreshToken(): mixed
    {
        $url = 'https://login.microsoftonline.com/' . AAD_OAUTH_TENANTID . '/oauth2/v2.0/token';

        $response = $this->httpClient->request('POST', $url, [
            'body' => [
                'grant_type' => 'refresh_token',
                'refresh_token' => $this->getRefreshToken(),
                'client_id' => AAD_OAUTH_CLIENTID,
                'scope' => AAD_OAUTH_SCOPE,
            ],
        ]);

        return json_decode(json: $response->getContent(), associative: null, depth: 512, flags: JSON_THROW_ON_ERROR);
    }

    public function getToken(): stdClass
    {
        return $this->token;
    }

    public function setToken(#[\SensitiveParameter] stdClass $token): OAuth
    {
        if ($this->jwksProvider === null) {
            $this->clearToken();
            return $this;
        }

        try {
            $idTokenString = (string) ($token->id_token ?? '');
            if ($idTokenString === '' || substr_count($idTokenString, needle: '.') !== 2) {
                $this->clearToken();
                return $this;
            }

            $keys = $this->jwksProvider->getKeys(AAD_OAUTH_TENANTID);
            $decoded = JWT::decode($idTokenString, $keys);

            $expectedIssuers = [
                'https://login.microsoftonline.com/' . AAD_OAUTH_TENANTID . '/v2.0',
                'https://sts.windows.net/' . AAD_OAUTH_TENANTID . '/',
            ];

            if (!property_exists($decoded, 'aud') || $decoded->aud !== AAD_OAUTH_CLIENTID) {
                $this->clearToken();
                return $this;
            }

            if (!property_exists($decoded, 'iss') || !in_array($decoded->iss, $expectedIssuers, strict: true)) {
                $this->clearToken();
                return $this;
            }

            $this->token = $decoded;
            $this->entraIdSession->set(EntraIdSession::ENTRA_ID_JWT, json_encode(
                value: $this->token,
                flags: JSON_THROW_ON_ERROR,
            ));
        } catch (JsonException|Throwable) {
            $this->clearToken();
        }

        return $this;
    }

    private function clearToken(): void
    {
        $this->token = new stdClass();
        $this->entraIdSession->set(EntraIdSession::ENTRA_ID_JWT, '{}');
    }

    public function getEntraIdSession(): EntraIdSession
    {
        return $this->entraIdSession;
    }

    public function getRefreshToken(): ?string
    {
        return $this->refreshToken;
    }

    public function setRefreshToken(#[\SensitiveParameter] ?string $refreshToken): OAuth
    {
        $this->refreshToken = $refreshToken;
        return $this;
    }

    public function getAccessToken(): ?string
    {
        return $this->accessToken;
    }

    public function setAccessToken(#[\SensitiveParameter] ?string $accessToken): OAuth
    {
        $this->accessToken = $accessToken;
        return $this;
    }

    public function getName(): string
    {
        return $this->token->name ?? '';
    }

    public function getMail(): string
    {
        return $this->token->preferred_username ?? '';
    }
}
