<?php

/**
 * OAuth class for Azure Active Directory.
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at https://mozilla.org/MPL/2.0/.
 *
 * @package   phpMyFAQ
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2022-2023 phpMyFAQ Team
 * @license   https://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2022-09-09
 */

namespace phpMyFAQ\Auth\Azure;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use phpMyFAQ\Configuration;
use phpMyFAQ\Session;
use stdClass;

/**
 * Class OAuth
 *
 * @package phpMyFAQ\Auth\Azure
 */
class OAuth
{
    /** @var stdClass|null JWT */
    private ?stdClass $token = null;

    private ?string $refreshToken = null;

    private ?string $accessToken = null;

    /**
     * Constructor.
     */
    public function __construct(private readonly Configuration $config, private readonly Session $session)
    {
    }

    /**
     * Returns the error message.
     */
    public function errorMessage(string $message): string
    {
        return $message;
    }

    /**
     * Returns the Authorization Code from Azure AD.
     *
     * @throws GuzzleException
     * @throws \JsonException
     */
    public function getOAuthToken(string $code): stdClass
    {
        $client = new Client([
            'base_uri' => 'https://login.microsoftonline.com/' . AAD_OAUTH_TENANTID . '/oauth2/v2.0/',
        ]);

        $response = $client->request('POST', 'token', [
            'form_params' => [
                'grant_type' => 'authorization_code',
                'client_id' => AAD_OAUTH_CLIENTID,
                'redirect_uri' => $this->config->getDefaultUrl() . 'services/azure/callback.php',
                'code' => $code,
                'code_verifier' => $this->session->get(Session::PMF_AZURE_AD_OAUTH_VERIFIER),
                'client_secret' => AAD_OAUTH_SECRET
            ]
        ]);

        return json_decode($response->getBody(), null, 512, JSON_THROW_ON_ERROR);
    }

    /**
     * @throws GuzzleException|\JsonException
     */
    public function refreshToken()
    {
        $client = new Client([
            'base_uri' => 'https://login.microsoftonline.com/' . AAD_OAUTH_TENANTID . '/oauth2/v2.0/',
        ]);

        $response = $client->request('POST', 'token', [
            'form_params' => [
                'grant_type' => 'refresh_token',
                'refresh_token' => $this->getRefreshToken(),
                'client_id' => AAD_OAUTH_CLIENTID,
                'scope' => AAD_OAUTH_SCOPE
            ]
        ]);

        return json_decode($response->getBody(), null, 512, JSON_THROW_ON_ERROR);
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
        $this->session->set(Session::PMF_AZURE_AD_JWT, json_encode($this->token, JSON_THROW_ON_ERROR));
        return $this;
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
