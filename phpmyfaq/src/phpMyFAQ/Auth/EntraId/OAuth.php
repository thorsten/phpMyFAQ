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
 * @copyright 2022-2024 phpMyFAQ Team
 * @license   https://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2022-09-09
 */

namespace phpMyFAQ\Auth\EntraId;

use phpMyFAQ\Configuration;
use stdClass;
use Symfony\Component\HttpClient\HttpClient;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

/**
 * Class OAuth
 *
 * @package phpMyFAQ\Auth\Azure
 */
class OAuth
{
    private HttpClientInterface $client;

    /** @var stdClass|null JWT */
    private ?stdClass $token = null;

    private ?string $refreshToken = null;

    private ?string $accessToken = null;

    /**
     * Constructor.
     */
    public function __construct(private readonly Configuration $configuration, private readonly Session $session)
    {
        $this->client = HttpClient::create();
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
     * @throws \JsonException
     * @throws TransportExceptionInterface
     */
    public function getOAuthToken(string $code): stdClass
    {
        $url = 'https://login.microsoftonline.com/' . AAD_OAUTH_TENANTID . '/oauth2/v2.0/token';

        if ($this->session->get(Session::ENTRA_ID_OAUTH_VERIFIER) !== '') {
            $codeVerifier = $this->session->get(Session::ENTRA_ID_OAUTH_VERIFIER);
        } else {
            $codeVerifier = $this->session->getCookie(Session::ENTRA_ID_OAUTH_VERIFIER);
        }

        $response = $this->client->request('POST', $url, [
            'body' => [
                'grant_type' => 'authorization_code',
                'client_id' => AAD_OAUTH_CLIENTID,
                'redirect_uri' => $this->configuration->getDefaultUrl() . 'services/entra-id/callback.php',
                'code' => $code,
                'code_verifier' => $codeVerifier,
                'client_secret' => AAD_OAUTH_SECRET
            ]
        ]);

        return json_decode($response->getContent(), null, 512, JSON_THROW_ON_ERROR);
    }

    /**
     * @throws \JsonException
     * @throws TransportExceptionInterface
     */
    public function refreshToken()
    {
        $url = 'https://login.microsoftonline.com/' . AAD_OAUTH_TENANTID . '/oauth2/v2.0/token';

        $response = $this->client->request('POST', $url, [
            'body' => [
                'grant_type' => 'refresh_token',
                'refresh_token' => $this->getRefreshToken(),
                'client_id' => AAD_OAUTH_CLIENTID,
                'scope' => AAD_OAUTH_SCOPE
            ]
        ]);

        return json_decode($response->getContent(), null, 512, JSON_THROW_ON_ERROR);
    }

    public function getToken(): stdClass
    {
        return $this->token;
    }

    /**
     * @throws \JsonException
     */
    public function setToken(stdClass $token): OAuth
    {
        $idToken = base64_decode(explode('.', (string) $token->id_token)[1]);
        $this->token = json_decode($idToken, null, 512, JSON_THROW_ON_ERROR);
        $this->session->set(Session::ENTRA_ID_JWT, json_encode($this->token, JSON_THROW_ON_ERROR));
        return $this;
    }

    public function getSession(): Session
    {
        return $this->session;
    }

    public function getRefreshToken(): ?string
    {
        return $this->refreshToken;
    }

    public function setRefreshToken(?string $refreshToken): OAuth
    {
        $this->refreshToken = $refreshToken;
        return $this;
    }

    public function getAccessToken(): ?string
    {
        return $this->accessToken;
    }

    public function setAccessToken(?string $accessToken): OAuth
    {
        $this->accessToken = $accessToken;
        return $this;
    }

    public function getName(): string
    {
        return $this->token->name;
    }

    public function getMail(): string
    {
        return $this->token->preferred_username;
    }
}
